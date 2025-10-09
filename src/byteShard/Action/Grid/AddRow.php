<?php

namespace byteShard\Action\Grid;

use byteShard\Cell;
use byteShard\Grid;
use byteShard\Internal\Action;
use byteShard\Internal\Action\ActionResultInterface;

class AddRow extends Action
{
    private string $cell;

    /**
     * @param string $cell
     * @param array<object> $values
     * @param int|null $position
     * @param bool $scrollToRow
     * @param string $style
     */
    public function __construct(
        string                  $cell,
        private readonly array  $values,
        private readonly ?int   $position = null,
        private readonly bool   $scrollToRow = true,
        private readonly string $style = ''
    )
    {
        parent::__construct();
        $this->cell = Cell::getContentCellName($cell);
    }

    protected function runAction(): ActionResultInterface
    {
        $action = [];
        $data   = [];
        $cells  = $this->getCells([$this->cell]);
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
            $contentClass = $cell->getContentClass();
            $grid         = new $contentClass($cell);
            if ($grid instanceof Grid) {
                $rowsToAdd = [];
                foreach ($grid->getRows($this->values) as $rowObject) {
                    [$rowId, $columns] = $rowObject->getColumns();
                    if ($rowId !== null) {
                        $rowsToAdd[$rowId]            = $data;
                        $rowsToAdd[$rowId]['columns'] = $columns;
                    }
                }
                if (!empty($rowsToAdd)) {
                    $action['LCell'][$cell->containerId()][$cell->cellId()]['addRow'] = $rowsToAdd;
                }
            }
        }
        return new Action\ActionResultMigrationHelper($action);
    }
}