<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Export\Grid;

use byteShard\File\FileInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use SimpleXMLElement;

class Excel
{
    private string $appName;
    // Content arrays
    private array $header = [];
    private array $body   = [];
    private array $footer = [];
    // Height configuration
    private int $header_row_height = 30;
    private int $body_row_height   = 20;
    private int $footer_row_height = 30;
    // Font  configuration
    private string $font_family      = 'Helvetica';
    private int    $header_font_size = 9;
    private int    $body_font_size   = 9;
    private int    $footer_font_size = 9;
    // Color configuration
    private string $background_color = 'D1E5FE';
    private string $border_color     = 'A4BED4';
    private string $even_color       = 'FFFFFF';
    private string $odd_color        = 'E3EFFF';
    private string $text_color       = '000000';
    private string $output_type;
    // Meta data
    private array  $meta           = [];
    private string $title          = 'Grid';
    private bool   $without_header = false;
    private array  $hidden_columns = [];
    private array  $coll_options   = [];
    private bool   $strip_tags     = false;

    /* @var SimpleXMLElement */
    private SimpleXMLElement $xml;
    /** @var Wrapper */
    private Wrapper $wrapper;

    public function __construct(string $type = 'Excel')
    {
        $this->output_type = $type;
        $this->wrapper     = new Wrapper();
    }

    /**
     * the xml that is provided by dhtmlx grid
     * @param string $xmlString
     * @return bool
     */
    public function setXml(string $xmlString): bool
    {
        $xml = simplexml_load_string(urldecode($xmlString));
        if ($xml !== false) {
            $this->xml = $xml;
            return true;
        }
        return false;
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->meta['creator'] = $author;
    }

    /**
     * @param string $sheet_name
     */
    public function setSheetName(string $sheet_name): void
    {
        $sheet_name  = preg_replace("/[^a-zA-Z0-9ÄÖÜäöüß\\040\\.\\-\\_,;<>|+-=!\"§$%&()?#'~^]/", "", $sheet_name);
        $sheet_name  = preg_replace("/[\\/\\\\]/", "_", $sheet_name);
        $sheet_name  = substr($sheet_name, 0, ((strlen($sheet_name) < 31) ? strlen($sheet_name) : 30));
        $this->title = $sheet_name;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->meta['subject'] = $subject;
    }

    /**
     * @param string $app_name
     */
    public function setAppName(string $app_name): void
    {
        $this->appName = $app_name;
    }

    /**
     * @throws Exception
     */
    public function createFile(): void
    {
        $this->meta['creator'] = $this->appName;
        if (isset($this->xml->head)) {
            $this->parseHeader($this->xml->head);
        }
        if (isset($this->xml->foot)) {
            $this->parseFooter($this->xml->foot);
        }
        $this->parseGlobalAttributes();
        $this->collectionsParse($this->xml->coll_options);
        if (isset($this->xml->row)) {
            $this->parseRows($this->xml->row);
        }
        $this->printGridExcel();
    }

    /**
     * @return FileInterface
     */
    public function getFile(): FileInterface
    {
        return $this->wrapper->outXLS($this->title, $this->output_type);
    }

    /**
     * @param SimpleXMLElement $header
     */
    private function parseHeader(SimpleXMLElement $header): void
    {
        if (isset($header->column)) {
            $columns = array($header->column);
        } else {
            $columns = $header->columns;
        }
        $i = 0;
        foreach ($columns as $rows) {
            $this->header[$i] = [];
            $k                = 0;
            foreach ($rows as $column) {
                $cell           = [];
                $cell['hidden'] = $column->attributes()->hidden === 'true';
                if ($cell['hidden'] === true) {
                    $this->hidden_columns[$k] = true;
                    $k++;
                    continue;
                }
                $cell['text']  = ($this->strip_tags === true) ? strip_tags(trim((string)$column)) : trim((string)$column);
                $cell['width'] = (int)trim((string)$column->attributes()->width);
                $cell['type']  = trim((string)$column->attributes()->type);
                $cell['align'] = trim((string)$column->attributes()->align);
                if (isset($column->attributes()->colspan)) {
                    $cell['colspan'] = (int)$column->attributes()->colspan;
                }
                if (isset($column->attributes()->rowspan)) {
                    $cell['rowspan'] = (int)$column->attributes()->rowspan;
                }
                if ($i === 0) {
                    // in case we have multiple header rows, use width and type only from the first row
                    $cell['excel_type'] = (isset($column->attributes()->excel_type)) ? trim((string)$column->attributes()->excel_type) : '';
                }
                $this->header[$i][] = $cell;
                $k++;
            }
            $i++;
        }
    }

