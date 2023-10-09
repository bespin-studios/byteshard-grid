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
 * Class TextArea
 * @package byteShard\CellContent\Grid\Column
 */
class TextArea extends Column
{
    protected string             $type           = 'text_area';
    protected string             $dhxTypeRw      = Grid\Enum\Type::TEXT_MULTILINE;
    protected string             $dhxTypeRo      = Grid\Enum\Type::TEXT_MULTILINE_READONLY;
    protected string             $sort           = Grid\Enum\Sort::STRING;
    protected string             $align          = Grid\Enum\Align::LEFT;
    protected string             $filter         = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int                $width          = 100;
    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::VARCHAR;
}
