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
 * Class Currency
 * @package byteShard\CellContent\Grid\Column
 */
class Currency extends Column
{
    protected string $type           = 'currency';
    protected string $dhxTypeRw      = Grid\Enum\Type::PRICE_EURO;
    protected string $dhxTypeRo      = Grid\Enum\Type::PRICE_EURO_READONLY;
    protected string $sort           = Grid\Enum\Sort::INTEGER;
    protected string $align          = Grid\Enum\Align::RIGHT;
    protected string $filter         = Grid\Enum\Filter::NUMERIC;
    protected int    $width          = 100;
    protected string $db_column_type = Enum\DB\ColumnType::VARCHAR;
    private string   $format         = '0,000.00';

    public function setCurrency(string $currency): self
    {
        $this->dhxTypeRo = $currency;
        $this->dhxTypeRw = $currency;
        return $this;
    }

    protected function getTypeSpecificAttributes(): array
    {
        return ['format' => $this->format];
    }
}