    /**
     * @param SimpleXMLElement $footer
     */
    private function parseFooter(SimpleXMLElement $footer): void
    {
        if (isset($footer->columns)) {
            $i = 0;
            foreach ($footer->columns as $rows) {
                $this->footer[$i] = [];
                foreach ($rows as $column) {
                    $cell          = [];
                    $cell['text']  = ($this->strip_tags === true) ? strip_tags(trim((string)$column)) : trim((string)$column);
                    $cell['width'] = (int)trim((string)$column->attributes()->width);
                    $cell['type']  = trim((string)$column->attributes()->type);
                    $cell['align'] = trim((string)$column->attributes()->align);
                    if (isset($column->attributes()->colspan)) {
                        $cell['colspan'] = (int)$column->attributes()->colspan;
                    }
                    if (isset($column->attributes()->rowspan)) {
                        $cell['rowspan'] = (int)$column->attributes()->rowspan;
                    }
                    $this->footer[$i][] = $cell;
                }
                $i++;
            }
        }
    }

    /**
     *
     */
    private function parseGlobalAttributes(): void
    {
        $this->setProfile((string)$this->xml->attributes()->profile);
        if (isset($this->xml->attributes()->without_header)) {
            $this->without_header = true;
        }
    }

    /**
     * @param string $profile
     */
    private function setProfile(string $profile): void
    {
        switch ($profile) {
            case 'color':
                $this->background_color = 'D1E5FE';
                $this->border_color     = 'A4BED4';
                $this->even_color       = 'FFFFFF';
                $this->odd_color        = 'E3EFFF';
                $this->text_color       = '000000';
                break;
            case 'gray':
                $this->background_color = 'E3E3E3';
                $this->border_color     = 'B8B8B8';
                $this->even_color       = 'FFFFFF';
                $this->odd_color        = 'EDEDED';
                $this->text_color       = '000000';
                break;
            case 'bw':
                $this->background_color = 'FFFFFF';
                $this->border_color     = '000000';
                $this->even_color       = 'FFFFFF';
                $this->odd_color        = 'FFFFFF';
                $this->text_color       = '000000';
                break;
        }
    }

    /**
     * @param $coll_options
     */
    private function collectionsParse($coll_options): void
    {
        for ($i = 0, $i_max = count($coll_options); $i < $i_max; $i++) {
            $index                      = (int)$coll_options[$i]->attributes()->for;
            $this->coll_options[$index] = array();
            for ($j = 0, $j_max = count($coll_options[$i]->item); $j < $j_max; $j++) {
                $item                               = $coll_options[$i]->item[$j];
                $value                              = (string)$item->attributes()->value;
                $label                              = (string)$item->attributes()->label;
                $this->coll_options[$index][$value] = $label;
            }
        }
    }

    /**
     * @param SimpleXMLElement $rows
     */
    private function parseRows(SimpleXMLElement $rows): void
    {
        $i = 0;
        foreach ($rows as $row) {
            $rowArr = [];
            if (isset($row->cell)) {
                $k = 0;
                foreach ($row->cell as $column) {
                    $alternating_background_color = ($i % 2 == 0) ? $this->even_color : $this->odd_color;
                    if (isset($this->hidden_columns[$k])) {
                        $k++;
                        continue;
                    }
                    $cell         = [];
                    $cell['text'] = $this->coll_options[$k][trim((string)$column)] ?? trim((string)$column);
                    if ($this->strip_tags === true) {
                        $cell['text'] = strip_tags($cell['text']);
                    }
                    $cell['bg']        = isset($column->attributes()->bgColor) ? (string)$column->attributes()->bgColor : $alternating_background_color;
                    $cell['textColor'] = isset($column->attributes()->textColor) ? (string)$column->attributes()->textColor : $this->text_color;
                    $cell['bold']      = isset($column->attributes()->bold) && $column->attributes()->bold === 'bold';
                    $cell['italic']    = isset($column->attributes()->italic) && $column->attributes()->italic === 'italic';
                    $cell['align']     = isset($column->attributes()->align) ? $column->attributes()->align : null;
                    $rowArr[]          = $cell;
                    $k++;
                }
            }
            $this->body[] = $rowArr;
            $i++;
        }
    }

    /**
     * @throws Exception
     */
    public function printGridExcel(): void
    {
        $this->wrapper = new Wrapper();
        $this->wrapper->createXLS($this->meta['creator'] ?? '', '', $this->title, $this->meta['subject'] ?? '', '', '', '');
        $this->wrapper->addHeader($this->header, $this->header_row_height, $this->text_color, $this->background_color, $this->border_color, $this->header_font_size, $this->font_family, $this->without_header);
        $this->wrapper->addRows($this->body, $this->body_row_height, $this->border_color, $this->body_font_size, $this->font_family);
        $this->wrapper->addFooter($this->footer, $this->footer_row_height, $this->text_color, $this->background_color, $this->border_color, $this->footer_font_size, $this->font_family);
    }
}
