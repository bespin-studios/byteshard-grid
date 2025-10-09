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
 * Class Text
 * @package byteShard\CellContent\Grid\Column
 */
class Text extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::TEXT;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::TEXT_READONLY;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int              $width     = 100;

    /**
     * Text constructor.
     */
    public function __construct(string $id, ?string $label = null, ?int $width = null, int|Enum\Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        parent::__construct(id: $id, label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
    }
}
