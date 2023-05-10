<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Cell;

use byteShard\Enum\AccessType;
use byteShard\Enum\LinkTarget;
use byteShard\Grid\Event\OnLinkClick;
use byteShard\Internal\Permission\PermissionImplementation;

class LinkMap
{
    use PermissionImplementation;

    private array $events = [];

    public function __construct
    (
        private readonly string     $value,
        private readonly string     $url = '',
        private readonly string     $tooltip = '',
        private readonly LinkTarget $target = LinkTarget::SELF,
        OnLinkClick                 ...$events
    )
    {
        $this->addEvents(...$events);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTooltip(): string
    {
        return $this->tooltip;
    }

    public function getTarget(): string
    {
        return $this->target->value;
    }

    public function addEvents(OnLinkClick ...$events): self
    {
        foreach ($events as $event) {
            if (!in_array($event, $this->events, true)) {
                $this->events[] = $event;
            }
        }
        return $this;
    }

    public function getEvents(): array
    {
        if ($this->getAccessType() === AccessType::RW) {
            return $this->events;
        }
        return [];
    }
}