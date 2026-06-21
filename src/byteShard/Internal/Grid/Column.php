<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

use byteShard\Enum;
use byteShard\Enum\Access;
use byteShard\Grid\Enum\Align;
use byteShard\Grid\Enum\Filter;
use byteShard\Grid\Enum\Sort;
use byteShard\Grid\Enum\Type;
use byteShard\Internal\Event\Event;
use byteShard\Internal\Validation\Validation;
use byteShard\Locale;
use byteShard\Internal\Permission\PermissionImplementation;
use byteShard\Session;
use Closure;

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
    private bool  $valueIsLocaleToken     = false;
    public string $dateField1             = '';
    public string $dateField2             = '';

    protected ?string $name;
    protected Type    $dhxTypeRw;
    protected Type    $dhxTypeRo;
    protected Filter  $filter;
    protected int     $width;
    protected int     $defaultWidth = 100;
    private int       $exportWidth;
    protected Align   $align;
    protected Sort    $sort;
    protected int     $collapse     = 0;
    public bool       $multiline    = true;
    protected bool    $colspan      = false;
    private bool      $cdata        = false;
    /** @var Event[] */
    private array   $events          = [];
    private string  $localeBaseToken = '';
    private string  $className       = '';
    private string  $dataBinding;
    private string  $id;
    private Closure $valueCallback;

    /**
     * Column constructor.
     * @param string $id
     * @param null|string $label
     * @param null|int $width
     * @param int|Access $accessType
     * @param null|string $dataBinding if dataBinding is null, it will be mapped to the id
     */
    public function __construct(string $id, ?string $label = null, ?int $width = null, int|Enum\Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
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

    public function translateValueLocaleToken(): self
    {
        $this->valueIsLocaleToken = true;
        return $this;
    }

    public function valueIsLocaleToken(): bool
    {
        return $this->valueIsLocaleToken;
    }

    public function setValueCallback(Closure $callback): self
    {
        $this->valueCallback = $callback;
        return $this;
    }

    public function getValueCallback(): ?Closure
    {
        return $this->valueCallback ?? null;
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
        trigger_error(__METHOD__.': is deprecated and has no more impact. Calls can be safely removed', E_USER_DEPRECATED);
        return $this;
    }

    public function setSort(Sort $enumSort): self
    {
        $this->sort = $enumSort;
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

    public function setFilter(Filter $filter): self
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

    public function setAlignment(Align $enumAlignment): self
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
        $result['label']               = $this->getLabel();
        $result['accessType']          = $this->getAccessType();
        $result['typeRO']              = $this->dhxTypeRo->value;
        $result['attributes']          = $this->getTypeSpecificAttributes();
        $result['attributes']['type']  = ($result['accessType'] === 2) ? $this->dhxTypeRw->value : $this->dhxTypeRo->value;
        $result['attributes']['sort']  = $this->sort->value;
        $result['attributes']['align'] = $this->align->value;
        $result['attributes']['width'] = $this->width ?? $this->defaultWidth;
        $result['collapse']            = $this->collapse;
        $result['colspan']             = $this->colspan;
        return $result;
    }

    public function getContentsForColumnProxy(): array
    {
        $accessType = $this->getAccessType();
        return [
            'label'       => $this->getLabel(),
            'accessType'  => $accessType,
            'typeRO'      => $this->dhxTypeRo,
            'attributes'  => $this->getTypeSpecificAttributes(),
            'type'        => $accessType === 2 ? $this->dhxTypeRw : $this->dhxTypeRo,
            'sort'        => $this->sort,
            'align'       => $this->align,
            'width'       => $this->width ?? $this->defaultWidth,
            'collapse'    => $this->collapse,
            'colspan'     => $this->colspan,
            'filter'      => $this->filter,
            'exportWidth' => $this->exportWidth ?? intdiv($this->width ?? $this->defaultWidth, 5),
        ];
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function getExportWidth(): int
    {
        if (isset($this->exportWidth)) {
            return $this->exportWidth;
        }
        if (isset($this->width)) {
            return intdiv($this->width, 5);
        }
        return intdiv($this->defaultWidth, 5);
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
            return $this->dhxTypeRw->value;
        }
        return $this->dhxTypeRo->value;
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
        trigger_error(__METHOD__.': is deprecated and has no more impact. Calls can be safely removed', E_USER_DEPRECATED);
        return '';
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
        trigger_error(__METHOD__.': is deprecated and has no more impact. Calls can be safely removed', E_USER_DEPRECATED);
        return '';
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
        $encrypted['i']      = $this->id;
        $nonce               = substr(md5($cellNonce.$this->id), 0, 24);
        $this->encryptedName = Session::encrypt(json_encode($encrypted), $nonce);
        return $this->encryptedName;
    }

    public function getObjectProperties(): array
    {
        $encrypted   = [];
        $validations = $this->getValidations();
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
        return $encrypted;
    }

    public function isCdata(): bool
    {
        return $this->cdata;
    }

    public function setCdata(bool $cdata): void
    {
        $this->cdata = $cdata;
    }

}
