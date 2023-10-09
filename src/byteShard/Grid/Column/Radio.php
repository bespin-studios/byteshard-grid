<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class Radio
 * @package byteShard\CellContent\Grid\Column
 */
class Radio extends Column
{
    protected string             $type           = 'radio';
    protected string             $dhxTypeRw      = Grid\Enum\Type::RADIO_COLUMN;
    protected string             $dhxTypeRo      = Grid\Enum\Type::RADIO_COLUMN_READONLY;
    protected string             $sort           = Grid\Enum\Sort::STRING;
    protected string             $align          = Grid\Enum\Align::CENTER;
    protected string             $filter         = Grid\Enum\Filter::CHECKBOX;
    protected int                $width          = 100;
    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::BOOL;
}
