<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Event;
use byteShard\Internal\Event;

/**
 * Class OnPoll
 * @package byteShard\Grid\Event
 */
class OnPoll extends Event\GridEvent
{
    protected static string $event = 'onPoll';
}
