<?php

namespace byteShard\Grid;

class CssClass
{
    /**
     * @param string $className
     * @param array<string, int|string> $rowIds
     */
    public function __construct(private string $className = '', private array $rowIds = [])
    {
    }

    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    public function addId(string $key, int|string $value): void
    {
        $this->rowIds[$key] = $value;
    }

    public function getIdValue(string $key): null|int|string
    {
        return $this->rowIds[$key] ?? null;
    }

    public function getClass(): string
    {
        return $this->className;
    }

}