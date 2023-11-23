<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

use byteShard\Enum;
use byteShard\Enum\Access;
use byteShard\Grid\Column\RowSelector;
use byteShard\Grid\Enum\Sort;
use byteShard\Internal\Event\Event;
use byteShard\Internal\Validation\Validation;
use byteShard\Locale;
use byteShard\Internal\Permission\PermissionImplementation;
use byteShard\Exception;
use byteShard\Session;

/**
 * Class Column
 * @package byteShard\Internal\Grid
 */
abstract class Column
{
    use PermissionImplementation;

    public string $encryptedName;
    public bool   $convert_date           = false;
    public bool   $jsLink                 = false;
    public bool   $htmlLink               = false;
    public bool   $dateDifferenceColumn   = false;
    public bool   $readOnlyKeyValueColumn = false;
    public bool   $getLocale              = false;
    public string $dateField1             = '';
    public string $dateField2             = '';

    protected ?string $name;

    protected string $type;
    protected string $dhxTypeRw;
    protected string $dhxTypeRo;
    protected string $filter;
    protected int    $width;
    protected int    $defaultWidth = 100;
    private int      $exportWidth;
    protected string $align;
    protected string $sort;
    protected int    $collapse     = 0;
    public bool      $multiline    = true;
    protected bool   $colspan      = false;

    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::VARCHAR;
    /** @var Event[] */
    private array $events = [];
    private string $localeBaseToken = '';
    private string $className       = '';
    private string $dataBinding;
    private string $id;

