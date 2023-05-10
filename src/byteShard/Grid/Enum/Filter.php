<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Enum;
use byteShard\Enum;

/**
 * Class Filter
 * @package byteShard\Grid\Enum
 */
final class Filter extends Enum\Enum
{
    const TEXT_ADVANCED  = '#text_filter_adv';
    const TEXT           = '#text_filter';
    const DATE           = '#date_filter';
    const CHECKBOX       = '#checkbox_filter';
    const COMBO_ADVANCED = '#chkcombo_filter';
    const NUMERIC        = '#numeric_filter';
    const LINK           = '#link_filter';
    const TRISTATE       = '#tricheckbox_filter';
}
