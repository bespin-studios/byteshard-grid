<?php

namespace byteShard\Grid\Cell;

use byteShard\Internal\Grid\RowActionInterface;

class RowActionSeparator implements RowActionInterface
{
    public function getAction(): array
    {
        return ['separator' => true];
    }
}