<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Action\Grid;

use byteShard\Cell;
use byteShard\ID\RowID;
use byteShard\Internal\Action;
use byteShard\Internal\Action\ActionResultInterface;

/**
 * @API
 */
class DeleteRow extends Action
{
    private string $cell;
    /** @var RowID[] */
    private array $rowIds;

    public function __construct(string $cell, array ...$rowIds)
    {
        parent::__construct();
        $this->cell = Cell::getContentCellName($cell);
        foreach ($rowIds as $rowId) {
            $this->rowIds[] = new RowID($rowId);
        }
    }

    protected function runAction(): ActionResultInterface
    {
        $action = [];
        $cells  = $this->getCells([$this->cell]);
        foreach ($cells as $cell) {
            $cellNonce = $cell->getNonce();
            if (empty($this->rowIds)) {
                $selectedId = $cell->getSelectedId()?->getIds();
                if (!empty($selectedId)) {
                    $action['LCell'][$cell->containerId()][$cell->cellId()]['modifyRows'][RowID::encrypt($selectedId, $cellNonce)] = 'deleteRow';
                }
            } else {
                foreach ($this->rowIds as $rowId) {
                    $action['LCell'][$cell->containerId()][$cell->cellId()]['modifyRows'][$rowId->getEncryptedRowId($cellNonce)] = 'deleteRow';
                }
            }
        }
        return new Action\ActionResultMigrationHelper($action);
    }
}