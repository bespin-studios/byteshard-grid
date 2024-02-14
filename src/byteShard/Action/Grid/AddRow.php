<?php

namespace byteShard\Action\Grid;

use byteShard\Cell;
use byteShard\ID\RowID;
use byteShard\Internal\Action;
use byteShard\Internal\Action\ActionResultInterface;

class AddRow extends Action
{
    private string $cell;
    private array  $columns;

    public function __construct(
        string                  $cell,
        private readonly array  $rowId,
        private readonly ?int   $position = null,
        private readonly bool   $scrollToRow = true,
        private readonly string $style = '',
        string                  ...$columns
    )
    {
        parent::__construct();
        $this->cell    = Cell::getContentCellName($cell);
        $this->columns = $columns;
    }

    protected function runAction(): ActionResultInterface
    {
        $action          = [];
        $cells           = $this->getCells([$this->cell]);
        $rowId           = new RowID($this->rowId);
        $data['columns'] = $this->columns;
        if ($this->scrollToRow === true) {
            $data['show'] = true;
        }
        if ($this->position !== null) {
            $data['position'] = $this->position;
        }
        if ($this->style !== '') {
            $data['style'] = $this->style;
        }
        foreach ($cells as $cell) {
            $action['LCell'][$cell->containerId()][$cell->cellId()]['addRow'][$rowId->getEncryptedRowId($cell->getNonce())] = $data;
        }
        return new Action\ActionResultMigrationHelper($action);
    }
}