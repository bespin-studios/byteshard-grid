<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard;

use byteShard\Enum\AccessType;
use byteShard\Grid\Column\RowSelector;
use byteShard\Grid\Event\OnDrop;
use byteShard\Grid\GridInterface;
use byteShard\Grid\Node;
use byteShard\ID\RowID;
use byteShard\Internal\CellContent;
use byteShard\Internal\Export\Handler;
use byteShard\Internal\Export\HandlerInterface;
use byteShard\Internal\ExportHandler;
use byteShard\Internal\Grid\Column;
use byteShard\Internal\Grid\ColumnProxy;
use byteShard\Internal\SimpleXML;
use byteShard\Internal\Struct\ClientData;
use byteShard\Internal\Struct\ClientDataInterface;
use byteShard\Internal\Struct\GetData;
use byteShard\Internal\Struct\ValidationFailed;
use byteShard\Popup\Message;
use DateTime;
use SimpleXMLElement;


//README: special columns in data:
// ->Style string
// -><field>_SPAN int

/**
 * Class Grid
 * @package byteShard
 */
abstract class Grid extends CellContent implements GridInterface
{
    /**
     * columns passed by defineCellContent
     * @var Column[]
     */
    private array $columns = [];

    /**
     * @var string
     */
    protected string $cellContentType = 'DHTMLXGrid';

    /** @var ColumnProxy[] */
    private array $columnProxies   = [];
    private array $multilineHeader = array();
    /** @var Node[] */
    private array  $nodes = [];
    private string $query;
    private array  $queryParameters;
    private string $filterQuery;
    private array  $filterQueryParameters;


    private bool   $selectLastSelected = true;
    private string $selectRowWithID;
    private int    $viewVersion        = 1;
    private int    $numberOfColumns    = 0;
    private int    $editLevel          = 1;
    private string $timestamp;

    // Events
    private bool $eventOnLinkClick = false;
    private bool $eventOnCellEdit  = false;
    private bool $eventOnCheck     = false;
    private bool $eventOnDrop      = false;

    // Parameters:
    protected bool $rowSpan               = false;
    protected bool $multiline             = true;
    protected bool $gridStatsInCellHeader = true;
    protected bool $treeCellEdit          = false;
    protected bool $smartRendering        = true;
    protected int  $preRendering          = 50;
    protected bool $headerMenu            = true;
    protected bool $columnMove            = true;
    protected bool $cookieOrderSaving     = true;
    protected bool $cookieSizeSaving      = true;
    protected bool $cookieHiddenSaving    = true;

    // Class internal variables for building output array
    private int   $visibleLevels = 0;
    private int   $expandToLevel = 0;
    private array $inputArray    = [];
    private array $outputArray   = [];
    private bool  $sort          = true;

    // Class internal variables for building output xml
    private array            $columnDefinition = [];
    private SimpleXMLElement $outputXml;

    private array  $columnData        = [];
    private string $rowSelectorColumn = '';


