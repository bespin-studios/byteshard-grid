<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\Grid\Column;
use byteShard\Internal\Grid\IDReference;

/**
 * Class Select
 * @package byteShard\CellContent\Grid\Column
 */
class Select extends Column implements IDReference
{
    protected string             $type           = 'select';
    protected string             $dhxTypeRw      = Grid\Enum\Type::SELECT;
    protected string             $dhxTypeRo      = Grid\Enum\Type::SELECT_READONLY;
    protected string             $sort           = Grid\Enum\Sort::STRING;
    protected string             $align          = Grid\Enum\Align::LEFT;
    protected string             $filter         = Grid\Enum\Filter::COMBO_ADVANCED;
    protected int                $width          = 100;
    protected array              $comboOptions   = [];
    private \byteShard\Combo     $combo;
    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::VARCHAR;
    public array                 $idReferences   = [];

    public function __construct(string $id, ?string $label = null, array|\byteShard\Combo $options = [], ?int $width = null, int|Enum\Access $accessType = 1, ?string $dataBinding = null)
    {
        parent::__construct(id: $id, label: $label, width: $width, accessType: $accessType, dataBinding: $dataBinding);
        if ($options instanceof \byteShard\Combo) {
            $this->combo = $options;
        } else {
            $this->comboOptions = $options;
        }
    }

    /**
     * @param array|\byteShard\Combo $options
     * @return $this
     * @API
     */
    public function setComboOptions(array|\byteShard\Combo $options): self
    {
        if ($options instanceof \byteShard\Combo) {
            $this->combo = $options;
        } else {
            $this->comboOptions = $options;
        }
        return $this;
    }

    public function getContents(): array
    {
        $column                   = parent::getColumnContent();
        $column['comboboxValues'] = $this->comboOptions;
        return $column;
    }

    public function getIDReferences(): array
    {
        if (isset($this->combo)) {
            $this->idReferences = $this->combo->getIdReferences();
        } elseif (isset($this->comboOptions['options']) && is_array($this->comboOptions['options'])) {
            foreach ($this->comboOptions['options'] as $option) {
                if (isset($option['text'], $option['value']) && is_string($option['value'])) {
                    $this->idReferences[$option['value']] = $option['text'];
                }
            }
        }
        return $this->idReferences;
    }

    public function getValueForId(null|array|object|string $id): string
    {
        if ($id === null || is_array($id) || is_object($id)) {
            return '';
        }
        if (array_key_exists($id, $this->idReferences)) {
            return $this->idReferences[$id];
        }
        return '';
    }

}
