<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid;

class Style
{
    /**
     * @param string $style
     * @param array<string, int|string> $rowIds
     */
    public function __construct(private string $style = '', private array $rowIds = [])
    {
    }

    public function setStyle(string $style): void
    {
        $this->style = $style;
    }

    public function addId(string $key, int|string $value): void
    {
        $this->rowIds[$key] = $value;
    }

    public function getIdValue(string $key): null|int|string
    {
        return $this->rowIds[$key] ?? null;
    }

    public function getStyle(): string
    {
        return $this->style;
    }
}