    public function newRunClientGridUpdate(ClientDataInterface $clientData): array
    {
        //TODO: refactor like SaveFormMessage
        $result['state'] = 1;
        if ($this->getAccessType() === Enum\AccessType::RW) {
            if (method_exists($this, 'defineUpdate')) {
                if ($clientData instanceof ValidationFailed) {
                    // validation failed, create message popup from the failed messages
                    $message = [];
                    /* @var Object[] $clientData */
                    foreach ($clientData->failedValidationsDataArray as $val) {
                        if (isset($val['failedRules']) && is_array($val['failedRules'])) {
                            foreach ($val['failedRules'] as $failure_message) {
                                $message[] = $failure_message;
                            }
                        }
                    }
                    if (count($message) === 0) {
                        $message[] = Locale::get('byteShard.cellContent.no_failed_validation_messages');
                    }
                    $msg = new Message();
                    $msg->setMessage($message);
                    return $msg->getNavigationArray();
                }

                if ($clientData instanceof ClientData || $clientData instanceof GetData) {
                    // validation ok, set validated data as clientData and run method defineUpdate which needs to be defined in the respective cell
                    $this->clientData = $clientData;
                    $result           = $this->defineUpdate();
                    if (is_array($result)) {
                        $actions = [];
                        foreach ($result as $key => $item) {
                            if ($item instanceof \byteShard\Internal\Action) {
                                $item->setClientTimeZone($this->getClientTimeZone());
                                $actions[] = $item;
                                unset($result[$key]);
                            }
                        }
                        if (!empty($actions)) {
                            $result = array_merge_recursive(Action::getClientResponse($this->cell, null, ...$actions));
                        }

                        if (array_key_exists('success', $result)) {
                            unset($result['success']);
                        }
                        if (array_key_exists('changes', $result)) {
                            unset($result['changes']);
                        }
                    } elseif ($result instanceof \byteShard\Internal\Action) {
                        $result = Action::getClientResponse($this->cell, null, $result);
                    } else {
                        $msg = new Message(Locale::get('byteShard.cellContent.unexpected_return_value'));
                        return $msg->getNavigationArray();
                    }
                } else {
                    // $clientData is neither of type Struct\ClientData nor Struct\ValidationFailed
                    $msg = new Message(Locale::get('byteShard.cellContent.unexpected_client_data'));
                    return $msg->getNavigationArray();
                }
            } else {
                $msg = new Message(Locale::get('byteShard.cellContent.undefined_method'));
                return $msg->getNavigationArray();
            }
        } else {
            $msg = new Message(Locale::get('byteShard.cellContent.permission'));
            return $msg->getNavigationArray();
        }
        if (is_array($result) && array_key_exists('state', $result) && is_array($result['state'])) {
            $result['state'] = min(...$result['state']);
        }
        if ($result === null || !isset($result['state']) || $result['state'] !== 2) {
            $msg             = new Message(Locale::get('byteShard.cellContent.generic'));
            $result          = $msg->getNavigationArray();
            $result['state'] = 2;
        }
        return $result;
    }

    /**
     * store client request time
     * only needed for cells with write access
     *
     * @session write
     */
    private function setRequestTimestamp()
    {
        $this->cell->setRequestTimestamp();
    }

    /**
     * @session write (setRequestTimestamp, storeCellEvents, Cell::setContentControlType)
     * @session read (Session::getDBTimeZone, Session::getClientTimeZone, Session::getDateTimeFormat)
     * @param array $content
     * @return array
     * @throws Exception
     * @internal
     */
    public function getCellContent(array $content = []): array
    {
        $parentContent = parent::getCellContent($content);
        $this->setRequestTimestamp();
        $this->defineCellContent();
        $nonce = $this->cell->getNonce();
        // create column proxy
        $this->createColumnProxies($nonce);

        $cellEvents = $this->getCellEvents();
        // create node proxy
        //TODO: create node proxy, create getters for all necessary properties and access them here
        // create publicly accessible properties in node proxy and only use the node proxy from here on
        foreach ($this->nodes as $node) {
            $node->setParentAccessType($this->getAccessType());
        }

        if (!empty($this->nodes) && count($this->columns) > 0) {
            session_write_close();

            $this->queryData();
            if ($this->sort === true) {
                $this->sortArray();
            }
            if (!empty($this->inputArray)) {
                if ($this->visibleLevels === 1) {
                    $this->buildFlatGrid($nonce);
                } elseif ($this->visibleLevels > 1) {
                    $this->determineEditLevel();
                    $this->buildTreeGrid($nonce);
                }
            }
            // test if any column has row-span
            foreach ($this->columnProxies as $columnProxy) {
                if ($columnProxy->hasRowSpan() === true) {
                    $this->rowSpan = true;
                    break;
                }
            }
            $this->selectLastSelectedRow();
        }
        return array_merge(
            $parentContent,
            array_filter(['cellHeader' => $this->getCellHeader()]),
            [
                'content'           => $this->getXML(),
                'contentType'       => $this->cellContentType,
                'contentEvents'     => $cellEvents,
                'contentParameters' => ['cn' => base64_encode($nonce)],
                'contentFormat'     => $this->cell->getContentFormat(),
                'settings'          => $this->getSettings(),
                'pre'               => $this->getJSMethodsBeforeLoading(),
                'post'              => $this->getJSMethodsAfterLoading(),
            ]
        );
    }

