<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Event;
use byteShard\Internal\Event;

/**
 * Class OnDoubleClick
 * @package byteShard\Grid\Event
 */
class OnDoubleClick extends Event\GridEvent implements Event\EventMigrationInterface
{
    protected static string $event = 'onDoubleClick';

    public function getClientArray(string $cellNonce): array
    {
        //TODO: Implement double click events
        return [];
    }
}
