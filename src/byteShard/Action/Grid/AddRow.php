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
                foreach ($grid->getRows($this->values) as $row) {
                    $data['columns'] = [];
                    foreach ($row['columns'] as $columnId => $column) {
                        $data['columns'][$columnId] = $column['value'];
                    }
                    if (!empty($data['columns'])) {
                        $action['LCell'][$cell->containerId()][$cell->cellId()]['addRow'][$row['row']['attr']['id']] = $data;
                    }
                }
            }
        }
        return new Action\ActionResultMigrationHelper($action);
    }
}