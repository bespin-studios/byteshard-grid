<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\Grid\CalColumn;

/**
 * Class DateDifference
 * @package byteShard\CellContent\Grid\Column
 */
class DateDifference extends CalColumn
{
    protected string             $type           = 'text';
    protected string             $dhxTypeRw      = Grid\Enum\Type::TEXT_READONLY;
    protected string             $dhxTypeRo      = Grid\Enum\Type::TEXT_READONLY;
    protected string             $sort           = Grid\Enum\Sort::STRING;
    protected string             $align          = Grid\Enum\Align::LEFT;
    protected string             $filter         = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int                $width          = 100;
    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::VARCHAR;

    /**
     * DateDifference constructor.
     * @param string $dbField1
     * @param string $dbField2
     * @param string|null $label
     * @param int|null $width
     * @param int $accessType
     * @param string|null $dataBinding
     */
    public function __construct(string $dbField1, string $dbField2, string $label = null, int $width = null, int $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        parent::__construct(id: $dbField1, label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
        $this->dateDifferenceColumn = true;
        $this->dateField1           = $dbField1;
        $this->dateField2           = $dbField2;
    }
}
