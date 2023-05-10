<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Export\Grid\PDF;

use TCPDF;

class CustomTCPDF extends TCPDF
{
    public function Footer()
    {
        $cur_y = $this->GetY();
        $original_margins = $this->getOriginalMargins();
        $this->SetTextColor(0, 0, 0);
        //set style for cell border
        $line_width = 0.85 / $this->getScaleFactor();
        $this->SetLineStyle(
            array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(0, 0, 0))
        );
        //print document barcode
        $barcode = $this->getBarcode();
        if (!empty($barcode)) {
            $this->Ln($line_width);
            $barcode_width = round(($this->getPageWidth() - $original_margins['left'] - $original_margins['right']) / 3);
            $this->write1DBarcode(
                $barcode,
                'C128B',
                $this->GetX(),
                $cur_y + $line_width,
                $barcode_width,
                (($this->getFooterMargin() / 3) - $line_width),
                0.3,
                '',
                ''
            );
        }
        if (empty($this->pagegroups)) {
            $page_num_text = $this->l['w_page'] . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
        } else {
            $page_num_text = $this->l['w_page'] . ' ' . $this->getPageNumGroupAlias() . ' / ' . $this->getPageGroupAlias();
        }
        $this->SetY($cur_y);
        //Print page number
        if ($this->getRTL()) {
            $this->SetX($original_margins['right']);
            $this->Cell(0, 0, $page_num_text, 0, 0, 'L');
        } else {
            $this->SetX($original_margins['left']);
            $this->Cell(0, 0, $page_num_text, 0, 0, 'R');
        }
    }
}