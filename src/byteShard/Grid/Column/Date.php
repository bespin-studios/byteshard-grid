<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\Grid\CalColumn;

/**
 * Class Date
 * @package byteShard\CellContent\Grid\Column
 */
class Date extends CalColumn
{
    public bool                  $convert_date      = true;
    protected string             $client_format     = '';
    protected string             $type              = 'date';
    protected string             $dhxTypeRw         = Grid\Enum\Type::TEXT_NOHTML;
    protected string             $dhxTypeRo         = Grid\Enum\Type::TEXT_NOHTML_READONLY;
    protected string             $sort              = Grid\Enum\Sort::DATETIME_GERMAN;
    protected string             $align             = Grid\Enum\Align::CENTER;
    protected string             $filter            = Grid\Enum\Filter::TEXT;
    protected int                $defaultWidth      = 120;
    protected Enum\DB\ColumnType $db_column_type    = Enum\DB\ColumnType::DATETIME2;
    protected string             $dbDateFormat      = 'Y-m-d H:i:s.u';
    protected string             $displayDateFormat = 'd.m.Y H:i:s';
    protected string             $dbTimezone        = 'UTC';
    protected string             $displayTimezone   = 'GMT+1';
    protected bool               $display_time      = false;

    /**
     * Date constructor.
     * @param string $dbField
     * @param ?string $name
     * @param null $width
     * @param int $accessType
     * @param string $displayDateFormat
     */
    public function __construct(string $dbField, ?string $name = null, $width = null, int $accessType = Enum\AccessType::R, string $displayDateFormat = '')
    {
        parent::__construct($dbField, $name, $width, $accessType);
        if ($displayDateFormat !== '') {
            $this->displayDateFormat = $displayDateFormat;
        }
    }

    /**
     * enables time, otherwise only date will be displayed
     */
    public function setEnableTime(): void
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        $this->display_time = true;
    }

    /**
     * disables time, only date will be displayed (default)
     */
    public function setDisableTime(): void
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        $this->display_time = false;
    }


    public function set_db_dateformat(string $format): self
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        $this->dbDateFormat = $format;
        return $this;
    }

    public function get_db_timezone(): string
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
        return $this->dbTimezone;
    }

    public function set_db_timezone(string $timezone): self
    {
        trigger_error(__METHOD__.': is deprecated.', E_USER_DEPRECATED);
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
