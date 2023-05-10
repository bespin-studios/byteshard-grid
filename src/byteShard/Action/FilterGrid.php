<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Action;

use byteShard\Cell;
use byteShard\Internal\Action;
use byteShard\Internal\Action\ActionResultInterface;

/**
 * Class FilterGrid
 * @package byteShard\Action
 */
class FilterGrid extends Action
{
    private string $cell;

    /**
     * FilterGrid constructor.
     * @param string $cell
     */
    public function __construct(string $cell)
    {
        parent::__construct();
        $this->cell = Cell::getContentCellName($cell);
        $this->addUniqueID($this->cell);
    }

    protected function runAction(): ActionResultInterface
    {
        $container = $this->getLegacyContainer();
        $id        = $this->getLegacyId();
        if (($container instanceof Cell) && isset($id['usrInput'])) {
            $cells = $this->getCells([$this->cell]);
            foreach ($cells as $cell) {
                $cell->setFilterValue($id['usrInput']);
            }
        }
        return new Action\ActionResult();
    }

    //TODO: filterRowIDs
    /*public function runAction(BSCell &$cell, &$id) {
       $tmp_id           = ID::explode($this->cellId);
       if ($tmp_id instanceof Struct\Navigation_ID) {
          $tmpCell = $_SESSION[MAIN]->getCell($tmp_id);
          if (is_subclass_of($tmpCell->getContentClass(), '\byteShard\Grid')) {
             if ($tmp_id instanceof Struct\Popup_ID) {
                $action['LCell'][$tmp_id->Popup_ID][$tmp_id->LCell_ID]['filterRowIDs']['Ar2_Ug1'] = true;
                $action['LCell'][$tmp_id->Popup_ID][$tmp_id->LCell_ID]['filterRowIDs']['Ar2_Ug2'] = true;
             } elseif ($tmp_id instanceof Struct\Tab_ID) {
                //$action['LCell'][$tmp_id->Tab_ID][$tmp_id->LCell_ID]['filterRowIDs']['Ar2_Ug1'] = true;
                //$action['LCell'][$tmp_id->Tab_ID][$tmp_id->LCell_ID]['filterRowIDs']['Ar3_Ug1'] = true;
                $action['LCell'][$tmp_id->Tab_ID][$tmp_id->LCell_ID]['filterRowIDs']['Pa10'] = true;
                //$action['LCell'][$tmp_id->Tab_ID][$tmp_id->LCell_ID]['filterRowIDs'] = 'all';
             }
          }
       }
       $action['state'] = 2;
       return $action;
    }*/
}
