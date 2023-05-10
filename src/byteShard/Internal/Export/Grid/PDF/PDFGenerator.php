<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Export\Grid\PDF;

use SimpleXMLElement;

class PDFGenerator
{
    public int         $minOffsetTop    = 10;
    public int         $minOffsetBottom = 10;
    public int         $minOffsetLeft   = 10;
    public int         $minOffsetRight  = 10;
    public int         $headerHeight    = 7;
    public int         $rowHeight       = 5;
    public int         $minColumnWidth  = 13;
    public int         $fontSize        = 8;
    public int         $dpi             = 96;
    public bool        $stripTags       = false;
    public string      $bgColor         = 'D1E5FE';
    public string      $lineColor       = 'A4BED4';
    public string      $headerTextColor = '000000';
    public string      $scaleOneColor   = 'FFFFFF';
    public string      $scaleTwoColor   = 'E3EFFF';
    public string      $gridTextColor   = '000000';
    public string      $pageTextColor   = '000000';
    public int         $footerImgHeight = 50;
    public int         $headerImgHeight = 50;
    public array       $lang            = [
        'a_meta_charset'  => 'UTF-8',
        'a_meta_dir'      => 'ltr',
        'a_meta_language' => 'en',
        'w_page'          => 'Page'
    ];
    private string     $orientation     = 'P';
    private array      $columns         = [];
    private array      $rows            = [];
    private int        $summaryWidth    = 0;
    private string     $profile         = '';
    private bool       $header          = false;
    private bool       $footer          = false;
    private string     $headerFile      = '';
    private string     $footerFile      = '';
    private bool       $pageHeader      = false;
    private bool       $pageFooter      = false;
    private array      $columnOptions   = [];
    private array      $hiddenColumns   = [];
    private array      $widths          = [];
    private string     $filename;
    private array      $footerColumns   = [];
    private PDFWrapper $wrapper;


