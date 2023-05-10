<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Cell;

use byteShard\Enum\AccessType;
use byteShard\Internal\Event\Event;
use byteShard\Internal\Permission\PermissionImplementation;

/**
 * Class Image
 * @package byteShard\Grid\Cell
 */
class Image
{
    use PermissionImplementation;

    /**
     * @var Event[]
     */
    public array   $events = [];
    public mixed   $value;
    public ?string $tooltip;
    public ?string $image;

    /**
     * Image constructor.
     * @param mixed $value
     * @param ?string $image
     * @param ?string $tooltip
     * @param Event ...$events
     */
    public function __construct(mixed $value, ?string $image = null, ?string $tooltip = null, Event ...$events)
    {
        $this->value   = $value;
        $this->image   = $image;
        $this->tooltip = $tooltip;
        $this->addEvents(...$events);
    }

    /**
     * @param Event ...$events
     * @return $this
     */
    public function addEvents(Event ...$events): self
    {
        foreach ($events as $event) {
            if (!in_array($event, $this->events, true)) {
                $this->events[] = $event;
            }
        }
        return $this;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        if ($this->getAccessType() === AccessType::RW && !empty($this->events)) {
            return $this->events;
        }
        return [];
    }
}
