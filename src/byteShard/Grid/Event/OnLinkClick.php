<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Event;
use byteShard\Internal\Event;

/**
 * Class OnLinkClick
 * @package byteShard\Grid\Event
 */
class OnLinkClick extends Event\GridEvent
{
    protected static string $event = 'onLinkClick';
}