    /**
     * PDFGenerator constructor.
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    // print grid
    public function printGrid(SimpleXMLElement|bool $xml): void
    {
        if ($xml instanceof SimpleXMLElement) {
            $this->headerParse($xml->head);
            $this->footerParse($xml->foot);
            $this->mainParse($xml);
            $this->collectionsParse($xml->coll_options);
            $this->rowsParse($xml->row);
            $this->printGridPdf();
        }
    }

    // sets colors according profile
    private function setProfile(): void
    {
        switch ($this->profile) {
            case 'color':
                $this->bgColor         = 'D1E5FE';
                $this->lineColor       = 'A4BED4';
                $this->headerTextColor = '000000';
                $this->scaleOneColor   = 'FFFFFF';
                $this->scaleTwoColor   = 'E3EFFF';
                $this->gridTextColor   = '000000';
                $this->pageTextColor   = '000000';
                break;
            case 'gray':
                $this->bgColor         = 'E3E3E3';
                $this->lineColor       = 'B8B8B8';
                $this->headerTextColor = '000000';
                $this->scaleOneColor   = 'FFFFFF';
                $this->scaleTwoColor   = 'EDEDED';
                $this->gridTextColor   = '000000';
                $this->pageTextColor   = '000000';
                break;
            case 'bw':
                $this->bgColor         = 'FFFFFF';
                $this->lineColor       = '000000';
                $this->headerTextColor = '000000';
                $this->scaleOneColor   = 'FFFFFF';
                $this->scaleTwoColor   = 'FFFFFF';
                $this->gridTextColor   = '000000';
                $this->pageTextColor   = '000000';
                break;
        }
    }


    // parses main settings
    private function mainParse(SimpleXMLElement $xml): void
    {
        $attributes = $xml->attributes();
        if ($attributes !== null) {
            $this->profile = (string)$attributes->profile;
            if ($attributes->header) {
                $this->header = (bool)$attributes->header;
            }
            if ($attributes->footer) {
                $this->footer = (bool)$attributes->footer;
            }
            if ($attributes->pageheader) {
                $this->pageHeader = (bool)$attributes->pageheader;
            }
            if ($attributes->pagefooter) {
                $this->pageFooter = (bool)$attributes->pagefooter;
            }
            if (100 / count($this->widths) < $this->minColumnWidth) {
                $this->orientation = 'L';
            }
            if ($attributes->orientation) {
                $this->orientation = (string)$attributes->orientation === 'landscape' ? 'L' : 'P';
            }
        }
        $this->setProfile();
    }


    // parses grid header
    private function headerParse(SimpleXMLElement $header): void
    {
        $widths = [];
        if (isset($header->column)) {
            $columnsRows = [$header->column];
        } else {
            $columnsRows = $header->columns;
        }
        if ($columnsRows !== null) {
            $i = 0;
            foreach ($columnsRows as $columns) {
                $summaryWidth = 0;
                $k            = 0;

                foreach ($columns as $column) {
                    if ((string)$column->attributes()?->hidden === 'true') {
                        $this->hiddenColumns[$k] = true;
                        $k++;
                        continue;
                    }
                    $columnArr = [
                        'hidden'  => false,
                        'text'    => $this->stripTags === true ? strip_tags(trim((string)$column)) : trim((string)$column),
                        'width'   => (int)$column->attributes()?->width,
                        'type'    => trim((string)$column->attributes()?->type),
                        'align'   => trim((string)$column->attributes()?->align),
                        'colspan' => trim((string)$column->attributes()?->colspan),
                        'rowspan' => trim((string)$column->attributes()?->rowspan)
                    ];

                    $summaryWidth        += $columnArr['width'];
                    $this->columns[$i][] = $columnArr;
                    if ($i === 0) {
                        $widths[] = $columnArr['width'];
                    }
                    $k++;
                }
                $this->columns[$i]['width'] = $summaryWidth;
                if ($i === 0) {
                    $this->summaryWidth = $summaryWidth;
                }
                $i++;
            }
        }

        for ($i = 0; $i < count($this->columns); $i++) {
            for ($j = 0; $j < count($widths); $j++) {
                if ($this->columns[$i][$j]['colspan'] != '') {
                    $w = $widths[$j];
                    for ($k = 1; $k < $this->columns[$i][$j]['colspan']; $k++) {
                        $w                                   += $widths[$j + $k];
                        $this->columns[$i][$j + $k]['width'] = 0;
                    }
                    $this->columns[$i][$j]['width'] = $w;
                    $j                              += $this->columns[$i][$j]['colspan'] - 1;
                } else {
                    $this->columns[$i][$j]['width'] = $widths[$j];
                }
            }
        }

        for ($i = 0; $i < count($this->columns); $i++) {
            for ($j = 0; $j < count($widths); $j++) {
                if ((isset($this->columns[$i][$j])) && ($this->columns[$i][$j]['rowspan'] != '') && (!isset($this->columns[$i][$j]['rowspanPos']))) {
                    for ($k = 1; $k < $this->columns[$i][$j]['rowspan']; $k++) {
                        $this->columns[$i + $k][$j]['rowspanPos'] = $this->columns[$i][$j]['rowspan'] - $k;
                        $this->columns[$i + $k][$j]['rowspan']    = $this->columns[$i][$j]['rowspan'];
                    }
                    $this->columns[$i][$j]['rowspanPos'] = 'top';
                }
            }
        }
        $this->widths = $widths;
    }


    // parses grid footer
    private function footerParse(SimpleXMLElement $footer): void
    {
        $this->footerColumns = [];
        if (isset($footer->columns)) {
            $columnsRows = $footer->columns;
            $i           = 0;
            foreach ($columnsRows as $columns) {
                $summaryWidth = 0;
                $j            = 0;
                foreach ($columns as $column) {
                    $columnArr = [];
                    if ($this->stripTags === true) {
                        $columnArr['text'] = strip_tags(trim((string)$column));
                    } else {
                        $columnArr['text'] = trim((string)$column);
                    }
                    $columnArr['width']        = $this->columns[0][$j]['width'] ?? 1;
                    $columnArr['type']         = trim((string)$column->attributes()?->type);
                    $columnArr['align']        = trim((string)$column->attributes()?->align);
                    $columnArr['colspan']      = trim((string)$column->attributes()?->colspan);
                    $columnArr['rowspan']      = trim((string)$column->attributes()?->rowspan);
                    $summaryWidth              += $columnArr['width'];
                    $this->footerColumns[$i][] = $columnArr;
                    if ($columnArr['colspan'] != '') {
                        $columnArr['width'] = 0;
                    }
                    $j++;
                }
                $this->footerColumns[$i]['width'] = $summaryWidth;
                $i++;
            }

            for ($i = 0; $i < count($this->footerColumns); $i++) {
                for ($j = 0; $j < count($this->widths); $j++) {
                    if ($this->footerColumns[$i][$j]['colspan'] !== '') {
                        $w = $this->widths[$j];
                        for ($k = 1; $k < $this->footerColumns[$i][$j]['colspan']; $k++) {
                            $w                                         += $this->widths[$j + $k];
                            $this->footerColumns[$i][$j + $k]['width'] = 0;
                        }
                        $this->footerColumns[$i][$j]['width'] = $w;
                        $j                                    += $this->footerColumns[$i][$j]['colspan'] - 1;
                    } else {
                        $this->footerColumns[$i][$j]['width'] = $this->widths[$j];
                    }
                }
            }

            for ($i = 0; $i < count($this->footerColumns); $i++) {
                for ($j = 0; $j < count($this->widths); $j++) {
                    if (($this->footerColumns[$i][$j]['rowspan'] !== '') && (!isset($this->footerColumns[$i][$j]['rowspanPos']))) {
                        for ($k = 1; $k < $this->footerColumns[$i][$j]['rowspan']; $k++) {
                            $this->footerColumns[$i + $k][$j]['rowspanPos'] = $this->footerColumns[$i][$j]['rowspan'] - $k;
                            $this->footerColumns[$i + $k][$j]['rowspan']    = $this->footerColumns[$i][$j]['rowspan'];
                        }
                        $this->footerColumns[$i][$j]['rowspanPos'] = 'top';
                    }
                }
            }
        }
    }


    private function collectionsParse(SimpleXMLElement $columnOptions): void
    {
        for ($i = 0; $i < count($columnOptions); $i++) {
            $index                       = (int)$columnOptions[$i]->attributes()->for;
            $this->columnOptions[$index] = [];
            for ($j = 0; $j < count($columnOptions[$i]->item); $j++) {
                $item                                = $columnOptions[$i]->item[$j];
                $value                               = (string)$item->attributes()->value;
                $label                               = (string)$item->attributes()->label;
                $this->columnOptions[$index][$value] = $label;
            }
        }
    }


    // parses rows
    private function rowsParse(SimpleXMLElement $rows): void
    {
        $i = 0;
        foreach ($rows as $row) {
            $rowArr = [];
            $cells  = $row->cell;
            $k      = 0;
            foreach ($cells as $cell) {
                if (isset($this->hiddenColumns[$k])) {
                    $k++;
                    continue;
                }
                $cellProperties = [];
                if ($this->stripTags === true) {
                    if (isset($this->columnOptions[$k][trim((string)$cell)])) {
                        $cellProperties['text'] = strip_tags($this->columnOptions[$k][trim((string)$cell)]);
                    } else {
                        $cellProperties['text'] = strip_tags(trim((string)$cell));
                    }
                } else {
                    if (isset($this->columnOptions[$k][trim((string)$cell)])) {
                        $cellProperties['text'] = $this->columnOptions[$k][trim((string)$cell)];
                    } else {
                        $cellProperties['text'] = trim((string)$cell);
                    }
                }
                if (isset($cell->attributes()->bgColor)) {
                    $cellProperties['bg'] = (string)$cell->attributes()->bgColor;
                } else {
                    $color                = ($i % 2 == 0) ? $this->scaleOneColor : $this->scaleTwoColor;
                    $cellProperties['bg'] = $color;
                }
                if (isset($cell->attributes()->textColor)) {
                    $cellProperties['textColor'] = (string)$cell->attributes()->textColor;
                } else {
                    $cellProperties['textColor'] = $this->gridTextColor;
                }
                $cellProperties['bold']   = isset($cell->attributes()->bold) && $cell->attributes()->bold == 'bold';
                $cellProperties['italic'] = isset($cell->attributes()->italic) && $cell->attributes()->italic == 'italic';
                $cellProperties['align']  = isset($cell->attributes()->align) ? $cell->attributes()->align : false;
                $rowArr[]                 = $cellProperties;
                $k++;
            }
            $this->rows[] = $rowArr;
            $i++;
        }
    }


    // returns header image name
    private function headerImgInit(): void
    {
        if (file_exists('./header.png')) {
            $this->headerFile = './header.png';
        } else {
            $this->header     = false;
            $this->pageHeader = false;
        }
    }


    // returns footer image name
    private function footerImgInit(): void
    {
        if (file_exists('./footer.png')) {
            $this->footerFile = './footer.png';
        } else {
            $this->footer     = false;
            $this->pageFooter = false;
        }
    }


    public function printGridPdf(): void
    {
        if (($this->header) || ($this->pageHeader)) {
            $this->headerImgInit();
        }
        if (($this->footer) || ($this->pageFooter)) {
            $this->footerImgInit();
        }

        // initials PDF-wrapper
        $this->wrapper = new PDFWrapper(
            $this->minOffsetTop,
            $this->minOffsetRight,
            $this->minOffsetBottom,
            $this->minOffsetLeft,
            $this->orientation,
            $this->fontSize,
            $this->dpi,
            $this->lang
        );

        // checking if document will have one page
        $pageHeight = $this->wrapper->getPageHeight() - $this->minOffsetTop - $this->minOffsetBottom;
        if (($this->header) || ($this->pageHeader)) {
            $pageHeight -= $this->headerImgHeight;
        }
        if (($this->footer) || ($this->pageFooter)) {
            $pageHeight -= $this->footerImgHeight;
        }
        $numRows = floor(($pageHeight - $this->headerHeight) / $this->rowHeight);
        // denies page numbers if document has one page
        if ($numRows >= count($this->rows)) {
            $this->wrapper->setNoPages();
        }

        $pageNumber = 1;
        $startRow   = 0;
        // circle for printing all pages
        while ($startRow < count($this->rows)) {
            $numRows  = $this->printGridPage($startRow, $pageNumber);
            $startRow += $numRows;
            if ($numRows == 0) {
                $startRow++;
            }
            $pageNumber++;
        }

        $this->wrapper->setFilename($this->filename);
        // outputs PDF in browser
        $this->wrapper->pdfOut();
    }


    // prints one page
    private function printGridPage(int $startRow, int $pageNumber): int
    {
        // adds new page
        $this->wrapper->addPage();

        // calculates top offset
        if ((($this->header) && ($pageNumber == 1)) || ($this->pageHeader)) {
            $offsetTop = $this->headerImgHeight;
        } else {
            $offsetTop = 0;
        }

        // calculates bottom offset
        if ($this->pageFooter) {
            $offsetBottom = $this->footerImgHeight;
        } else {
            $offsetBottom = 0;
        }

        // calculates page height without top and bottom offsets
        $pageHeight = $this->wrapper->getPageHeight() - $offsetTop - $offsetBottom - $this->minOffsetTop - $this->minOffsetTop;
        // calculates rows number on current page
        $numRows = floor(
            ($pageHeight - $this->headerHeight * count($this->columns) - $this->headerHeight * count(
                    $this->footerColumns
                )) / $this->rowHeight
        );

        $offsetRight = $this->minOffsetRight;
        $offsetLeft  = $this->minOffsetLeft;
        // sets page offsets
        $this->wrapper->setPageSize($offsetTop, $offsetRight, $offsetBottom, $offsetLeft);

        // prints grid header
        $this->wrapper->headerDraw(
            $this->headerHeight,
            $this->columns,
            $this->summaryWidth,
            $this->headerTextColor,
            $this->bgColor,
            $this->lineColor
        );
        // prints grid footer
        $this->wrapper->footerDraw($this->headerHeight, $this->footerColumns);
        // prints grid values
        $rowsNum = $this->wrapper->gridDraw(
            $this->rowHeight,
            $this->rows,
            $this->widths,
            $startRow,
            $numRows,
            $this->scaleOneColor,
            $this->scaleTwoColor,
            $this->profile
        );

        // prints footer if needed
        if (($this->pageFooter) || ((count($this->rows) <= $startRow + $rowsNum) && ($this->footer))) {
            $this->wrapper->drawImgFooter($this->footerFile, $this->footerImgHeight);
        }

        // prints header if needs
        if ((($this->header) && ($pageNumber == 1)) || ($this->pageHeader)) {
            $this->wrapper->drawImgHeader($this->headerFile);
        }
        // returns number of printed rows ;
        return $rowsNum;
    }
}
