<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class Tristate
 * @package byteShard\CellContent\Grid\Column
 */
class Tristate extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::CHECKBOX_TRISTATE;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::CHECKBOX_TRISTATE_READONLY;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::CENTER;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::TRISTATE;
    protected int              $width     = 100;
}
