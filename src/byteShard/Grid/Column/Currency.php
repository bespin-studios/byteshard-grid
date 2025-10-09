<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class Currency
 * @package byteShard\CellContent\Grid\Column
 */
class Currency extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::PRICE_EURO;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::PRICE_EURO_READONLY;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::INTEGER;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::RIGHT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::NUMERIC;
    protected int              $width     = 100;
    private string             $format    = '0,000.00';

    public function setCurrency(Grid\Enum\Type $currency): self
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
