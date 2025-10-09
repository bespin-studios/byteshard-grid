<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class Tree
 * @package byteShard\CellContent\Grid\Column
 */
class Tree extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::TREE;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::TREE;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int              $width     = 100;

    public function __construct(?string $label = null, ?int $width = null, int $accessType = 1, ?string $dataBinding = null)
    {
        parent::__construct(id: 'TreeColumn', label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
    }
}
