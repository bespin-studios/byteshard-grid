<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\Grid\Column;

/**
 * Class RowSelector
 * @package byteShard\CellContent\Grid\Column
 */
class RowSelector extends Column
{
    protected string             $type                  = 'rowSelector';
    protected string             $dhxTypeRw             = Grid\Enum\Type::CHECKBOX;
    protected string             $dhxTypeRo             = Grid\Enum\Type::CHECKBOX_READONLY;
    protected string             $sort                  = Grid\Enum\Sort::STRING;
    protected string             $align                 = Grid\Enum\Align::CENTER;
    protected string             $filter                = Grid\Enum\Filter::CHECKBOX;
    protected int                $width                 = 55;
    protected Enum\DB\ColumnType $db_column_type        = Enum\DB\ColumnType::VARCHAR;
    private bool                 $comply_to_access_type = false;
    private bool                 $readonlyHidden        = false;

    public function __construct(?string $id = null, ?string $label = null, ?int $width = null, ?string $dataBinding = null)
    {
        $id    = 'rowSelector';
        $label = '#master_checkbox';
        $this->setUnrestrictedAccess();
        parent::__construct(id: $id, label: $label, width: $width, dataBinding: $dataBinding);
    }

    /**
     * This will force the row selector column to comply to the access type of the row level
     * @param bool $comply
     * @return RowSelector
     */
    public function complyToAccessTypes(bool $comply = true): self
    {
        $this->comply_to_access_type = $comply;
        return $this;
    }

    /**
     * This will hide the checkbox on read only row levels
     * @param bool $hidden
     * @return RowSelector
     */
    public function setReadonlyHidden(bool $hidden = true): self
    {
        $this->readonlyHidden = $hidden;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComplyToAccessType(): bool
    {
        return $this->comply_to_access_type;
    }

    /**
     * @return bool
     */
    public function getReadonlyHidden(): bool
    {
        return $this->readonlyHidden;
    }
}
