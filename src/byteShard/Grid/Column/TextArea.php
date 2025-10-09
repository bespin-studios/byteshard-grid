<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class TextArea
 * @package byteShard\CellContent\Grid\Column
 */
class TextArea extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::TEXT_MULTILINE;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::TEXT_MULTILINE_READONLY;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int              $width     = 100;
}