    /**
     * @return void
     * @API
     */
    public function omitSorting(): void
    {
        $this->sort = false;
    }

    private array $columnValidations = [];

    private function createColumnProxies(string $nonce)
    {
        $serverTimeZone = Settings::getServerTimeZone();
        $clientTimeZone = Session::getClientTimeZone();
        foreach ($this->columns as $column) {
            $columnAccessType = $column->getAccessType();
            if ($columnAccessType > AccessType::NONE) {
                // TODO: check if this needs to be called here and in getColumnDefinition.
                $column->setLocaleBaseToken($this->cell->createLocaleBaseToken('Cell').'.Grid.');
                $events = $column->getEvents();
                $column->getEncryptedName($nonce);

                $this->columnValidations[] = $column->getClientValidations();
                foreach ($events as $event) {
                    $actions = $event->getActionArray();
                    foreach ($actions as $action) {
                        $action->initActionInCell($this->cell);
                    }
                    $this->cell->setEventForInteractiveObject($column->encryptedName, $event);
                }

                if ($columnAccessType === AccessType::RW) {
                    if (($column instanceof Grid\Column\Checkbox) || ($column instanceof Grid\Column\Radio) || ($column instanceof Grid\Column\RadioRow)) {
                        $this->eventOnCheck = true;
                    } else {
                        $this->eventOnCellEdit = true;
                    }
                    if ($column instanceof Grid\Column\Tree) {
                        $this->treeCellEdit = true;
                    }
                }
                if ($columnAccessType === AccessType::RW || !empty($events)) {
                    // TODO: add validations to grid columns, replace 8th parameter by validations
                    $this->cell->setContentControlType($column->encryptedName, $column->getField(), $columnAccessType, $column->getDBColumnType(), $column->getType(), $column->getLabel(), [], $column->getDateFormat());
                }
                $this->numberOfColumns++;

                $this->columnData[$column->encryptedName]['className'] = $column->getClassName();

                $columnProxy = new ColumnProxy($column, $clientTimeZone, $serverTimeZone, $this->cell);
                if ($column instanceof RowSelector) {
                    $this->rowSelectorColumn = $column->encryptedName;
                }
                if ($this->eventOnLinkClick === false && $columnProxy->hasJavascriptLink()) {
                    $this->eventOnLinkClick = true;
                }
                $this->columnProxies[$column->encryptedName] = $columnProxy;
            }
        }
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return $this
     * @API
     * @session none
     */
    protected function setQuery(string $query, array $parameters = []): self
    {
        $this->query           = $query;
        $this->queryParameters = $parameters;
        return $this;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return $this
     * @API
     * @session none
     */
    protected function setFilterQuery(string $query, array $parameters = []): self
    {
        if ($this->filterValue !== null) {
            $this->filterQuery           = $query;
            $this->filterQueryParameters = $parameters;
        }
        return $this;
    }

    /**
     * @param object[] $data
     * @return $this
     * @API
     * @session none
     */
    protected function setData(array $data): self
    {
        $this->inputArray = $data;
        return $this;
    }

    /**
     * @param Node ...$nodes
     * @return $this
     * @API
     * @session none
     */
    protected function setNodes(Grid\Node ...$nodes): self
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
        return $this;
    }

    /**
     * @param Column ...$columns
     * @return $this
     * @throws Exception
     * @API
     * @session read getAccessType()
     */
    protected function setColumns(Column ...$columns): self
    {
        foreach ($columns as $column) {
            $column->setParentAccessType($this->getAccessType());
            $this->columns[] = $column;
        }
        return $this;
    }

    /**
     * TODO: refactor into column
     * current usage: pass an array with the same number of elements as visible columns.
     * possible values are <name> or '#rspan'
     * @param array $values
     * @param int $level
     * @return $this
     * @API
     * @session none
     */
    protected function setMultilineHeader(array $values, int $level = 1): self
    {
        foreach ($values as $value) {
            $this->multilineHeader[$level][] = $value;
        }
        return $this;
    }

    /**
     * @param string $id
     * @return $this
     * @API
     * @session none
     */
    protected function setIdOfSelectedRow(string $id): self
    {
        $this->selectRowWithID = $id;
        return $this;
    }

    /**
     * @return $this
     * @API
     * @session none
     */
    protected function dontSelectLastSelectedRow(): self
    {
        $this->selectLastSelected = false;
        return $this;
    }

    /**
     * @session write
     */
    private function getCellEvents(): array
    {
        foreach ($this->getEvents() as $event) {
            if ($event instanceof OnDrop) {
                $this->eventOnDrop = true;
            }
            $this->cell->registerContentEvent($event);
        }
        $cellEvents = $this->getParentEventsForClient();
        if ($this->eventOnCellEdit === true) {
            $cellEvents['onEditCell'][] = 'doOnCellEdit';
        }
        if ($this->eventOnCheck === true) {
            $cellEvents['onCheck'][] = 'doOnCheck';
        }
        if ($this->eventOnLinkClick === true) {
            $cellEvents['onLinkClick'][] = 'doOnLinkClick';
        }
        return $cellEvents;
    }

    private function getSettings(): array
    {
        $cookieName = get_class($this).'_'.$this->viewVersion.'_'.$this->numberOfColumns;
        $settings   = [
            'gridStatsInCellHeader' => $this->gridStatsInCellHeader,
            'editLvl'               => $this->editLevel,
            'timestamp'             => $this->timestamp ?? date('YmdHis', time()),
            'cookieName'            => $cookieName,
            'locale'                => Session::getPrimaryLocale(),
            'i18n'                  => ['d' => ',', 'g' => '.']
        ];
        if ($this->rowSelectorColumn !== '') {
            $settings['rowSelector'] = $this->rowSelectorColumn;
        }
        return $settings;
    }

    private function getJSMethodsBeforeLoading(): array
    {
        if (!empty(array_filter($this->columnValidations))) {
            $methods['setColValidators'] = $this->columnValidations;
        }
        $methods['enableRowspan']     = $this->rowSpan;
        $methods['enableMultiline']   = $this->multiline;
        $methods['enableDragAndDrop'] = $this->eventOnDrop;
        $methods['setDragBehavior']   = $this->eventOnDrop ? 'sibling' : null;
        // TODO: check access type of tree column and cell
        $methods['enableTreeCellEdit'] = $this->treeCellEdit;
        return array_filter($methods);
    }

    private function getJSMethodsAfterLoading(): array
    {
        $methods = [];
        if ($this->smartRendering) {
            $methods['enableSmartRendering'] = true;
        }
        if ($this->preRendering > 0) {
            $methods['enablePreRendering'] = $this->preRendering;
        }
        if ($this->headerMenu) {
            $methods['enableHeaderMenu'] = '';
        }
        if ($this->columnMove) {
            $methods['enableColumnMove'] = true;
        }
        $cookieName           = get_class($this).'_'.$this->viewVersion.'_'.$this->numberOfColumns;
        $cookieExpirationDate = 'expires='.(new DateTime('now'))->modify('+10 years')->format('D, d M Y').' 23:00:00 GMT';
        $cookieParameters     = $cookieExpirationDate.';SameSite=Lax';
        if ($this->cookieOrderSaving) {
            $methods['loadOrderFromCookie'] = $cookieName;
            $methods['enableOrderSaving']   = [$cookieName, $cookieParameters];
        }
        if ($this->cookieSizeSaving) {
            $methods['loadSizeFromCookie']   = $cookieName;
            $methods['enableAutoSizeSaving'] = [$cookieName, $cookieParameters];
        }
        if ($this->cookieHiddenSaving) {
            $methods['loadHiddenColumnsFromCookie']   = $cookieName;
            $methods['enableAutoHiddenColumnsSaving'] = [$cookieName, $cookieParameters];
        }
        return $methods;
    }

    /**
     * @session none
     * @param Node $node
     */
    private function addNode(Grid\Node $node): void
    {
        if ($node->isVisible()) {
            $this->visibleLevels++;
            if ($node->isExpanded()) {
                $this->expandToLevel = $this->visibleLevels;
            }
        }
        if ($this->visibleLevels > 1) {
            $this->cellContentType = 'DHTMLXTreegrid';
        }
        $this->nodes[] = $node;
    }

    /**
     * @session none
     */
    private function queryData(): void
    {
        $this->timestamp = date('YmdHis', time());
        if (empty($this->inputArray) && isset($this->query) && !empty($this->nodes)) {
            if (isset($this->filterQuery)) {
                $this->inputArray = Database::getArray($this->filterQuery);
            } else {
                $this->inputArray = Database::getArray($this->query);
            }
        }
    }

    /**
     * @session none
     */
    private function sortArray(): void
    {
        // no need to sort without data or nodes
        if (empty($this->inputArray) || empty($this->nodes)) {
            return;
        }

        $nodes  = [];
        $sortBy = [];
        foreach ($this->nodes as $node) {
            if ($node->isVisible()) {
                $nodes[]  = [
                    'd' => $node->getSortDirection(),
                    't' => $node->getSortType()
                ];
                $sortBy[] = $node->getSortField();
            }
        }

        // generate arrays for array_multisort
        $sort = [];
        foreach ($this->inputArray as $key => $row) {
            foreach ($sortBy as $nodeIdx => $node) {
                if (isset($row->{$node}) || property_exists($row, $node)) {
                    $sort[$nodeIdx][$key] = is_string($row->{$node}) ? strtolower($row->{$node}) : (is_int($row->{$node}) ? $row->{$node} : '');
                }
            }
        }

        // generate arguments for array_multisort
        $args = [];
        foreach ($nodes as $nodeIdx => $node) {
            $args[] = &$sort[$nodeIdx];
            $args[] = $node['d'];
            $args[] = $node['t'];
        }
        $args[] = &$this->inputArray;
        array_multisort(...$args);
    }

    /**
     * @session none
     */
    private function determineEditLevel(): void
    {
        $level = 1;
        foreach ($this->nodes as $node) {
            if ($node->isVisible() === true) {
                if ($node->getAccessType() === AccessType::RW) {
                    $this->editLevel = $level;
                }
                $level++;
            }
        }
    }

    /**
     * @session none
     */
    private function buildFlatGrid(string $nonce)
    {
        if (count($this->columnProxies) > 0) {
            $rowIdPart     = [];
            $rowAccessType = 0;
            $rowType       = '';
            foreach ($this->nodes as $node) {
                if ($node->isVisible()) {
                    $rowType       = $node->getFieldToDisplayInClient();
                    $rowAccessType = $node->getAccessType();
                }
                if ($node->includeIdInRowId()) {
                    $rowIdPart[] = $node->getIdField();
                }
            }
            $localeCache = [];
            foreach ($this->inputArray as $key => $val) {
                // safe memory, unset rows as they're processed
                unset($this->inputArray[$key]);

                // generate Row ID
                $rowIdArray = [];
                foreach ($rowIdPart as $rowIdIndex) {
                    $rowIdArray[$rowIdIndex] = $val->{$rowIdIndex};
                }
                $rowIdObject = new RowID($rowIdArray);
                $rowId       = $rowIdObject->getEncodedRowId();

                // set technical values
                $this->outputArray[$rowId]['row'] = [
                    'level'      => 1,
                    'node'       => $rowType,
                    'accessType' => isset($val->BSRowAccessType) ? min($rowAccessType, (int)$val->BSRowAccessType) : $rowAccessType,
                    'attr'       => [
                        'id' => $rowIdObject->getEncryptedRowId($nonce)
                    ],
                    'usr'        => []
                ];
                if (isset($val->Style) && !empty($val->Style)) {
                    $this->outputArray[$rowId]['row']['attr']['style'] = $val->Style;
                }

                // set content values
                foreach ($this->columnProxies as $columnProxy) {
                    $this->outputArray[$rowId][$columnProxy->encryptedName] = $columnProxy->getValue($val, $this->outputArray[$rowId]['row']['attr']['id'], $rowType, $localeCache, $this->outputArray[$rowId]['row']['accessType']);
                }
            }
        }
    }

    /**
     * @session none
     */
    private function buildTreeGrid(string $nonce)
    {
        //TODO: support functionality for parent displaying child data, parent displaying parent data and parent displaying aggregated child data
        if (count($this->columns) > 0 && !empty($this->nodes)) {
            $previousId = [];
            $nodes      = [];
            foreach ($this->nodes as $nodeIndex => $node) {
                $previousId[$nodeIndex] = null;
                $nodes[$nodeIndex]      = [
                    'visible'    => $node->isVisible(),
                    'id'         => $node->getIdField(),
                    'field'      => $node->getFieldToDisplayInClient(),
                    'accessType' => $node->getAccessType(),
                    'useId'      => $node->includeIdInRowId()
                ];
            }
            $localeCache = [];
            foreach ($this->inputArray as $key => $val) {
                unset($this->inputArray[$key]);
                $cryptoRowId  = [];
                $currentLevel = 1;
                foreach ($nodes as $nodeIndex => $node) {
                    if ($node['visible'] === true && $val->{$node['id']} !== null) {
                        $cryptoRowId[$node['id']] = $val->{$node['id']};
                        if ($previousId[$nodeIndex] !== $val->{$node['id']}) {
                            $rowIdObject = new RowID($cryptoRowId);
                            $rowId       = $rowIdObject->getEncodedRowId();

                            // set technical values
                            $this->outputArray[$rowId]['row'] = [
                                'level'      => $currentLevel,
                                'node'       => $node['field'],
                                'accessType' => $node['accessType'],
                                'attr'       => [
                                    'id' => $rowIdObject->getEncryptedRowId($nonce)
                                ],
                                'usr'        => []
                            ];
                            if (isset($val->Style) && !empty($val->Style)) {
                                $this->outputArray[$rowId]['row']['attr']['style'] = $val->Style;
                            }

                            // expand grid if desired
                            if ($this->expandToLevel > $currentLevel) {
                                $this->outputArray[$rowId]['row']['attr']['open'] = '1';
                            }

                            // set content values
                            foreach ($this->columnProxies as $columnProxy) {
                                $this->outputArray[$rowId][$columnProxy->encryptedName] = $columnProxy->getValue($val, $this->outputArray[$rowId]['row']['attr']['id'], $node['field'], $localeCache, $this->outputArray[$rowId]['row']['accessType']);
                            }
                            $previousId[$nodeIndex] = $val->{$node['id']};
                        }
                        $currentLevel++;
                    } elseif ($node['useId'] === true && $val->{$node['id']} !== null) {
                        $cryptoRowId[$node['id']] = $val->{$node['id']};
                    }
                }
            }
        }
    }

    /**
     * @session none
     */
    private function selectLastSelectedRow()
    {
        if ($this->selectLastSelected === true && $this->selectedID instanceof \byteShard\ID\ID) {
            $ids = $this->selectedID->getIds();
            if (!empty($ids)) {
                $rowId  = new RowID($ids);
                $select = $rowId->getEncodedRowId();
                if (array_key_exists($select, $this->outputArray)) {
                    $this->outputArray[$select]['row']['attr']['select'] = '1';
                    $this->openTreeWithSelectedChildren();
                }
            }
        }
    }

    private function openTreeWithSelectedChildren()
    {
        // rowId json is generated in the order of nodes, create json for each level but the last one
        $nodeCount = count($this->nodes) - 1;
        if ($nodeCount > 0) {
            $rowId = [];
            for ($index = 0; $index < $nodeCount; $index++) {
                $idField = $this->nodes[$index]->getIdField();
                if (property_exists($this->selectedID, $idField)) {
                    $rowId[$idField] = $this->selectedID->{$idField};
                    $row             = json_encode($rowId);
                    if (isset($this->outputArray[$row])) {
                        $this->outputArray[$row]['row']['attr']['open'] = '1';
                    }
                }
            }
        }
    }

    /**
     * creates and return xml for DHTMLX Grid
     * @session read getColumnDefinition
     * @param bool $init
     * @return string
     * @throws \Exception
     */
    private function getXML(bool $init = true): string
    {
        SimpleXML::initializeDecode();
        $this->outputXml = new SimpleXMLElement('<?xml version="1.0" encoding="'.$this->getOutputCharset().'" ?><rows/>');

        $this->columnDefinition = [];
        foreach ($this->columnProxies as $column) {
            $this->columnDefinition[$column->encryptedName] = $column->getColumnDefinition();
        }
        if ($init) {
            $this->buildHeaderAsXML();
        }
        $this->attachUserDataAsXML();
        $this->buildContentAsXML();
        return SimpleXML::asString($this->outputXml);
    }

    /*
     * Appends head to outputXml
     * @session none
     */
    private function buildHeaderAsXML(): void
    {
        $header  = SimpleXML::addChild($this->outputXml, 'head');
        $filters = [];
        // add column definition to xml
        foreach ($this->columnDefinition as $column) {
            $col = SimpleXML::addChild($header, 'column', $column['label']);
            // add defined attributes to the column
            foreach ($column['attributes'] as $attributeName => $attributeValue) {
                SimpleXML::addAttribute($col, $attributeName, $attributeValue);
            }

            // Wenn als colType tree gefunden wird die Editierbarkeit anhand des accessType setzen
            /*
             * if ($attributeName == "type" && $attributeValue == "tree" && $column['accessType'] == 2){ $this->parameters['beforeInit']['enableTreeCellEdit']=true; }else{ $this->parameters['beforeInit']['enableTreeCellEdit']=false; }
             */

            // Spezialbehandlung der Comboboxen (coro oder dhtmlxCombo)
            switch ($column['attributes']['type']) {
                // Ohne Break da auch die combo Attribute zutreffen
                /** @noinspection PhpMissingBreakStatementInspection */
                case Grid\Enum\Type::COMBO_READONLY:
                    SimpleXML::addAttribute($col, 'editable', 'false');
                case Grid\Enum\Type::COMBO:
                    SimpleXML::addAttribute($col, 'xmlcontent', '1');
                    // @TODO: Alle Einträge anhängen
                    // Create a Combo XML and embed it in the GRID XML
                    if (isset($column['comboboxValues'])) {
                        if ($column['comboboxValues'] instanceof Combo) {
                            $column['comboboxValues']->getXMLElement($col, false);
                            /*$comboXML = new SimpleXMLElement($column['comboboxValues']->getXML());
                            //TODO: check if getXMLElement($col) doesn't create the same xml. This should perform better and unified, more simple code
                            $col->appendXML($comboXML, false);*/
                        } else {
                            $comboXML = new SimpleXMLElement(Combo::getXMLString($column['comboboxValues']));
                            SimpleXML::appendXML($col, $comboXML, false);
                        }
                    }
                    break;
                case Grid\Enum\Type::SELECT_READONLY:
                    // Gültige Combobox Werte für Spalte setzen
                    if (isset($column['comboboxValues'])) {
                        foreach ($column['comboboxValues'] as $optionIdx => $optionName) {
                            $option = SimpleXML::addChild($col, 'option', $optionName);
                            SimpleXML::addAttribute($option, 'value', $optionIdx);
                        }
                    }
                    break;
                /*case Grid\Column::CELLTYPE_checkbox:
                case Grid\Column::CELLTYPE_checkbox_readonly:
                   $option = $col->addChild('option', 'Nein');
                   $option->addAttribute('value', 0);
                   $option = $col->addChild('option', 'Ja');
                   $option->addAttribute('value', 1);
                   break;*/
            }
            // Array für Filter-Initialisierung zusammensetzen
            $filters[] = (isset($column['attributes']['filter'])) ? $column['attributes']['filter'] : '';
        }
        // ### beforeInit Abschnitt erstellen
        $beforeInit = SimpleXML::addChild($header, 'beforeInit');
        // Zusätzliche Header Zeilen
        if (count($this->multilineHeader) > 0) {
            foreach ($this->multilineHeader as $columns) {
                $call = SimpleXML::addChild($beforeInit, 'call');
                SimpleXML::addAttribute($call, 'command', 'attachHeader');
                SimpleXML::addChild($call, 'param', implode(',', $columns));
            }
        }
        // Filter: Wenn Filter in mindestens einer Spalte konfiguriert ist, die attachHeader Funktion ausführen
        if (strlen(implode(',', $filters)) > count($filters)) {
            $call = SimpleXML::addChild($beforeInit, 'call');
            SimpleXML::addAttribute($call, 'command', 'attachHeader');
            SimpleXML::addChild($call, 'param', implode(',', $filters));
        }

        // Sonstige Einstellungen
        $header?->addChild('settings')?->addChild('colwidth', 'px');
    }

    /**
     * Appends userdata to outputXml
     * @session none
     */
    private function attachUserDataAsXML(): void
    {
        // XLS-Spaltenbreiten auslesen und definieren
        $xlsWidth = [];
        foreach ($this->columnDefinition as $colID => $gridColumn) {
            $xlsWidth[$colID] = $gridColumn['attributes']['width_xls'];
        }
        $json = json_encode($xlsWidth);
        if ($json !== false) {
            $data = SimpleXML::addChild($this->outputXml, 'userdata', $json);
            $data?->addAttribute('name', 'xlsExportWidth');
        }

        /*
         * //Globale userData direkt an Ergebnis-Objekt hängen if(isset($this->arParameters['userData']) && is_arrayWC($this->arParameters['userData'])){ foreach($this->arParameters['userData'] as $name=>$value){ $data=$this->outputXml->addChild("userdata",(is_bool($value))?($value ? 'true' : 'false'):$value); $data->addAttribute("name",$name); } }
         */
    }

    /**
     * appends the grid content to outputXml
     * @session none
     */
    private function buildContentAsXML(): void
    {
        $lastRow = [];
        foreach ($this->outputArray as $rowData) {
            // Entsprechend dem Tree level die row unter das entsprechende Objekt hängen
            if ($rowData['row']['level'] === 1) {
                // Oberste Ebene
                $lastRow[$rowData['row']['level']] = $this->addContentRowXML($rowData, $this->outputXml);
            } else {
                // Alle Unterebenen
                $lastRow[$rowData['row']['level']] = $this->addContentRowXML($rowData, $lastRow[$rowData['row']['level'] - 1]);
            }
        }
    }

    /**
     * appends a row to the grid content
     * @session none
     * @param array $rowData content of the row to append
     * @param ?SimpleXMLElement $parentXMLObj parent object to append the row to
     */
    private function addContentRowXML(array $rowData, ?SimpleXMLElement $parentXMLObj): ?SimpleXMLElement
    {
        if ($parentXMLObj === null) {
            return null;
        }
        $row = $parentXMLObj->addChild('row');
        if ($row !== null) {
            foreach ($rowData['row']['attr'] as $attribute => $value) {
                // attributes: style, open, select
                SimpleXML::addAttribute($row, $attribute, $value);
            }

            // add userdata
            // optional: define export colors
            // past implementation: ['usr']['exportColor'] = 0
            foreach ($rowData['row']['usr'] as $name => $value) {
                $userData = SimpleXML::addChild($row, 'userdata', $value);
                SimpleXML::addAttribute($userData, 'name', $name);
            }

            foreach ($this->columnDefinition as $columnId => $column) {
                // loop over each cell
                $cell = SimpleXML::addChild($row, 'cell', $rowData[$columnId]['value']);

                if ($cell !== null) {
                    foreach ($rowData[$columnId]['attributes'] as $name => $value) {
                        SimpleXML::addAttribute($cell, $name, $value);
                    }
                }
            }
        }
        return $row;
    }

    public function getExportHandler(ExportHandler $exportHandler): ?HandlerInterface
    {
        return new Handler($exportHandler);
    }
}
