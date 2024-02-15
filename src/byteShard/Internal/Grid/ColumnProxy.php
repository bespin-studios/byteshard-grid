<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

use byteShard\Cell;
use byteShard\Enum\AccessType;
use byteShard\Exception;
use byteShard\Grid\Column\Image;
use byteShard\Grid\Column\Link;
use byteShard\Grid\Column\RowSelector;
use byteShard\Grid\Column\Tree;
use byteShard\Grid\Enum\Type;
use byteShard\Locale;
use byteShard\Utils\Strings;
use DateTime;
use DateTimeZone;

class ColumnProxy
{
    public string        $encryptedName;
    private string       $specialType                           = '';
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
        $this->isLocaleToken    = $column->getLocale;
        $this->wrapGridContents = $wrapGridContents;
        $this->className        = $column->getClassName();
        $this->cellId           = $cell->getNewId()?->getEncryptedCellIdForEvent() ?? '';

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
            $this->specialType = 'dateDifference';
        }
        if ($column instanceof IDReference && $column->getAccessType() === AccessType::R) {
            $this->specialType  = 'idReference';
            $this->idReferences = $column->getIDReferences();
        }

        if ($column instanceof Image) {
            $this->specialType = 'image';
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
                $this->specialType = 'link';
                // either link maps or unmapped properties, not both at once
                if (!empty($column->getEvents())) {
                    $this->javascriptLink = true;
                } else {
                    $this->url = $column->getUrl().'^'.$column->getTarget();
                }
                $this->tooltip = $column->getTooltip();
            } else {
                $this->specialType = 'linkMap';
            }
        }

        $this->columnDefinition          = $column->getContents();
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
                $this->hideRowSelectorCheckboxOnReadOnlyRows    = true;
                $this->columnDefinition['attributes']['typeRO'] = Type::CHECKBOX2_READONLY;
            }
            if ($column->getReadonlyHidden() === true || $column->isComplyToAccessType() === true) {
                $this->rowSelectorIgnoresAccessType = false;
            }
        }
    }

    public function hasJavascriptLink(): bool
    {
        return $this->javascriptLink;
    }

    public function hasRowSpan(): bool
    {
        return $this->rowSpan;
    }

    public function getColumnDefinition(): array
    {
        return $this->columnDefinition;
    }

    public function getValue(object $data, string $rowId, string $rowType, array &$localeCache, int $accessType): array
    {
        if ($this->treeColumn) {
            $this->dataBinding = $rowType;
        }
        $value = '';
        switch ($this->specialType) {
            case 'dateDifference':
                if (isset($data->{$this->dateField1}, $data->{$this->dateField2}) && !empty($data->{$this->dateField1}) && !empty($data->{$this->dateField2})) {
                    $value = $this->getDateDifference(
                        new DateTime($data->{$this->dateField1}, $this->dbTimezone),
                        new DateTime($data->{$this->dateField2}, $this->dbTimezone)
                    );
                }
                break;
            case 'idReference':
                $value = $this->idReferences[$data->{$this->dataBinding}] ?? '';
                break;
            case 'image':
                if (isset($data->{$this->dataBinding}, $this->imageMap[$data->{$this->dataBinding}])) {
                    $value          = $this->imageMap[$data->{$this->dataBinding}]['value'];
                    $encryptedValue = $this->imageMap[$data->{$this->dataBinding}]['encryptedValue'] ?? '';
                    if ($this->imageMap[$data->{$this->dataBinding}]['jsLink'] === true) {
                        $value .= '^javascript:doOnGridLink("'.$this->cellId.'","'.$rowId.'","'.$this->encryptedName.'","'.$encryptedValue.'")^_self';
                    }
                }
                break;
            case 'link':
                $value = $data->{$this->dataBinding} ?? '';
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
            case 'linkMap':
                if (isset($data->{$this->dataBinding}, $this->linkMap[$data->{$this->dataBinding}])) {
                    $map = $this->linkMap[$data->{$this->dataBinding}];
                    if ($map['js'] === true) {
                        $value = $map['value'].'^'.$map['tooltip'].'^javascript:doOnGridLink("'.$this->cellId.'","'.$rowId.'","'.$this->encryptedName.'")^_self';
                    } else {
                        $value = $map['value'].'^'.$map['tooltip'].'^'.$map['url'].'^'.$map['target'];
                    }
                }
                break;
            default:
                if ($this->columnType === RowSelector::class) {
                    if ($this->hideRowSelectorCheckboxOnReadOnlyRows === true && $accessType < AccessType::READWRITE) {
                        $value = 3;
                    } else {
                        $value = $data->{$this->dataBinding} ?? 0;
                    }
                } else {
                    $value = $data->{$this->dataBinding} ?? '';
                    if ($this->convertDate === true) {
                        $value = $this->getDate($value);
                    } elseif ($value instanceof DateTime) {
                        $value = $value->format($this->clientFormat);
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
        $result = [
            'value'      => $value,
            'attributes' => []
        ];

        $cssClasses = [];
        if ($this->className !== '') {
            $cssClasses[] = $this->className;
        }
        if ($this->wrapGridContents && $this->wrapText) {
            $cssClasses[] = 'noWrap';
        }

        //TODO: past implementation
        /*
        $styles = [];
        if (isset($rowData[$columnId]['color']) && is_numeric($rowData[$columnId]['color'])) {
           $style[] = 'background-color:#'.$rowData[$columnId]['color'];
        }
        if (isset($rowData[$columnId]['nowrap'])) {
           $style[] = 'white-space:nowrap';
        }
        if (!empty($styles)) {
            $result['attributes']['style'] = implode(';', $styles).';';
        }*/

        if ($accessType === 1) {
            if ($this->columnType === RowSelector::class) {
                if ($this->rowSelectorIgnoresAccessType === false) {
                    $result['attributes']['type'] = $this->columnDefinition['attributes']['typeRO'];
                }
            } elseif ($this->columnDefinition['attributes']['type'] !== $this->columnDefinition['attributes']['typeRO']) {
                $result['attributes']['type'] = $this->columnDefinition['attributes']['typeRO'];
            }
        }
        switch ($this->columnDefinition['attributes']['type']) {
            case Type::IMAGE:
                if (empty($result['value'])) {
                    $result['attributes']['type'] = 'ro';
                }
                break;
            case Type::CHECKBOX:
            case Type::CHECKBOX_READONLY:
                // catch ### in checkbox columns
                if (!is_numeric($result['value']) && !is_bool($result['value'])) {
                    $result['value'] = 0;
                }
                break;
        }

        // row span implementation
        if (isset($data->{$this->dataBinding.'_SPAN'}) && $data->{$this->dataBinding.'_SPAN'} > 0) {
            $this->rowSpan = true;
            if ($this->span === 0) {
                $result['attributes']['rowspan'] = $data->{$this->dataBinding.'_SPAN'};
                $this->span                      = $data->{$this->dataBinding.'_SPAN'};
            }
            $this->span--;
        }

        if (!empty($cssClasses)) {
            $result['attributes']['class'] = implode(' ', $cssClasses);
        }

        return $result;
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
