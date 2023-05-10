<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Validate;

use byteShard\Internal\Struct\ValidationFailed;
use byteShard\Internal\Struct\ClientData;
use byteShard\Enum;
use byteShard\Locale;
use byteShard\Internal\Validate;
use byteShard\ID;
use byteShard\Session;
use DateTime;
use Exception;

/**
 * Class Grid
 * @package byteShard\Internal\Validate
 */
class Grid
{
    private array             $clientData;
    private array             $rowIDs;
    private array             $gridColumns = [];
    private array             $gridNodes   = [];
    private ?ClientData       $validatedClientData;
    private ?ValidationFailed $failedValidations;

    public function __construct(array $clientData, array $clientRows, ?array $gridColumns = null, ?array $gridNodes = null)
    {
        $this->clientData = $clientData;
        $this->rowIDs     = $clientRows;
        if ($gridColumns !== null) {
            $this->gridColumns = $gridColumns;
        }
        if ($gridNodes !== null) {
            $this->gridNodes = $gridNodes;
        }
    }

    public function validate($dataTime): object
    {
        $this->validatedClientData = new ClientData();
        $this->validatedClientData->setRequestDataTime(DateTime::createFromFormat('U.u', $dataTime));
        $this->validatedClientData->setSendDataTime(DateTime::createFromFormat('U.u', microtime(true)));
        $this->failedValidations = new ValidationFailed();

        $this->unsetUnused();
        if (count($this->gridColumns) > 0 && count($this->gridNodes) > 0) {
            $this->validateGridColumns();
        }
        if ($this->failedValidations->failedValidations === 0) {
            return $this->validatedClientData;
        } else {
            return $this->failedValidations;
        }
    }

    private function unsetUnused(): void
    {
        //TODO: remove timestamp from js, delete this function and function call afterwards
        if (isset($this->clientData['timestamp'])) {
            unset($this->clientData['timestamp']);
        }
    }

    private function validateGridColumns(): void
    {
        if (count($this->clientData) > 0 && count($this->rowIDs) > 0) {
            if (array_key_exists('colID', $this->clientData) && array_key_exists('newVal', $this->clientData) && array_key_exists($this->clientData['colID'], $this->gridColumns)) {
                $encryptedColumnData = $this->clientData['colID'];
                try {
                    $columnData = json_decode(Session::decrypt($encryptedColumnData));
                } catch (Exception $e) {
                    $columnData = null;
                }

                $columnValidations = null;
                if ($columnData !== null) {
                    $type        = $columnData->t;
                    $columnId    = $columnData->id;
                    $accessType  = property_exists($columnData, 'a') ? $columnData->a : 0;
                    $validations = null;
                    $label       = array_key_exists('label', $this->clientData) ? htmlspecialchars($this->clientData['label'], ENT_QUOTES, 'UTF-8') : '';
                    $dateFormat  = 'Y-m-d H:i:s';
                    if (property_exists($columnData, 'v')) {
                        $columnValidations = $columnData->v;
                    }
                } else {
                    $encryptedName = $this->clientData['colID'];
                    $type          = $this->gridColumns[$encryptedName]['type'];
                    $columnId      = $this->gridColumns[$encryptedName]['name'];
                    $accessType    = $this->gridColumns[$encryptedName]['accessType'];
                    $validations   = array_key_exists('validations', $this->gridColumns[$encryptedName]) ? $this->gridColumns[$encryptedName]['validations'] : null;
                    $label         = array_key_exists('label', $this->gridColumns[$encryptedName]) ? $this->gridColumns[$encryptedName]['label'] : '';
                    $dateFormat    = array_key_exists('date_format', $this->gridColumns[$encryptedName]) ? $this->gridColumns[$encryptedName]['date_format'] : null;
                }

                if ($accessType === Enum\AccessType::RW) {
                    $clientValue      = $this->clientData['newVal'];
                    $validationResult = Validate::validate($clientValue, $type, $validations, $dateFormat);
                    if ($columnValidations !== null) {
                        if (property_exists($columnValidations, 'a')) {
                            foreach ($columnValidations->a as $validationClassName) {
                                if (str_starts_with($validationClassName, '!')) {
                                    $validationClassName = '\byteShard\Validation\\'.ltrim($validationClassName, '!');
                                } else {
                                    $validationClassName = '\\'.$validationClassName;
                                }
                                var_dump($validationClassName::verify($clientValue));
                            }
                        }
                        if (property_exists($columnValidations, 'o')) {
                            foreach ($columnValidations->c as $validationClassName => $arg) {
                                if (str_starts_with($validationClassName, '!')) {
                                    $validationClassName = '\byteShard\Validation\\'.ltrim($validationClassName, '!');
                                } else {
                                    $validationClassName = '\\'.$validationClassName;
                                }
                                var_dump($validationClassName::verify($clientValue, $arg));
                                //$validationClassName::verify($clientValue, $arg);
                            }
                        }
                    }
                    if ($validationResult->validationsFailed === 0) {
                        $rows = ID::explode($this->rowIDs);
                        if ($rows !== null) {
                            if (is_object($rows)) {
                                $rows = array($rows);
                            }
                            if (is_array($rows)) {
                                $this->validatedClientData->setColumn($columnId, $clientValue, $type, $this->clientData['colID']);
                                $typeArray = [];
                                foreach ($this->gridNodes as $node) {
                                    $typeArray[$node['name']] = $node['type'];
                                }
                                foreach ($rows as $rowID) {
                                    $row = $this->validatedClientData->addRow();
                                    $row->addField($columnId, $clientValue, $type);
                                    foreach ($rowID as $rowProperty => $rowValue) {
                                        if (!in_array($rowProperty, ['encryptedId', 'decryptedId'])) {
                                            $row->addField($rowProperty, $rowValue, array_key_exists($rowProperty, $typeArray) ? $typeArray[$rowProperty] : '');
                                        }
                                    }
                                }
                            } else {
                                //TODO: log suspicious requests
                                //TODO: add some form of client message
                                $this->failedValidations->failedValidations++;
                            }
                        } else {
                            //TODO: log suspicious requests
                            //TODO: add some form of client message
                            $this->failedValidations->failedValidations++;
                        }
                    } else {
                        $this->failedValidations->failedValidations                              += $validationResult->validationsFailed;
                        $this->failedValidations->failedValidationsDataArray[$columnId]['label'] = $label;
                        // add the Form\Control Label name to the error message
                        // this can't be done in BSValidate because it's different for Form and Grid etc.
                        if (is_array($validationResult->failedRules)) {
                            foreach ($validationResult->failedRules as $failureType => $failureText) {
                                $validationResult->failedRules[$failureType] = sprintf(Locale::get('byteShard.validate.grid.column'), $label, $failureText);
                            }
                        }
                        $this->failedValidations->failedValidationsDataArray[$columnId]['failedRules'] = $validationResult->failedRules;
                    }
                } else {
                    // only hidden fields and fields with write access are returned, the rest is discarded.
                    // if any other fields are needed it has to be implemented here
                }
            } else {
                // either colID / newVal are missing from the client request or the encrypted name in colID could not be found in the grid
                //TODO: log suspicious requests
            }
        }
    }
}
