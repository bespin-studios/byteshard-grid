<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Enum;

/**
 * Class Filter
 * @package byteShard\Grid\Enum
 */
enum Filter: string
{
    case TEXT_ADVANCED = '#text_filter_adv';
    case TEXT = '#text_filter';
    case DATE = '#date_filter';
    case CHECKBOX = '#checkbox_filter';
    case COMBO_ADVANCED = '#chkcombo_filter';
    case NUMERIC = '#numeric_filter';
    case LINK = '#link_filter';
    case TRISTATE = '#tricheckbox_filter';
}
