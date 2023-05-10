<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Export\Grid;

use byteShard\File\CSV;
use byteShard\File\FileInterface;
use byteShard\File\Xls;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Exception;

class Wrapper
{
    private string $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private array $types = [];

    private string $textColor = '';

    private int $currentRow = 1;

    private array $columns = [];

    private Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function createXLS(string $creator, string $lastModifiedBy, string $title, string $subject, string $dsc, string $keywords, string $category): void
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->getProperties()->setCreator($creator)
            ->setLastModifiedBy($lastModifiedBy)
            ->setTitle($title)
            ->setSubject($subject)
            ->setDescription($dsc)
            ->setKeywords($keywords)
            ->setCategory($category);
    }

    /**
     * @param string $title
     * @param string $type
     * @return FileInterface
     * @throws Exception
     */
    public function outXLS(string $title, string $type = 'Excel2007'): FileInterface
    {
        $this->spreadsheet->getActiveSheet()->setTitle($title);
        $this->spreadsheet->setActiveSheetIndex(0);

        $file = match (strtolower($type)) {
            'csv'   => new CSV(),
            default => new Xls(),
        };

        $file->setContent($this->spreadsheet);
        return $file;
    }

    /**
     * @param array $rows
     * @param int $height
     * @param string $color
     * @param string $background_color
     * @param string $border_color
     * @param int $size
     * @param string $font
     * @param bool $without_header
     * @throws Exception
     */
    public function addHeader(array $rows, int $height, string $color, string $background_color, string $border_color, int $size, string $font, bool $without_header = false): void
    {
        $this->textColor = $color;
        $this->columns   = $rows;
        $this->types     = [];
        $this->spreadsheet->setActiveSheetIndex(0);
        $active_sheet = $this->spreadsheet->getActiveSheet();
        $first_row    = current($rows);
        foreach ($first_row as $key => $column) {
            $this->types[$key] = $column['excel_type'] !== '' ? $column['excel_type'] : $column['type'];
        }
        if ($without_header === false) {
            foreach ($rows as $row_number => $columns) {
                $this->spreadsheet->getActiveSheet()->getRowDimension($this->currentRow)->setRowHeight($height);
                foreach ($columns as $column_number => $column) {
                    $active_sheet->setCellValueByColumnAndRow($column_number + 1, $this->currentRow, $column['text']);
                    $active_sheet->getColumnDimension($this->getColumnName($column_number))->setWidth(($column['width']) / 6);
                    $active_sheet->getStyle($this->getColumnName($column_number).$this->currentRow)->getFont()->getColor()->setRGB($color);
                    if (isset($column['colspan'])) {
                        $active_sheet->mergeCells($this->getColumnName($column_number).($this->currentRow + 1).':'.$this->getColumnName($column_number + $column['colspan'] - 1).($this->currentRow + 1));
                    }
                    if (isset($column['rowspan'])) {
                        $active_sheet->mergeCells($this->getColumnName($column_number).($this->currentRow + 1).':'.$this->getColumnName($column_number).($this->currentRow + min($column['rowspan'], count($this->columns))));
                    }
                    $alignment = $active_sheet->getStyle($this->getColumnName($column_number).$this->currentRow)->getAlignment();
                    $alignment->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $alignment->setVertical(Alignment::VERTICAL_CENTER);
                    $alignment->setWrapText(true);
                }
                $this->currentRow++;
            }
            $style = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => $this->getARGB($border_color)]]],
                'fill'    => [
                    'fillType'   => Fill::FILL_SOLID,
                    'rotation'   => 90,
                    'startColor' => ['argb' => $this->getARGB($background_color)]],
                'font'    => [
                    'bold' => true,
                    'name' => $font,
                    'size' => $size]];
            $this->spreadsheet->getActiveSheet()->getStyle(('A1:'.$this->getColumnName(count($rows[0]) - 1).($this->currentRow - 1)))->applyFromArray($style);
            $this->spreadsheet->getActiveSheet()->freezePane('A'.(count($rows) + 1));
        }
    }

    /**
     * @param array $rows
     * @param int $height
     * @param string $border_color
     * @param int $size
     * @param string $font
     * @throws Exception
     */
    public function addRows(array $rows, int $height, string $border_color, int $size, string $font): void
    {
        $this->spreadsheet->setActiveSheetIndex(0);
        $active_sheet = $this->spreadsheet->getActiveSheet();
        $style        = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => $this->getARGB($border_color)]]],
            'fill'    => [
                'fillType' => Fill::FILL_SOLID,
                'rotation' => 90],
            'font'    => [
                'bold'  => false,
                'name'  => $font,
                'size'  => $size,
                'color' => ['rgb' => $this->getARGB($this->textColor)]]];
        foreach ($rows as $row) {
            $active_sheet->getRowDimension($this->currentRow)->setRowHeight($height);
            // set row style
            $active_sheet->getStyle(($this->getColumnName(0).$this->currentRow.':'.$this->getColumnName(count($row) - 1).$this->currentRow))->applyFromArray($style);
            $row_style = $active_sheet->getStyle(($this->getColumnName(0).$this->currentRow.':'.$this->getColumnName(count($row) - 1).$this->currentRow));
            $row_style->applyFromArray($style);
            $row_style->getAlignment()->setWrapText(true);
            foreach ($row as $column_number => $column) {
                $text = $column['text'];
                if (isset($this->types[$column_number]) && ($this->types[$column_number] == 'ch' || $this->types[$column_number] == 'ra')) {
                    //TODO: Localize
                    $text = $text === '1' ? 'Yes' : 'No';
                }
                switch (strtolower($this->types[$column_number])) {
                    case 'string':
                    case 'str':
                    case 'txt':
                    case 'edtxt':
                    case 'rotxt':
                    case 'ro':
                    case 'co':
                    case 'coro':
                        $active_sheet->getCell($this->getColumnName($column_number).$this->currentRow)->setValueExplicit($text, DataType::TYPE_STRING);
                        break;
                    case 'number':
                    case 'num':
                    case 'edn':
                    case 'ron':
                        $text = str_replace(",", ".", $text);
                        $text = is_string($text) && !is_numeric($text) ? null : $text;
                        $active_sheet->getCell($this->getColumnName($column_number).$this->currentRow)->setValueExplicit($text, DataType::TYPE_NUMERIC);
                        break;
                    case 'boolean':
                    case 'bool':
                        $active_sheet->getCell($this->getColumnName($column_number).$this->currentRow)->setValueExplicit($text, DataType::TYPE_BOOL);
                        break;
                    case 'formula':
                        $active_sheet->getCell($this->getColumnName($column_number).$this->currentRow)->setValueExplicit($text, DataType::TYPE_FORMULA);
                        break;
                    case 'date':
                        $active_sheet->setCellValueByColumnAndRow($column_number + 1, $this->currentRow, $text);
                        $active_sheet->getStyle($this->getColumnName($column_number).$this->currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
                        break;
                    default:
                        $active_sheet->setCellValueByColumnAndRow($column_number + 1, $this->currentRow, $text);
                        break;
                }
                // set individual style per cell
                $cellStyle['font']['bold']       = $column['bold'];
                $cellStyle['font']['italic']     = $column['italic'];
                $cellStyle['font']['color']      = ['argb' => $this->getARGB($column['textColor'])];
                $cellStyle['fill']['startColor'] = ['argb' => $this->getARGB($column['bg'])];
                $active_sheet->getStyle($this->getColumnName($column_number).$this->currentRow)->applyFromArray($cellStyle);
                $active_sheet->getStyle($this->getColumnName($column_number).$this->currentRow)->getAlignment()->setHorizontal($column['align'] !== null ? $column['align'] : $this->columns[0][$column_number]['align']);
            }
            $this->currentRow++;
        }
    }

    /**
     * @param array $rows
     * @param int $height
     * @param string $color
     * @param string $background_color
     * @param string $border_color
     * @param int $size
     * @param string $font
     * @throws Exception
     */
    public function addFooter(array $rows, int $height, string $color, string $background_color, string $border_color, int $size, string $font): void
    {
        if (empty($rows)) {
            return;
        }
        $activeSheet = $this->spreadsheet->getActiveSheet();
        $style       = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => $this->getARGB($border_color)]]],
            'fill'    => [
                'fillType'   => Fill::FILL_SOLID,
                'rotation'   => 90,
                'startColor' => ['argb' => $this->getARGB($background_color)]],
            'font'    => [
                'bold' => true,
                'name' => $font,
                'size' => $size]];
        $activeSheet->getStyle('A'.$this->currentRow.':'.$this->getColumnName(count($rows[0]) - 1).($this->currentRow + count($rows)))->applyFromArray($style);
        foreach ($rows as $columns) {
            $activeSheet->getRowDimension($this->currentRow)->setRowHeight($height);
            foreach ($columns as $column_number => $column) {
                $activeSheet->setCellValueByColumnAndRow($column_number + 1, $this->currentRow, $column['text']);
                $styleObject = $activeSheet->getStyle($this->getColumnName($column_number).$this->currentRow);
                $styleObject->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $styleObject->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $styleObject->getFont()->getColor()->setARGB($this->getARGB($color));
                if (isset($column['colspan'])) {
                    $activeSheet->mergeCells($this->getColumnName($column_number).$this->currentRow.':'.$this->getColumnName($column_number + $column['colspan'] - 1).$this->currentRow);
                }
                if (isset($column['rowspan'])) {
                    $activeSheet->mergeCells($this->getColumnName($column_number).$this->currentRow.':'.$this->getColumnName($column_number).($this->currentRow - 1 + $column['rowspan']));
                }
            }
            $this->currentRow++;
        }
    }

    /**
     * @param int $column_number
     * @return string
     */
    private function getColumnName(int $column_number): string
    {
        $column_number++;
        $column_name = '';
        while ($column_number > 0) {
            $mod           = ($column_number - 1) % 26;
            $column_name   = $this->alphabet[$mod].$column_name;
            $column_number = (int)(($column_number - $mod) / 26);
        }
        return $column_name;
    }

    /**
     * @param string $color
     * @return ?string
     */
    private function getARGB(string $color): ?string
    {
        $color = $this->processColorForm($color);
        if ($color !== 'transparent') {
            return "FF".strtoupper($color);
        }
        return null;
    }

    /**
     * @param string $color
     * @return ?string
     */
    private function getRGB(string $color): ?string
    {
        $color = $this->processColorForm($color);
        if ($color !== 'transparent') {
            return $color;
        }
        return null;
    }

    /**
     * @param string $color
     * @return string
     */
    private function processColorForm(string $color): string
    {
        if ($color === 'transparent') {
            return $color;
        }
        if (preg_match("/#[0-9A-Fa-f]{6}/", $color)) {
            return substr($color, 1);
        }
        if (preg_match("/[0-9A-Fa-f]{6}/", $color)) {
            return $color;
        }
        $result = preg_match_all("/rgb\s?\(\s?(\d{1,3})\s?,\s?(\d{1,3})\s?,\s?(\d{1,3})\s?\)/", trim($color), $rgb);

        if ($result) {
            $color = '';
            for ($i = 1; $i <= 3; $i++) {
                $comp  = dechex($rgb[$i][0]);
                $color .= strlen($comp) === 1 ? '0'.$comp : $comp;
            }
            return $color;
        }
        return 'transparent';
    }
}
