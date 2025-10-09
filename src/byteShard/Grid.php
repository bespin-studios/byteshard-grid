<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard;

use byteShard\Cell\Event\OnPoll;
use byteShard\Enum\AccessType;
use byteShard\Enum\ContentType;
use byteShard\Event\OnPollInterface;
use byteShard\Event\OnSelectInterface;
use byteShard\Grid\Column\RowSelector;
use byteShard\Grid\CssClass;
use byteShard\Grid\Event\OnDrop;
use byteShard\Grid\GridInterface;
use byteShard\Grid\Node;
use byteShard\Grid\Style;
use byteShard\ID\RowID;
use byteShard\Internal\CellContent;
use byteShard\Internal\Event\ImplicitEventInterface;
use byteShard\Internal\Export\Handler;
use byteShard\Internal\Export\HandlerInterface;
use byteShard\Internal\ExportHandler;
use byteShard\Internal\Grid\Column;
use byteShard\Internal\Grid\ColumnProxy;
use byteShard\Internal\Grid\Row;
use byteShard\Internal\SimpleXML;
use byteShard\Internal\Struct\ClientCell;
use byteShard\Internal\Struct\ClientCellEvent;
use byteShard\Internal\Struct\ClientCellProperties;
use byteShard\Internal\Struct\ClientData;
use byteShard\Internal\Struct\ClientDataInterface;
use byteShard\Internal\Struct\ContentComponent;
use byteShard\Internal\Struct\GetData;
use byteShard\Internal\Struct\ValidationFailed;
use byteShard\Popup\Message;
use DateTime;
use SimpleXMLElement;
use byteShard\Internal\Deeplink\Deeplink;


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

    protected ContentType $contentType = ContentType::DhtmlxGrid;

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
    /** @var array<string, Row> */
    private array $outputArray = [];
    private bool  $sort        = true;

    // Class internal variables for building output xml
    private SimpleXMLElement $outputXml;

    private array  $columnData         = [];
    private string $rowSelectorColumn  = '';
    private bool   $cellContentDefined = false;
    /**
     * @var Style[]
     */
    private array $rowStyles = [];
    /** @var array<CssClass> */
    private array   $rowClasses = [];
    private ?string $pollId     = null;


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
    private function setRequestTimestamp(): void
    {
        $this->cell->setRequestTimestamp();
    }

    protected function defineDataBinding(): array
    {
        return [];
    }

    private function processNodeDefinitions(): void
    {
        $this->processCellContentDefinitions();
        foreach ($this->nodes as $node) {
            $node->setParentAccessType($this->getAccessType());
        }
    }

    private function processCellContentDefinitions(): void
    {
        if ($this->cellContentDefined === false) {
            $this->defineCellContent();
            $this->cellContentDefined = true;
        }
    }

    public function getRows(array $data): array
    {
        $this->processCellContentDefinitions();
        $this->setData($data);
        $nonce = $this->cell->getNonce();
        // create column proxy
        $this->createColumnProxies($nonce);

        // create node proxy
        // TODO: create node proxy, create getters for all necessary properties and access them here
        // create publicly accessible properties in node proxy and only use the node proxy from here on
        $this->processNodeDefinitions();

        if (!empty($this->nodes) && count($this->columns) > 0) {
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
        }
        return $this->outputArray;
    }

    /**
     * @session write (setRequestTimestamp, storeCellEvents, Cell::setContentControlType)
     * @session read (Session::getDBTimeZone, Session::getClientTimeZone, Session::getDateTimeFormat)
     * @throws Exception
     * @internal
     */
    public function getCellContent(bool $resetNonce = true): ?ClientCell
    {
        parent::getCellContent($resetNonce);
        $this->setRequestTimestamp();
        $this->processCellContentDefinitions();
        if ($this->hasFallbackContent()) {
            return $this->getFallbackContent()->getCellContent(false);
        }
        $components = parent::getComponents();
        $data       = $this->defineDataBinding();
        if (!empty($data)) {
            $this->setData($data);
        }
        $nonce = $this->cell->getNonce();
        // create column proxy
        $this->createColumnProxies($nonce);

        $cellEvents = $this->getCellEvents();
        // create node proxy
        //TODO: create node proxy, create getters for all necessary properties and access them here
        // create publicly accessible properties in node proxy and only use the node proxy from here on
        $this->processNodeDefinitions();

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
        $pre             = $this->getJSMethodsBeforeLoading();
        $pre['settings'] = $this->getSettings();
        $pre['cn']       = base64_encode($nonce);
        $components[]    = new ContentComponent(
            type   : $this->contentType,
            content: $this->getXML(),
            events : $cellEvents,
            setup  : $pre,
            update : $this->getJSMethodsAfterLoading()
        );
        return new ClientCell(
            new ClientCellProperties(
                nonce     : $nonce,
                cellHeader: $this->getCellHeader(),
                pollId    : $this->pollId),
            ...$components,
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

    private function createColumnProxies(string $nonce): void
    {
        $serverTimeZone = Settings::getServerTimeZone();
        $clientTimeZone = Session::getClientTimeZone();
        $baseLocale     = $this->getScopeLocaleTokenBasedOnNamespace('Cell').'.Grid.';
        foreach ($this->columns as $column) {
            $columnAccessType = $column->getAccessType();
            if ($columnAccessType > AccessType::NONE) {
                // TODO: check if this needs to be called here and in getColumnDefinition.
                $column->setLocaleBaseToken($baseLocale);
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
                    $this->cell->setContentControlType($column->encryptedName, $column->getField(), $columnAccessType, $column->getType(), $column->getLabel(), $column->getDateFormat());
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
        $cellEvents = $this->addImplicitEvents($cellEvents);
        $result     = [];
        foreach ($cellEvents as $eventName => $events) {
            foreach ($events as $handler) {
                $result[] = new ClientCellEvent($eventName, $handler);
            }
        }
        if ($this->eventOnCellEdit === true) {
            $result[] = new ClientCellEvent('onEditCell', 'doOnCellEdit');
        }
        if ($this->eventOnCheck === true) {
            $result[] = new ClientCellEvent('onCheck', 'doOnCheck');
        }
        if ($this->eventOnLinkClick === true) {
            $result[] = new ClientCellEvent('onLinkClick', 'doOnLinkClick');
        }
        return $result;
    }

    private function addImplicitEvents(array $events): array
    {
        $interfaces = array_flip(class_implements($this));
        // remove events which have already been declared explicitly
        foreach ($this->getEvents() as $event) {
            if ($event instanceof ImplicitEventInterface) {
                $interface = $event->getImplicitInterfaceClass();
                if (array_key_exists($interface, $interfaces)) {
                    unset($interfaces[$interface]);
                }
            }
        }
        foreach ($interfaces as $interface) {
            switch ($interface) {
                case OnPollInterface::class:
                    $onPoll       = new OnPoll();
                    $pollEvent    = $onPoll->getClientArray($this->cell->getNonce());
                    $this->pollId = $pollEvent['onPoll'];
                    $events       = array_merge_recursive($events, $pollEvent);
                    break;
                case OnSelectInterface::class:
                    $onSelect = new Grid\Event\OnSelect();
                    $events   = array_merge_recursive($events, $onSelect->getClientArray($this->cell->getNonce()));
                    break;
            }
        }
        return $events;
    }

    private function getSettings(): array
    {
        $cookieName           = $this->getCookieName();
        $cookieExpirationDate = 'expires='.(new DateTime('now'))->modify('+10 years')->format('D, d M Y').' 23:00:00 GMT';
        $cookieParameters     = $cookieExpirationDate.';SameSite=Lax';
        $settings             = [
            'gridStatsInCellHeader' => $this->gridStatsInCellHeader,
            'editLvl'               => $this->editLevel,
            'timestamp'             => $this->timestamp ?? date('YmdHis', time()),
            'cookieName'            => $cookieName,
            'cookieParam'           => $cookieParameters,
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
        $cookie                        = Deeplink::getCookie();
        if ($cookie !== null && isset($cookie['cell'], $cookie['filter'])) {
            if ($cookie['cell'] === $this->cell->getLayoutCellId()) {
                $filter = [];
                foreach ($this->columns as $column) {
                    $filter[$column->encryptedName] = $cookie['filter'][$column->getId()] ?? '';
                }
                if (!empty($filter)) {
                    $methods['setColFilter'] = [
                        'cookieName' => $this->getCookieName(),
                        'filters'    => $filter,
                    ];
                }
                Deeplink::cleanupCookie();
            }
        }
        return array_filter($methods);
    }

    protected function getCookieName(): string
    {
        return get_class($this).'_'.$this->viewVersion.'_'.$this->numberOfColumns;
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
        if ($this->cookieOrderSaving) {
            $methods['loadOrderFromCookie'] = true;
            $methods['enableOrderSaving']   = true;
        }
        if ($this->cookieSizeSaving) {
            $methods['loadSizeFromCookie']   = true;
            $methods['enableAutoSizeSaving'] = true;
        }
        if ($this->cookieHiddenSaving) {
            $methods['loadHiddenColumnsFromCookie']   = true;
            $methods['enableAutoHiddenColumnsSaving'] = true;
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
            $this->contentType = ContentType::DhtmlxTreeGrid;
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

    public function setStyles(Style ...$styles): void
    {
        $this->rowStyles = $styles;
    }

    public function setCssClasses(CssClass ...$cssClasses): void
    {
        $this->rowClasses = $cssClasses;
    }

    /**
     * @param array<int, string> $rowIdPart
     * @return array<string, array<string, string>>
     */
    private function getRowAttributes(array $rowIdPart): array
    {
        $rowAttributes = [];
        foreach ($this->rowStyles as $style) {
            $rowIdArray = [];
            foreach ($rowIdPart as $rowIdIndex) {
                $rowIdArray[$rowIdIndex] = $style->getIdValue($rowIdIndex);
            }
            $rowIdObject                                             = new RowID($rowIdArray);
            $rowAttributes[$rowIdObject->getEncodedRowId()]['style'] = $style->getStyle();
        }
        foreach ($this->rowClasses as $class) {
            $rowIdArray = [];
            foreach ($rowIdPart as $rowIdIndex) {
                $rowIdArray[$rowIdIndex] = $class->getIdValue($rowIdIndex);
            }
            $rowIdObject                                             = new RowID($rowIdArray);
            $rowAttributes[$rowIdObject->getEncodedRowId()]['class'] = $class->getClass();
        }
        return $rowAttributes;
    }

    /**
     * @session none
     */
    private function buildFlatGrid(string $nonce): void
    {
        if (count($this->columnProxies) > 0) {
            $rowIdPart     = [];
            $rowAccessType = 0;
            $dataBinding   = '';
            foreach ($this->nodes as $node) {
                if ($node->isVisible()) {
                    $dataBinding   = $node->getFieldToDisplayInClient();
                    $rowAccessType = $node->getAccessType();
                }
                if ($node->includeIdInRowId()) {
                    $rowIdPart[] = $node->getIdField();
                }
            }
            $rowAttributes = $this->getRowAttributes($rowIdPart);
            $localeCache   = [];
            $treeColumn    = null;
            foreach ($this->columnProxies as $columnProxy) {
                if ($columnProxy->isTreeColumn()) {
                    $treeColumn = $columnProxy;
                }
            }
            foreach ($this->inputArray as $key => $val) {
                // save memory, unset rows as they're processed
                unset($this->inputArray[$key]);

                $row = new Row($rowIdPart, $nonce, $val, $this->columnProxies, $dataBinding, $rowAccessType, $localeCache, $rowAttributes, $treeColumn);

                $this->outputArray[$row->getEncodedRowId()] = $row;
            }
        }
    }

    /**
     * @session none
     */
    private function buildTreeGrid(string $nonce): void
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

            $treeColumn    = null;
            foreach ($this->columnProxies as $columnProxy) {
                if ($columnProxy->isTreeColumn()) {
                    $treeColumn = $columnProxy;
                }
            }

            //TODO: rowStyles
            foreach ($this->inputArray as $key => $val) {
                unset($this->inputArray[$key]);
                $cryptoRowId  = [];
                $currentLevel = 1;
                foreach ($nodes as $nodeIndex => $node) {
                    if ($node['visible'] === true && $val->{$node['id']} !== null) {
                        $cryptoRowId[$node['id']] = $val->{$node['id']};
                        if ($previousId[$nodeIndex] !== $val->{$node['id']}) {
                            $row = new Row($cryptoRowId, $nonce, $val, $this->columnProxies, $node['field'], $node['accessType'], $localeCache, [], $treeColumn, $currentLevel);
                            if ($this->expandToLevel > $currentLevel) {
                                $row->setExpanded();
                            }
                            $this->outputArray[$row->getEncodedRowId()] = $row;

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
    private function selectLastSelectedRow(): void
    {
        if ($this->selectLastSelected === true && $this->selectedID instanceof \byteShard\ID\ID) {
            $ids = $this->selectedID->getIds();
            if (!empty($ids)) {
                $rowId  = new RowID($ids);
                $select = $rowId->getEncodedRowId();
                if (isset($this->outputArray[$select])) {
                    $this->outputArray[$select]->setSelected();
                    $this->openTreeWithSelectedChildren();
                }
            }
        }
    }

    private function openTreeWithSelectedChildren(): void
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
                        $this->outputArray[$row]->setExpanded();
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
        $this->outputXml->addAttribute('total', count($this->outputArray));

        if ($init === true) {
            $this->buildHeaderAsXML();
        }
        $this->buildContentAsXML();
        return SimpleXML::asString($this->outputXml);
    }

    private function buildHeaderAsXML(): void
    {
        $header       = $this->outputXml->addChild('head');
        $filters      = [];
        $exportWidths = [];
        foreach ($this->columnProxies as $column) {
            $column->addColumnHeaderToXml($header);
            $filters[] = $column->getFilter();
            [$columnId, $exportWidth] = $column->getExportWidth();
            $exportWidths[$columnId] = $exportWidth;
        }
        $beforeInit = $header?->addChild('beforeInit');
        foreach ($this->multilineHeader as $multilineColumns) {
            $call = $beforeInit->addChild('call');
            $call->addAttribute('command', 'attachHeader');
            SimpleXML::addChild($call, 'param', implode(',', $multilineColumns), null, true);
        }
        $filterString = implode(',', $filters);
        if (strlen($filterString) > count($filters)) {
            $call = $beforeInit->addChild('call');
            $call->addAttribute('command', 'attachHeader');
            SimpleXML::addChild($call, 'param', $filterString, null, true);
        }
        $header?->addChild('settings')?->addChild('colwidth', 'px');
        $exportWidthJson = json_encode($exportWidths);
        if ($exportWidthJson !== false) {
            $data = SimpleXML::addChild($this->outputXml, 'userdata', $exportWidthJson, null, true);
            $data?->addAttribute('name', 'xlsExportWidth');
        }
    }

    /**
     * appends the grid content to outputXml
     * @session none
     */
    private function buildContentAsXML(): void
    {
        $lastRow = [];
        if ($this->visibleLevels === 1) {
            foreach ($this->outputArray as $row) {
                $row->addRowToXml($this->outputXml);
            }
        } else {
            foreach ($this->outputArray as $row) {
                $level = $row->getLevel();
                if ($level === 1) {
                    $lastRow[$level] = $row->addRowToXml($this->outputXml);
                } else {
                    $lastRow[$level] = $row->addRowToXml($lastRow[$level - 1]);
                }
            }
        }
    }

    public function getExportHandler(ExportHandler $exportHandler): ?HandlerInterface
    {
        return new Handler($exportHandler);
    }
}
