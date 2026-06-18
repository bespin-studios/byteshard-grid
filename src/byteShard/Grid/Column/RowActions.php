<?php

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Enum\Access;
use byteShard\Grid;
use byteShard\Internal\Grid\Column;
use byteShard\Internal\Grid\RowActionInterface;

class RowActions extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::ROW_ACTIONS;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::ROW_ACTIONS;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::DISABLE;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::NONE;
    protected int              $width     = 50;

    public function __construct(string $id, ?string $label = null, ?int $width = null, int|Access $accessType = Enum\AccessType::R, ?string $dataBinding = null)
    {
        parent::__construct($id, $label ?? '', $width, $accessType, $dataBinding);
    }

    /** @var RowActionInterface[] */
    private array $actions = [];

    public function addActions(RowActionInterface ...$actions): static
    {
        foreach ($actions as $action) {
            $this->actions[] = $action;
        }
        return $this;
    }

    public function getRowActions(): array
    {
        return array_map(fn(RowActionInterface $a) => $a->getAction(), $this->actions);
    }
}