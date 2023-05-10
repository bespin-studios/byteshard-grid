<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Action\Grid;

use byteShard\Cell;
use byteShard\ID\RowID;
use byteShard\Internal\Action;
use byteShard\Internal\Action\ActionResultInterface;
use byteShard\Internal\Grid\Column;

/**
 * @API
 */
class SetCellValue extends Action
{
    private string $cell;
    private array  $newValues = [];

    public function __construct(string $cell)
    {
        parent::__construct();
        $this->cell = Cell::getContentCellName($cell);
    }

    /**
     * @API
     */
    public function setNewValue(array $rowId, Column $column, string $newValue)
    {
        $rowIdObject = new RowID($rowId);

        $this->newValues[$rowIdObject->getEncodedRowId()] = [
            'row' => $rowIdObject,
            'col' => [
                $column->field => [
                    'column'   => $column,
                    'newValue' => $newValue
                ]
            ]
        ];
    }

    protected function runAction(): ActionResultInterface
    {
        $result = ['state' => 2];
        if (!empty($this->newValues)) {
            $cells = $this->getCells([$this->cell]);
            foreach ($cells as $cell) {
                $cellNonce = $cell->getNonce();
                foreach ($this->newValues as $values) {
                    $encryptedRowId = $values['row']->getEncryptedRowId($cellNonce);
                    foreach ($values['col'] as $data) {
                        $data['column']->setLocaleBaseToken($cell->createLocaleBaseToken('Cell').'.Grid.');
                        $columnId = $data['column']->getEncryptedName($cellNonce);

                        $result['LCell'][$cell->containerId()][$cell->cellId()]['updateGridData'][$encryptedRowId]['columns'][$columnId]['value'] = $data['newValue'];
                    }
                }
            }
        }
        return new Action\ActionResultMigrationHelper($result);
    }
}