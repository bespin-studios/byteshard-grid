<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Event;
use byteShard\Internal\Event;

/**
 * Class OnSelect
 * @package byteShard\Grid\Event
 */
class OnSelect extends Event\GridEvent implements Event\EventMigrationInterface
{
    protected static string $event = 'onSelect';

    public function getClientArray(string $cellNonce): array
    {
        return ['onRowSelect' => ['doOnRowSelect']];
    }
}
