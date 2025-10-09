<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

use BackedEnum;
use byteShard\Cell;
use byteShard\Combo;
use byteShard\Enum\AccessType;
use byteShard\Exception;
use byteShard\Grid\Column\Currency;
use byteShard\Grid\Column\Image;
use byteShard\Grid\Column\Link;
use byteShard\Grid\Column\RowSelector;
use byteShard\Grid\Column\Tree;
use byteShard\Grid\Enum\Filter;
use byteShard\Grid\Enum\Type;
use byteShard\Internal\SimpleXML;
use byteShard\Locale;
use byteShard\Utils\Strings;
use Closure;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;
use UnitEnum;

class ColumnProxy
{
    private const dateDifference = 1;
    private const valueCallback  = 2;
    private const link           = 3;
    private const linkMap        = 4;
    private const image          = 5;
    private const idReference    = 6;

    public string        $encryptedName;
    private int          $specialType                           = 0;
    private bool         $convertDate                           = false;
    private array        $idReferences                          = [];
    private array        $imageMap                              = [];
    private bool         $isLocaleToken;
    private bool         $javascriptLink                        = false;
    private string       $dbFormat;
    private string       $clientFormat;
    private DateTimeZone $dbTimezone;
    private DateTimeZone $clientTimezone;
    private string       $dateField1;
    private string       $dateField2;
    private bool         $treeColumn;
    private bool         $wrapText;
    private int          $span                                  = 0;
    private bool         $rowSpan                               = false;
    private array        $columnDefinition;
    private bool         $hideRowSelectorCheckboxOnReadOnlyRows = false;
    private bool         $rowSelectorIgnoresAccessType          = true;
    private string       $columnType;
    private string       $className;
    private string       $cellId;
    private string       $tooltip;
    private string       $url;
    private array        $linkMap                               = [];
    private string       $dataBinding;
    private string       $id;
    private Closure      $valueCallback;
    private bool         $cdata;

    // grid-wide settings
    private bool $wrapGridContents;

    public function __construct(Column $column, DateTimeZone $clientTimeZone, DateTimeZone $serverTimeZone, Cell $cell, bool $wrapGridContents = true)
    {
        $this->columnType       = $column::class;
        $this->treeColumn       = $column::class === Tree::class;
        $this->id               = $column->getId();
        $this->dataBinding      = $column->getDataBinding();
        $this->encryptedName    = $column->encryptedName;
        $this->wrapText         = $column->multiline;
        $this->dateField1       = $column->dateField1 ?? '';
        $this->dateField2       = $column->dateField2 ?? '';
        $this->isLocaleToken    = $column->valueIsLocaleToken();
        $this->cdata            = $column->isCdata();
        $this->wrapGridContents = $wrapGridContents;
        $this->className        = $column->getClassName();
        $this->cellId           = $cell->getNewId()?->getEncryptedCellIdForEvent() ?? '';

        $valueCallback = $column->getValueCallback();
        if ($valueCallback !== null) {
            $this->specialType   = self::valueCallback;
            $this->valueCallback = $valueCallback;
        }

        if ($column instanceof CalColumn) {
            $this->convertDate = true;
            // date format could be defined per column type, therefore we don't use a grid wide setting here
            $this->dbFormat       = $column->getServerDateFormat();
            $this->clientFormat   = $column->getClientDateFormat();
            $this->dbTimezone     = $column->getServerTimeZone() ?? $serverTimeZone;
            $this->clientTimezone = $column->getClientTimeZone() ?? $clientTimeZone;
        }

        // the order of setting specialType is important
        if ($column->dateDifferenceColumn === true) {
            $this->specialType = self::dateDifference;
        }
        if ($column instanceof IDReference && $column->getAccessType() === AccessType::R) {
            $this->specialType  = self::idReference;
            $this->idReferences = $column->getIDReferences();
        }

        if ($column instanceof Image) {
            $this->specialType = self::image;
            $this->imageMap    = $column->getImageMap($cell);
            foreach ($this->imageMap as $map) {
                if ($map['jsLink'] === true) {
                    $this->javascriptLink = true;
                }
            }
        }
        if ($column instanceof Link) {
            $this->linkMap = $column->getLinkMaps();
            if (empty($this->linkMap)) {
                $this->specialType = self::link;
                // either link maps or unmapped properties, not both at once
                if (!empty($column->getEvents())) {
                    $this->javascriptLink = true;
                } else {
                    $this->url = $column->getUrl().'^'.$column->getTarget();
                }
                $this->tooltip = $column->getTooltip();
            } else {
                $this->specialType = self::linkMap;
            }
        }

        $this->columnDefinition          = $column->getContentsForColumnProxy();
        $this->columnDefinition['label'] = Strings::purify($this->columnDefinition['label']);
        if ($this->columnDefinition['colspan'] === true) {
            $this->columnDefinition['label'] = '#cspan';
        } elseif ($this->columnDefinition['collapse'] > 0) {
            $this->columnDefinition['label'] = '{#collapse}'.($this->columnDefinition['collapse'] + 1).':'.$this->columnDefinition['label'];
        }
        $this->columnDefinition['attributes']['id'] = $this->encryptedName;
        if ($column instanceof CalColumn) {
            $this->columnDefinition['attributes']['format'] = '';
        }
        if ($column instanceof RowSelector) {
            if ($column->getReadonlyHidden() === true) {
                $this->hideRowSelectorCheckboxOnReadOnlyRows = true;
                $this->columnDefinition['typeRO']            = Type::CHECKBOX2_READONLY;
            }
            if ($column->getReadonlyHidden() === true || $column->isComplyToAccessType() === true) {
                $this->rowSelectorIgnoresAccessType = false;
            }
        }
    }

