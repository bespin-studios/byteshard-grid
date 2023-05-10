<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid;

use byteShard\Enum;
use byteShard\Internal\Permission\PermissionImplementation;
use byteShard\Settings;

/**
 * Class Node
 * @package byteShard\Grid
 */
class Node
{
    use PermissionImplementation {
        setPermission as PermissionTrait_setPermission;
        setAccessType as PermissionTrait_setAccessType;
    }

    private bool                $expand;
    private string              $field;
    private string              $idField;
    private string              $sortByField;
    private Enum\Sort\Direction $sortDirection = Enum\Sort\Direction::ASC;
    private Enum\Sort\Type      $sortType      = Enum\Sort\Type::STRING;
    private bool                $useId         = true;
    private bool                $visible;

    /**
     * Node constructor.
     * @param string $field
     * @param bool $visible
     * @param int $accessType
     * @param false $expandTo
     */
    public function __construct(string $field, bool $visible = true, mixed $accessType = 1, bool $expandTo = false)
    {
        $this->setAccessType($accessType);
        $this->field   = $field;
        $this->idField = $field.Settings::getIDSuffix();
        $this->visible = $visible;
        $this->expand  = $expandTo;
    }

    public function getFieldToDisplayInClient(): string
    {
        return $this->field;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isExpanded(): bool
    {
        return $this->expand;
    }

    public function getSortDirection(): int
    {
        return $this->sortDirection->value;
    }

    public function getSortType(): int
    {
        return $this->sortType->value;
    }

    public function getSortField(): string
    {
        return $this->sortByField ?? $this->field;
    }

    public function getIdField(): string
    {
        return $this->idField;
    }

    public function includeIdInRowId(): bool
    {
        return $this->useId || $this->visible;
    }

    /**
     * @param mixed $enumDbColumnType
     * @return $this
     * @API
     * @deprecated
     */
    public function setDBColumnType(mixed $enumDbColumnType): self
    {
        trigger_error(__METHOD__.' is deprecated and serves no purpose any longer. You can safely remove it.', E_USER_DEPRECATED);
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     * @API
     */
    public function sortBy(string $field): self
    {
        $this->sortByField = $field;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     * @API
     */
    public function setIdField(string $field): self
    {
        $this->idField = $field;
        return $this;
    }

    /**
     * @param bool $visibility
     * @return $this
     * @API
     */
    public function setVisibility(bool $visibility): self
    {
        $this->visible = $visibility;
        return $this;
    }

    /**
     * @param bool $useId
     * @return $this
     * @API
     */
    public function setUseId(bool $useId = true): self
    {
        $this->useId = $useId;
        return $this;
    }

    /**
     * @param Enum\Sort\Direction $direction
     * @return $this
     * @API
     */
    public function setSortDirection(Enum\Sort\Direction $direction): self
    {
        $this->sortDirection = $direction;
        return $this;
    }

    /**
     * @param Enum\Sort\Type $type
     * @return $this
     * @API
     */
    public function setSortType(Enum\Sort\Type $type): self
    {
        $this->sortType = $type;
        return $this;
    }
}
