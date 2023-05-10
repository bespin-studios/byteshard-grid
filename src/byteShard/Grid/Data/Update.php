<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Data;

use byteShard\Internal\Data;

/**
 * Class Update
 * @package byteShard\Data\Grid
 */
class Update extends Data\Update
{
    public function process(): array
    {
        if (empty($this->definedFields)) {
            if ($this->sourceCell !== null) {
                $column = $this->clientData->getColumn($this->sourceCell);
            } else {
                $column = $this->clientData->getColumn();
            }
            $this->setFields($column->name);
        }
        return parent::process();
    }
}