    public function getFilter(): string
    {
        return $this->columnDefinition['filter']->value;
    }

    public function getExportWidth(): array
    {
        return [$this->encryptedName, $this->columnDefinition['exportWidth']];
    }

    public function isTreeColumn(): bool
    {
        return $this->treeColumn;
    }

    public function setDataBinding(string $dataBinding): void
    {
        $this->dataBinding = $dataBinding;
    }

    public function hasJavascriptLink(): bool
    {
        return $this->javascriptLink;
    }

    public function hasRowSpan(): bool
    {
        return $this->rowSpan;
    }

    public function getCellValue(object $data, string $rowId, array &$localeCache, int $accessType): mixed
    {
        $value       = '';
        $dataBinding = $this->dataBinding;
        switch ($this->specialType) {
            case self::dateDifference:
                $field1 = $this->dateField1;
                $field2 = $this->dateField2;
                if (!empty($data->$field1) && !empty($data->$field2)) {
                    $value = $this->getDateDifference(
                        new DateTime($data->$field1, $this->dbTimezone),
                        new DateTime($data->$field2, $this->dbTimezone)
                    );
                }
                break;
            case self::idReference:
                $value = $this->idReferences[$data->$dataBinding] ?? '';
                break;
            case self::image:
                if (isset($data->$dataBinding)) {
                    $imageKey = $data->$dataBinding;
                    if (isset($this->imageMap[$imageKey])) {
                        $imageData      = $this->imageMap[$imageKey];
                        $value          = $imageData['value'];
                        $encryptedValue = $imageData['encryptedValue'] ?? '';
                        if ($imageData['jsLink'] === true) {
                            $value .= '^javascript:doOnGridLink("'.$this->cellId.'","'.$rowId.'","'.$this->encryptedName.'","'.$encryptedValue.'")^_self';
                        }
                    }
                }
                break;
            case self::link:
                $value = $data->$dataBinding ?? '';
                if ($this->convertDate === true) {
                    $value = $this->getDate($value);
                } elseif ($value instanceof DateTime) {
                    $value = $value->format($this->clientFormat);
                } else {
                    $value = Strings::purify($value);
                }
                if ($this->javascriptLink === true) {
                    $value = $value.'^'.$this->tooltip.'^javascript:doOnGridLink("'.$this->cellId.'","'.$rowId.'","'.$this->encryptedName.'")^_self';
                } else {
                    $value = $value.'^'.$this->tooltip.'^'.$this->url;
                }
                break;
            case self::linkMap:
                if (isset($data->$dataBinding)) {
                    $mapKey = $data->$dataBinding;
                    if (isset($this->linkMap[$mapKey])) {
                        $map = $this->linkMap[$mapKey];
                        if ($map['js'] === true) {
                            $value = $map['value'].'^'.$map['tooltip'].'^javascript:doOnGridLink("'.$this->cellId.'","'.$rowId.'","'.$this->encryptedName.'")^_self';
                        } else {
                            $value = $map['value'].'^'.$map['tooltip'].'^'.$map['url'].'^'.$map['target'];
                        }
                    }
                }
                break;
            case self::valueCallback:
                $value = ($this->valueCallback)($data->$dataBinding);
                break;
            default:
                if ($this->columnType === RowSelector::class) {
                    if ($this->hideRowSelectorCheckboxOnReadOnlyRows === true && $accessType < AccessType::READWRITE) {
                        $value = 3;
                    } else {
                        $value = $data->$dataBinding ?? 0;
                    }
                } else {
                    $value = $data->$dataBinding ?? '';
                    if ($this->convertDate === true) {
                        $value = $this->getDate($value);
                    } elseif ($value instanceof DateTime) {
                        $value = $value->format($this->clientFormat);
                    } elseif ($value instanceof BackedEnum) {
                        $value = $value->value;
                    } elseif ($value instanceof UnitEnum) {
                        $value = $value->name;
                    } elseif ($this->columnType === Currency::class) {
                        $value = number_format((float)$value, 2, '.', '');
                    } else {
                        $value = Strings::purify($value);
                    }
                }
                break;
        }
        if ($this->isLocaleToken === true) {
            // since the locale is probably a very limited scope, we cache it in a hashmap
            if (!isset($localeCache[$value])) {
                $localeCache[$value] = Locale::get($value);
            }
            $value = $localeCache[$value];
        }
        $colType = $this->columnDefinition['type'];
        if ($colType === Type::CHECKBOX || $colType === Type::CHECKBOX_READONLY) {
            if (!is_numeric($value) && !is_bool($value)) {
                $value = 0;
            }
        }
        return $value;
    }

