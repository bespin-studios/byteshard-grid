<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Enum;

use byteShard\Enum;

/**
 * Class Type
 * @package byteShard\Grid\Enum
 */
final class Type extends Enum\Enum
{
    const CALCULATOR                 = 'calck';
    const CALENDAR                   = 'dhxCalendar';
    const CALENDAR_MANUAL            = 'dhxCalendarA';
    const CHECKBOX                   = 'ch';
    const CHECKBOX_READONLY          = 'chro';
    const CHECKBOX2                  = 'chadv';
    const CHECKBOX2_READONLY         = 'chadvro';
    const CHECKBOX_TRISTATE          = 'trich';
    const CHECKBOX_TRISTATE_READONLY = 'trichro';
    const COLORPICKER                = 'cp';
    const COMBO                      = 'combo';
    const COMBO_READONLY             = 'comboro';
    const CONTEXT                    = 'context';
    const GRID                       = 'grid';
    const HIDDEN                     = 'hidden';
    const IMAGE                      = 'img';
    const LINK                       = 'blink';
    const NUMERIC                    = 'edn';
    const NUMERIC_READONLY           = 'ron';
    const PRICE                      = 'price';
    const PRICE_EURO                 = 'priceEur';
    const PRICE_EURO_READONLY        = 'priceEurro';
    const RADIO_COLUMN               = 'ra';
    const RADIO_COLUMN_READONLY      = 'raro';
    const RADIO_ROW                  = 'ra_str';
    const RADIO_ROW_READONLY         = 'raro_str';
    const SELECT                     = 'co';
    const SELECT_MULTI               = 'clist';
    const SELECT_READONLY            = 'coro';
    const TEXT                       = 'ed';
    const TEXT_MULTILINE             = 'txt';
    const TEXT_MULTILINE_NOHTML      = 'txttxt';
    const TEXT_MULTILINE_READONLY    = 'txtro';
    const TEXT_NOHTML_READONLY       = 'rotxt';
    const TEXT_NOHTML                = 'edtxt';
    const TEXT_READONLY              = 'ro';
    const ACHECK                     = 'acheck';
    const CNTR                       = 'cntr';
    const COROTXT                    = 'corotxt';
    const COTXT                      = 'cotxt';
    const DYN                        = 'dyn';
    const MATH                       = 'math';
    const STREE                      = 'stree';
    const SUB_ROW                    = 'sub_row';
    const SUB_ROW_AJAX               = 'sub_row_ajax';
    const SUB_ROW_GRID               = 'sub_row_grid';
    const TIME                       = 'time';
    const TREE                       = 'tree';
    const DATERO                     = 'datero';
}
