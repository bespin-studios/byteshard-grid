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
 * Class Tree
 * @package byteShard\CellContent\Grid\Column
 */
class Tree extends Column
{
    protected string             $type           = 'tree';
    protected string             $dhxTypeRw      = Grid\Enum\Type::TREE;
    protected string             $dhxTypeRo      = Grid\Enum\Type::TREE;
    protected string             $sort           = Grid\Enum\Sort::STRING;
    protected string             $align          = Grid\Enum\Align::LEFT;
    protected string             $filter         = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int                $width          = 100;
    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::VARCHAR;

    public function __construct(?string $label = null, ?int $width = null, int $accessType = 1, ?string $dataBinding = null)
    {
        parent::__construct(id: 'TreeColumn', label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
    }
}
