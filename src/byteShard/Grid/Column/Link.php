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
 * Class Link
 * @package byteShard\CellContent\Grid\Column
 */
class Link extends Column
{
    protected Grid\Enum\Type   $dhxTypeRw = Grid\Enum\Type::LINK;
    protected Grid\Enum\Type   $dhxTypeRo = Grid\Enum\Type::LINK;
    protected Grid\Enum\Sort   $sort      = Grid\Enum\Sort::STRING;
    protected Grid\Enum\Align  $align     = Grid\Enum\Align::LEFT;
    protected Grid\Enum\Filter $filter    = Grid\Enum\Filter::TEXT;
    protected int              $width     = 100;
    private Enum\LinkTarget    $target    = Enum\LinkTarget::BLANK;
    private string             $url;
    private string             $tooltip;
    /** @var Grid\Cell\LinkMap[] */
    private array $linkMap = [];

    /**
     * @API
     */
    public function setTarget(Enum\LinkTarget $target): self
    {
        $this->target = $target;
        return $this;
    }

    public function getTarget(): string
    {
        return $this->target->value;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url ?? '';
    }

    public function setTooltip(string $tooltip): self
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function getTooltip(): string
    {
        return $this->tooltip ?? '';
    }

    /**
     * @API
     */
    public function setLinkMap(Grid\Cell\LinkMap ...$linkMaps): self
    {
        foreach ($linkMaps as $linkMap) {
            if (!array_key_exists($linkMap->getValue(), $this->linkMap)) {
                $this->linkMap[$linkMap->getValue()] = $linkMap;
            }
        }
        return $this;
    }

    public function getLinkMaps(): array
    {
        $linkMaps = [];
        foreach ($this->linkMap as $value => $map) {
            $linkMaps[$value] = [
                'value'   => $value,
                'js'      => !empty($map->getEvents()),
                'url'     => $map->getUrl(),
                'tooltip' => $map->getTooltip(),
                'target'  => $map->getTarget()
            ];
        }
        return $linkMaps;
    }
}
