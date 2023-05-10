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
    protected string $type           = 'text';
    protected string $dhxTypeRw      = Grid\Enum\Type::TEXT;
    protected string $dhxTypeRo      = Grid\Enum\Type::TEXT_READONLY;
    protected string $sort           = Grid\Enum\Sort::STRING;
    protected string $align          = Grid\Enum\Align::LEFT;
    protected string $filter         = Grid\Enum\Filter::TEXT_ADVANCED;
    protected int    $width          = 100;
    protected string $db_column_type = Enum\DB\ColumnType::VARCHAR;

    /**
     * Text constructor.
     */
    public function __construct(string $dbField, string $name = null, int $width = null, $accessType = Enum\AccessType::R)
    {
        parent::__construct($dbField, $name, $width, $accessType);
    }
}
