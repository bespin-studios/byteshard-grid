<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

interface IDReference
{
    public function getIDReferences(): array;

    public function getValueForId(null|array|object|string $id): string;
}