    public function addColumnToXml(SimpleXMLElement $row, object $data, string $rowId, array &$localeCache, int $accessType): void
    {
        $cellValue = $this->getCellValue($data, $rowId, $localeCache, $accessType);
        if ($this->cdata === true) {
            $cell = SimpleXML::addChildCData($row, 'cell', $cellValue);
        } else {
            $cell = $row->addChild('cell', !empty($cellValue) ? htmlspecialchars(htmlspecialchars_decode($cellValue, 16), 16, 'UTF-8') : $cellValue);
        }
        if ($cell !== null) {
            $this->addColumnAttributesToXml($cell, $cellValue, $accessType, $data);
        }
    }

    private function addColumnAttributesToXml(SimpleXMLElement $cell, mixed $cellValue, int $accessType, object $data): void
    {
        // class
        $class = trim(($this->className !== '' ? htmlspecialchars_decode($this->className).' ' : '').($this->wrapGridContents && $this->wrapText ? 'noWrap' : ''), ' ');
        if ($class !== '') {
            $cell->addAttribute('class', $class);
        }

        // type
        if ($this->columnDefinition['type'] === Type::IMAGE && empty($cellValue)) {
            $cell->addAttribute('type', Type::TEXT_READONLY->value);
        } else if ($accessType === 1) {
            if ($this->columnType === RowSelector::class) {
                if ($this->rowSelectorIgnoresAccessType === false) {
                    $cell->addAttribute('type', $this->columnDefinition['typeRO']->value);
                }
            } elseif ($this->columnDefinition['type'] !== $this->columnDefinition['typeRO']) {
                $cell->addAttribute('type', $this->columnDefinition['typeRO']->value);
            }
        }

        // rowspan
        if (isset($data->{$this->dataBinding.'_SPAN'})) {
            $span = $data->{$this->dataBinding.'_SPAN'};
            if (is_numeric($span) && $span > 0) {
                $this->rowSpan = true;
                if ($this->span === 0) {
                    $cell->addAttribute('rowspan', $span);
                    $this->span = $span;
                }
                $this->span--;
            }
        }

        //TODO: colspan
        //TODO: style
    }

