<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Enum;

/**
 * Class Type
 * @package byteShard\Grid\Enum
 */
enum Type: string
{
    case CALCULATOR = 'calck';
    case CALENDAR = 'dhxCalendar';
    case CALENDAR_MANUAL = 'dhxCalendarA';
    case CHECKBOX = 'ch';
    case CHECKBOX_READONLY = 'chro';
    case CHECKBOX2 = 'chadv';
    case CHECKBOX2_READONLY = 'chadvro';
    case CHECKBOX_TRISTATE = 'trich';
    case CHECKBOX_TRISTATE_READONLY = 'trichro';
    case COLORPICKER = 'cp';
    case COMBO = 'combo';
    case COMBO_READONLY = 'comboro';
    case CONTEXT = 'context';
    case GRID = 'grid';
    case HIDDEN = 'hidden';
    case IMAGE = 'img';
    case LINK = 'blink';
    case NUMERIC = 'edn';
    case NUMERIC_READONLY = 'ron';
    case PRICE = 'price';
    case PRICE_EURO = 'priceEur';
    case PRICE_EURO_READONLY = 'priceEurro';
    case RADIO_COLUMN = 'ra';
    case RADIO_COLUMN_READONLY = 'raro';
    case RADIO_ROW = 'ra_str';
    case RADIO_ROW_READONLY = 'raro_str';
    case SELECT = 'co';
    case SELECT_MULTI = 'clist';
    case SELECT_READONLY = 'coro';
    case TEXT = 'ed';
    case TEXT_MULTILINE = 'txt';
    case TEXT_MULTILINE_NOHTML = 'txttxt';
    case TEXT_MULTILINE_READONLY = 'txtro';
    case TEXT_NOHTML_READONLY = 'rotxt';
    case TEXT_NOHTML = 'edtxt';
    case TEXT_READONLY = 'ro';
    case ACHECK = 'acheck';
    case CNTR = 'cntr';
    case COROTXT = 'corotxt';
    case COTXT = 'cotxt';
    case DYN = 'dyn';
    case MATH = 'math';
    case STREE = 'stree';
    case SUB_ROW = 'sub_row';
    case SUB_ROW_AJAX = 'sub_row_ajax';
    case SUB_ROW_GRID = 'sub_row_grid';
    case TIME = 'time';
    case TREE = 'tree';
    case DATERO = 'datero';
}
