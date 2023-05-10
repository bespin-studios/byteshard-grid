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
 * Class Checkbox2
 * @package byteShard\CellContent\Grid\Column
 */
class Checkbox2 extends Column
{
    protected string $type           = 'checkbox';
    protected string $dhxTypeRw      = Grid\Enum\Type::CHECKBOX2;
    protected string $dhxTypeRo      = Grid\Enum\Type::CHECKBOX2_READONLY;
    protected string $sort           = Grid\Enum\Sort::STRING;
    protected string $align          = Grid\Enum\Align::CENTER;
    protected string $filter         = Grid\Enum\Filter::CHECKBOX;
    protected int    $width          = 100;
    protected string $db_column_type = Enum\DB\ColumnType::BOOLEAN;
}
