<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Enum\Access;
use byteShard\Grid;
use byteShard\Internal\Grid\CalColumn;

/**
 * Class DateDifference
 * @package byteShard\CellContent\Grid\Column
 */
class DateDifference extends CalColumn
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::TEXT_READONLY;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::TEXT_READONLY;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int              $width     = 100;

    /**
     * DateDifference constructor.
     * @param string $dbField1
     * @param string $dbField2
     * @param string|null $label
     * @param int|null $width
     * @param int|Access $accessType
     * @param string|null $dataBinding
     */
    public function __construct(string $dbField1, string $dbField2, ?string $label = null, ?int $width = null, int|Enum\Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        parent::__construct(id: $dbField1, label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
        $this->dateDifferenceColumn = true;
        $this->dateField1           = $dbField1;
        $this->dateField2           = $dbField2;
    }
}
