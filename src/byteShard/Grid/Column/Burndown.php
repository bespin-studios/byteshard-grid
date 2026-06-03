<?php

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\Grid\Column;

class Burndown extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::BURNDOWN;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::BURNDOWN;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::NONE;
    protected int              $width     = 200;

    public function __construct(string $id, ?string $label = null, ?int $width = null, int|Enum\Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        parent::__construct(id: $id, label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
    }

    /**
     * @param int $budgetUsed
     * @param int $totalBudget
     * @param int $elapsed time if total duration is 12 months and 3 have already elapsed, use 3 and 12 or use days if total days is 100 and 15 have elapsed and so on...
     * @param int $duration
     * @param string $currencySymbol
     * @return string
     */
    public static function create(int $budgetUsed, int $totalBudget, int $elapsed, int $duration, string $currencySymbol = '€'): string
    {
        $array = [
            $budgetUsed, $totalBudget, $elapsed, $duration
        ];
        if ($currencySymbol !== '€') {
            $array[] = $currencySymbol;
        }
        return implode('/', $array);
    }
}