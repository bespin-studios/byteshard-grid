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
 * Class Number
 * @package byteShard\CellContent\Grid\Column
 */
class Number extends Column
{
    protected string $type           = Enum\DB\ColumnType::INT;
    protected string $dhxTypeRw      = Grid\Enum\Type::NUMERIC;
    protected string $dhxTypeRo      = Grid\Enum\Type::NUMERIC_READONLY;
    protected string $sort           = Grid\Enum\Sort::INTEGER;
    protected string $align          = Grid\Enum\Align::RIGHT;
    protected string $filter         = Grid\Enum\Filter::NUMERIC;
    protected int    $width          = 100;
    protected string $db_column_type = Enum\DB\ColumnType::INT;

    /**
     * Number constructor.
     * @param string $dbField
     * @param string|null $name
     * @param int|null $width
     * @param int $accessType
     */
    public function __construct(string $dbField, string $name = null, int $width = null, int $accessType = Enum\AccessType::R)
    {
        parent::__construct($dbField, $name, $width, $accessType);
    }
}
