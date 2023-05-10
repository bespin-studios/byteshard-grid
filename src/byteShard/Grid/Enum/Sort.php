<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Enum;
use byteShard\Enum;

/**
 * Class Sort
 * @package byteShard\Grid\Enum
 */
final class Sort extends Enum\Enum
{
    const DATE_GERMAN     = 'sort_GERDate';
    const DATETIME_GERMAN = 'sort_GERDateTime';
    const STRING          = 'str';
    const INTEGER         = 'int';
    const IMAGE           = 'sort_img';
    const DISABLE         = 'na';
}
