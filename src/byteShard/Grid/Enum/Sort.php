<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Enum;

/**
 * Class Sort
 * @package byteShard\Grid\Enum
 */
enum Sort: string
{
    case DATE_GERMAN = 'sort_GERDate';
    case DATETIME_GERMAN = 'sort_GERDateTime';
    case STRING = 'str';
    case INTEGER = 'int';
    case IMAGE = 'sort_img';
    case DISABLE = 'na';
}
