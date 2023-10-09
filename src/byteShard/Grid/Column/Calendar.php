<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum\DB\ColumnType;
use byteShard\Grid;
use byteShard\Internal\Grid\CalColumn;

/**
 * Class Calendar
 * @package byteShard\CellContent\Grid\Column
 */
class Calendar extends CalColumn
{
    public bool          $convert_date      = true;
    protected string     $type              = 'calendar';
    protected string     $dhxTypeRw         = Grid\Enum\Type::CALENDAR;
    protected string     $dhxTypeRo         = Grid\Enum\Type::DATERO;
    protected string     $sort              = Grid\Enum\Sort::DATE_GERMAN;
    protected string     $align             = Grid\Enum\Align::CENTER;
    protected string     $filter            = Grid\Enum\Filter::TEXT_ADVANCED;//'#date_filter';
    protected int        $width             = 100;
    protected string     $client_format     = '';
    protected string     $dbDateFormat      = 'Y-m-d H:i:s.u';
    protected string     $displayDateFormat = 'd.m.Y';
    protected string     $dbTimezone        = 'UTC';
    protected string     $displayTimezone   = 'GMT+1';
    protected bool       $display_time      = false;
    protected ColumnType $db_column_type    = ColumnType::DATETIME2;

    public function setEnableTime(): void
    {
        trigger_error(__METHOD__.': is deprecated. Please refactor and use setDisplayTime() instead', E_USER_DEPRECATED);
        $this->setDisplayTime();
    }

    public function setDisableTime(): void
    {
        trigger_error(__METHOD__.': is deprecated. Please refactor and use setDisplayTime(false) instead', E_USER_DEPRECATED);
        $this->setDisplayTime(false);
    }

    public function set_db_dateformat(string $format): self
    {
        trigger_error(__METHOD__.': is deprecated. Please refactor and use setServerDateFormat() instead', E_USER_DEPRECATED);
        $this->dbDateFormat = $format;
        return $this;
    }

    public function get_db_timezone(): string
    {
        trigger_error(__METHOD__.': is deprecated. Please refactor and use getServerDateFormat() instead', E_USER_DEPRECATED);
        return $this->dbTimezone;
    }

    public function set_db_timezone(string $timezone): self
    {
        trigger_error(__METHOD__.': is deprecated. Please refactor and use setServerTimeZone(\DateTimeZone $timeZone) instead', E_USER_DEPRECATED);
        $this->dbTimezone = $timezone;
        return $this;
    }

    public function get_display_timezone(): string
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        return $this->displayTimezone;
    }

    public function set_display_timezone(string $timezone): self
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        $this->displayTimezone = $timezone;
        return $this;
    }

    public function get_db_format(): string
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        return $this->dbDateFormat;
    }

    public function get_display_format(): string
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        return $this->displayDateFormat;
    }
}
