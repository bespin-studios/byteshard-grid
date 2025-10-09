<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Enum\Access;
use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class Number
 * @package byteShard\CellContent\Grid\Column
 */
class Number extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::NUMERIC;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::NUMERIC_READONLY;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::INTEGER;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::RIGHT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::NUMERIC;
    protected int              $width     = 100;

    /**
     * Number constructor.
     * @param string $id
     * @param string|null $label
     * @param int|null $width
     * @param int|Access $accessType
     * @param string|null $dataBinding
     */
    public function __construct(string $id, ?string $label = null, ?int $width = null, int|Enum\Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        parent::__construct(id: $id, label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
    }
}
