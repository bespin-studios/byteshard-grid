<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Export;

use byteShard\Internal\Export\Grid\Excel;
use byteShard\Internal\Export\Grid\PDF\PDFGenerator;
use byteShard\Internal\ExportHandler;
use byteShard\Locale;
use PhpOffice\PhpSpreadsheet\Exception;

class Handler implements HandlerInterface
{
    private ExportHandler $exportHandler;

    public function __construct(ExportHandler $exportHandler) {
        $this->exportHandler = $exportHandler;
    }

    public function getPDFExport(): void
    {
        $xmlString = $_POST['grid_xml'];

        $xmlString = urldecode($xmlString);

        $xml = simplexml_load_string($xmlString);
        $pdf = new PDFGenerator($this->exportHandler->getFilename());
        $pdf->printGrid($xml);
        $this->exportHandler->updateSession(ExportHandler::FINISHED);
    }


    /**
     * @param string $documentTitle
     * @param string $documentAuthor
     * @param string $type
     * @throws Exception
     */
    public function getXLSExport(string $documentTitle, string $documentAuthor, string $type = 'Excel'): void
    {
        $xls = new Excel($type);
        $xls->setAuthor($documentAuthor);
        $xls->setSheetName($documentTitle);
        $xls->setAppName($this->exportHandler->getAppName());
        if ($xls->setXml($_POST['grid_xml']) === true) {
            $GLOBALS['output_buffer'] = ob_get_clean();
            //TODO: catch exception and show error message in the client
            $xls->createFile();
            $this->exportHandler->updateSession(ExportHandler::FINISHED);
            $result = $xls->getFile();
            header('Content-Type: '.$result->getContentType());
            header('Content-Disposition: attachment; filename="'.$this->exportHandler->getFilename().'.'.$result->getFileExtension().'"');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: no-cache, no-store, must-revalidate, no-transform');
            header('Content-Transfer-Encoding: binary');
            $contentLength = $result->getContentLength();
            if ($contentLength !== null) {
                header('Content-Length: '.$contentLength);
            }
            $result->getContent();
        } else {
            $this->exportHandler->updateSession(ExportHandler::ERROR, Locale::get('byteShard.bs_export.error'));
        }
    }
}