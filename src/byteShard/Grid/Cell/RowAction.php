<?php

namespace byteShard\Grid\Cell;

use byteShard\Internal\Grid\RowActionInterface;

class RowAction implements RowActionInterface
{
    public function __construct(
        private readonly string  $id,
        private readonly string  $label,
        private readonly ?string $icon       = null,
        private readonly ?string $showWhen   = null,
        private readonly ?string $activeWhen = null,
    ) {}

    public function getAction(): array
    {
        return array_filter([
            'id'         => $this->id,
            'label'      => $this->label,
            'icon'       => $this->icon,
            'showWhen'   => $this->showWhen,
            'activeWhen' => $this->activeWhen,
        ], fn($v) => $v !== null);
    }
}