    /**
     * Column constructor.
     * @param string $id
     * @param null|string $label
     * @param null|int $width
     * @param int|Access $accessType
     * @param null|string $dataBinding if dataBinding is null, it will be mapped to the id
     */
    public function __construct(string $id, ?string $label = null, int $width = null, int|Enum\Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        $this->id            = $id;
        $this->encryptedName = Session::encrypt($id);
        $this->name          = $label;
        if ($width !== null) {
            $this->width = $width;
        }
        $this->dataBinding = $dataBinding ?? $id;
        $this->setAccessType($accessType);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDataBinding(): string
    {
        return $this->dataBinding;
    }

    /**
     * @param Enum\DB\ColumnType $enumDbColumnType
     * @return $this
     * @API
     */
    public function setDBColumnType(Enum\DB\ColumnType $enumDbColumnType): self
    {
        $this->db_column_type = $enumDbColumnType;
        return $this;
    }

    public function setSort(string $enumSort): self
    {
        if (Sort::is_enum($enumSort)) {
            $this->sort = $enumSort;
        }
        return $this;
    }

    /**
     * @param Event ...$events
     * @return $this
     */
    public function addEvents(Event ...$events): self
    {
        foreach ($events as $event) {
            if (!in_array($event, $this->events, true)) {
                $this->events[] = $event;
            }
        }
        return $this;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function setFilter(string $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * This will group the columns after this column
     * @link https://docs.dhtmlx.com/grid__grouping_columns.html
     * @param int $nrOfColumns
     * @return $this
     * @API
     */
    public function setCollapse(int $nrOfColumns): self
    {
        $this->collapse = $nrOfColumns;
        return $this;
    }

    /**
     * This method will merge the header with the previous column header
     * @param bool $bool
     * @return $this
     * @API
     */
    public function setColspan(bool $bool): self
    {
        $this->colspan = $bool;
        return $this;
    }

    public function setWidth(int $int): self
    {
        $this->width = $int;
        return $this;
    }

    /**
     * @API
     */
    public function setExportWidth(int $int): self
    {
        $this->exportWidth = $int;
        return $this;
    }

    public function setAlignment(string $enumAlignment): self
    {
        $this->align = $enumAlignment;
        return $this;
    }

    /**
     * @API
     */
    public function setMultiline(bool $bool = true): self
    {
        $this->multiline = $bool;
        return $this;
    }

    /**
     * @session read getAccessType()
     * @return array
     */
    public function getColumnContent(): array
    {
        $result['label']                   = $this->getLabel();
        $result['accessType']              = $this->getAccessType();
        $result['attributes']              = $this->getTypeSpecificAttributes();
        $result['attributes']['type']      = ($result['accessType'] === 2) ? $this->dhxTypeRw : $this->dhxTypeRo;
        $result['attributes']['typeRO']    = $this->dhxTypeRo;
        $result['attributes']['sort']      = $this->sort;
        $result['attributes']['align']     = $this->align;
        $result['attributes']['filter']    = $this->filter;
        $result['attributes']['width']     = $this->width ?? $this->defaultWidth;
        $result['attributes']['width_xls'] = $this->exportWidth ?? $result['attributes']['width'] / 5;
        $result['attributes']['masterchk'] = get_called_class() === RowSelector::class;
        $result['collapse']                = $this->collapse;
        $result['colspan']                 = $this->colspan;
        return $result;
    }

    protected function getTypeSpecificAttributes(): array
    {
        return [];
    }

    public function getField(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        if ($this->name === null) {
            $locale = Locale::getArray($this->localeBaseToken.'Column.'.$this->id.'.Label');
            if ($locale['found'] === true) {
                return $locale['locale'];
            }
        } else {
            return $this->name;
        }
        return '';
    }

    public function getType(): string
    {
        if ($this->getAccessType() === Enum\AccessType::RW) {
            return $this->dhxTypeRw;
        }
        return $this->dhxTypeRo;
    }

    /**
     * @session read getColumnContent()
     */
    public function getContents(): array
    {
        return $this->getColumnContent();
    }

    /**
     * set the locale path <CELL_NAME>.Cell.<CELL_ID>.Grid.
     * @param string $token
     * @internal
     */
    public function setLocaleBaseToken(string $token): void
    {
        $this->localeBaseToken = $token;
    }

    /**
     * @return string
     * @internal
     */
    public function getLocaleBaseToken(): string
    {
        return $this->localeBaseToken;
    }

    /**
     * @return string
     */
    public function getDBColumnType(): string
    {
        /*if ($this->db_column_type === '') {
            // TODO: get default db column type from appSettings instead of session
            trigger_error(__METHOD__.': empty db_column_type is deprecated.', E_USER_DEPRECATED);
            return $_SESSION[MAIN]->getDefaultDBColumnType('grid', $this->type);
        }*/
        return $this->db_column_type->value;
    }

    /**
     * @return string
     * @API
     */
    public function getDateTimeClientFormat(): string
    {
        if (isset($this->client_format)) {
            if ($this->client_format === '') {
                if (isset($this->display_time) && $this->display_time === true) {
                    $token = 'byteShard.date.grid.date_time.client';
                } else {
                    $token = 'byteShard.date.grid.date.client';
                }
                $locale = Locale::getArray($token);
                return $locale['raw'];
            } else {
                return $this->client_format;
            }
        } else {
            return 'Y-m-d H:i:s';
        }
    }

    /**
     * @API
     */
    public function getDateTimeDBFormat(): string
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        return $_SESSION[MAIN]->getDateTimeFormat($this->getDBColumnType());
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        if (isset($this->displayDateFormat) && $this->displayDateFormat !== '') {
            return $this->displayDateFormat;
        } else {
            return 'Y-m-d';
        }
    }

    /**
     * @API
     */
    public function setClassName(string $className): self
    {
        $this->className = $className;
        return $this;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @var Validation[]
     */
    private array $validations = [];

    /**
     * @API
     */
    public function addValidations(Validation ...$validations): void
    {
        foreach ($validations as $validation) {
            $this->validations[$validation->className()] = $validation;
        }
    }

    public function getValidations(): ?array
    {
        if (empty($this->validations)) {
            return null;
        }
        $mergeArray = [];
        foreach ($this->validations as $validation) {
            $mergeArray[] = $validation->getClientArray();
        }
        return array_merge_recursive(... $mergeArray);
    }

    public function getClientValidations(): ?string
    {
        if (empty($this->validations)) {
            return null;
        }
        $validations = [];
        foreach ($this->validations as $validation) {
            $validations[] = $validation->getClientValidation();
        }
        return implode(',', $validations);
    }

    public function getEncryptedName(string $cellNonce = ''): string
    {
        $encrypted['i'] = $this->id;
        $validations    = $this->getValidations();
        if ($validations !== null) {
            $encrypted['v'] = $validations;
        }
        $encrypted['a'] = $this->getAccessType();


        $gridColumnClass = $this::class;
        // abbreviate framework controls to keep object ids as short as possible
        if (str_starts_with($gridColumnClass, 'byteShard\\Grid\\Column\\')) {
            $gridColumnClass = '!g'.substr($gridColumnClass, 22);
        }
        $encrypted['t'] = $gridColumnClass;
        $encrypted['l'] = $this->getLabel();
        //$encrypted['c']
        $nonce               = substr(md5($cellNonce.$this->id), 0, 24);
        $this->encryptedName = Session::encrypt(json_encode($encrypted), $nonce);
        return $this->encryptedName;
    }
}