    public function addColumnHeaderToXml(SimpleXMLElement $header): void
    {
        $column = SimpleXML::addChild($header, 'column', $this->columnDefinition['label'], null, true);
        // add default attributes to the column
        $column->addAttribute('align', $this->columnDefinition['align']->value);
        $column->addAttribute('id', $this->encryptedName);
        $column->addAttribute('sort', $this->columnDefinition['sort']->value);
        $column->addAttribute('type', $this->columnDefinition['type']->value);
        $column->addAttribute('width', $this->columnDefinition['width']);

        // add additional attributes to the column
        $allowedAttributes = ['color' => true, 'format' => true];
        foreach ($this->columnDefinition['attributes'] as $attributeName => $attributeValue) {
            if (isset($allowedAttributes[$attributeName])) {
                SimpleXML::addAttribute($column, $attributeName, $attributeValue, null, true);
            }
        }

        // Wenn als colType tree gefunden wird die Editierbarkeit anhand des accessType setzen
        /*
         * if ($attributeName == "type" && $attributeValue == "tree" && $column['accessType'] == 2){ $this->parameters['beforeInit']['enableTreeCellEdit']=true; }else{ $this->parameters['beforeInit']['enableTreeCellEdit']=false; }
         */

        switch ($this->columnDefinition['type']) {
            // Ohne Break da auch die combo Attribute zutreffen
            /** @noinspection PhpMissingBreakStatementInspection */
            case Type::COMBO_READONLY:
                $column->addAttribute('editable', 'false');
            case Type::COMBO:
                $column->addAttribute('xmlcontent', '1');
                // @TODO: Alle Einträge anhängen
                // Create a Combo XML and embed it in the GRID XML
                if (isset($this->columnDefinition['comboboxValues'])) {
                    if ($this->columnDefinition['comboboxValues'] instanceof Combo) {
                        $this->columnDefinition['comboboxValues']->getXMLElement($column, false);
                        /*$comboXML = new SimpleXMLElement($column['comboboxValues']->getXML());
                        //TODO: check if getXMLElement($col) doesn't create the same xml. This should perform better and unified, more simple code
                        $col->appendXML($comboXML, false);*/
                    } else {
                        $comboXML = new SimpleXMLElement(Combo::getXMLString($this->columnDefinition['comboboxValues']));
                        SimpleXML::appendXML($column, $comboXML, false);
                    }
                }
                break;
            case Type::SELECT_READONLY:
                // Gültige Combobox Werte für Spalte setzen
                if (isset($this->columnDefinition['comboboxValues'])) {
                    foreach ($this->columnDefinition['comboboxValues'] as $optionIdx => $optionName) {
                        $option = SimpleXML::addChild($column, 'option', $optionName, null, true);
                        SimpleXML::addAttribute($option, 'value', $optionIdx, null, true);
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
    }

    // calculate the difference between two dates.
    function getDateDifference(DateTime $date1, DateTime $date2, array $weekdays = [1, 2, 3, 4, 5]): int
    {
        // same day, ignore all logic and return 0
        if ($date1->format('Y-m-d') === $date2->format('Y-m-d')) {
            return 0;
        }

        $startDate = ($date1 > $date2) ? $date2 : $date1;
        $endDate   = ($date1 > $date2) ? $date1 : $date2;
        $sign      = ($date1 > $date2) ? '-' : '';
        $days      = $startDate->diff($endDate)->days;

        if ($days < 7) {
            // iterate the date period between the start and end date, return the number of days which are defined in $weekdays
            $daysInWeek = 0;
            $startDay   = (int)$startDate->format('N');
            $endDay     = (int)$endDate->format('N');
            if ($endDay >= $startDay) {
                for ($i = $startDay + 1; $i <= $endDay; $i++) {
                    !in_array($i, $weekdays) ?: $daysInWeek++;
                }
            } else {
                for ($i = $startDay + 1; $i <= 7; $i++) {
                    !in_array($i, $weekdays) ?: $daysInWeek++;
                }
                for ($i = 1; $i <= $endDay; $i++) {
                    !in_array($i, $weekdays) ?: $daysInWeek++;
                }
            }
            return $sign === '-' ? -$daysInWeek : $daysInWeek;
        }

        // due to performance of long date intervals we calculate the days in the first and last week and add the total number of weeks
        $daysFirstAndLastWeek = 0;
        for ($i = (int)(clone $startDate)->modify('+1 day')->format('N'); $i <= 7; $i++) {
            !in_array($i, $weekdays) ?: $daysFirstAndLastWeek++;
        }
        for ($i = (int)$endDate->format('N'); $i >= 1; $i--) {
            !in_array($i, $weekdays) ?: $daysFirstAndLastWeek++;
        }
        // calculate remaining weeks
        $start     = (clone $startDate)->modify('next monday');
        $end       = $endDate->format('N') !== '1' ? (clone $endDate)->modify('previous monday') : $endDate;
        $fullWeeks = 0;
        if ($end > $start) {
            $fullWeeks = (int)$start->diff($end)->format('%a') / 7;
        }
        $total = $daysFirstAndLastWeek + ($fullWeeks * count($weekdays));
        return $sign === '-' ? -$total : $total;
    }

    /**
     * @throws Exception
     */
    private function getDate(string|DateTime|null $value): string
    {
        if ($value instanceof DateTime) {
            return $value->setTimezone($this->clientTimezone)->format($this->clientFormat);
        }
        if ($value !== '' && $value !== null) {
            $date = DateTime::createFromFormat($this->dbFormat, $value, $this->dbTimezone);
            if ($date === false) {
                throw new Exception(__METHOD__.': could not create DateTime::createFromFormat. Use column->setServerDateFormat() or App\Settings::getDateFormat($objectType). Value: '.$value.' - Format: '.$this->dbFormat.' - Column: '.$this->id);
            }
            return $date->setTimezone($this->clientTimezone)->format($this->clientFormat);
        }
        return '';
    }
}
