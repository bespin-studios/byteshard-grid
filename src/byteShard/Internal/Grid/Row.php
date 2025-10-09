<?php

namespace byteShard\Internal\Grid;

use byteShard\ID\RowID;
use byteShard\Internal\SimpleXML;
use SimpleXMLElement;

class Row
{
    private array        $userData   = [];
    private string       $encodedRowId;
    private string       $encryptedRowId;
    private int          $accessType;
    private bool         $selected   = false;
    private bool         $expanded   = false;
    private string       $style      = '';
    private string       $class      = '';
    private ?ColumnProxy $treeColumn = null;

    /**
     * @param array<string> $rowIdParts
     * @param array<ColumnProxy> $columnProxies
     */
    public function __construct(
        array                   $rowIdParts,
        string                  $nonce,
        private readonly object $record,
        private readonly array  $columnProxies,
        private readonly string $dataBinding,
        int                     $accessType,
        private array           &$localeCache,
        private array           $rowAttributes,
        private readonly int    $level = 1)
    {
        $rowIdArray = [];
        foreach ($rowIdParts as $rowIdIndex) {
            $rowIdArray[$rowIdIndex] = $record->{$rowIdIndex};
        }
        $rowId                = new RowID($rowIdArray);
        $this->encryptedRowId = $rowId->getEncryptedRowId($nonce);
        $this->encodedRowId   = $rowId->getEncodedRowId();
        $this->accessType     = isset($record->BSRowAccessType) ? min($accessType, (int)$record->BSRowAccessType) : $accessType;
        if (isset($this->rowAttributes[$this->encodedRowId])) {
            if (isset($this->rowAttributes[$this->encodedRowId]['style'])) {
                $this->style = $this->rowAttributes[$this->encodedRowId]['style'];
            }
            if (isset($this->rowAttributes[$this->encodedRowId]['class'])) {
                $this->class = $this->rowAttributes[$this->encodedRowId]['class'];
            }
        }
        foreach ($this->columnProxies as $columnProxy) {
            if ($columnProxy->isTreeColumn()) {
                $this->treeColumn = $columnProxy;
                break;
            }
        }
    }

    public function getEncodedRowId(): string
    {
        return $this->encodedRowId;
    }

    public function setStyle(string $style): void
    {
        $this->style = $style;
    }

    public function setSelected(): void
    {
        $this->selected = true;
    }

    public function setExpanded(): void
    {
        $this->expanded = true;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getColumns(): array
    {
        if (empty($this->columnProxies)) {
            return [null, null];
        }
        $result = [];

        if ($this->treeColumn !== null) {
            $this->treeColumn->setDataBinding($this->dataBinding);
        }

        foreach ($this->columnProxies as $columnProxy) {
            $result[$columnProxy->encryptedName] = $columnProxy->getCellValue($this->record, $this->encryptedRowId, $this->localeCache, $this->accessType);
        }
        return [$this->encryptedRowId, $result];
    }

    public function addRowToXml(?SimpleXMLElement $parentXmlObject): ?SimpleXMLElement
    {
        if ($parentXmlObject === null) {
            return null;
        }

        $row = $parentXmlObject->addChild('row');

        if ($row === null) {
            return null;
        }

        $row->addAttribute('id', $this->encryptedRowId);
        if ($this->selected === true) {
            $row->addAttribute('select', '1');
        }
        if ($this->expanded === true) {
            $row->addAttribute('open', '1');
        }
        if ($this->style !== '') {
            $row->addAttribute('style', htmlspecialchars_decode($this->style));
        } elseif (isset($this->record->BSStyle)) {
            $row->addAttribute('style', htmlspecialchars_decode($this->record->BSStyle));
        }
        if ($this->class !== '') {
            $row->addAttribute('class', htmlspecialchars_decode($this->class));
        } elseif (isset($this->record->BSClass)) {
            $row->addAttribute('class', htmlspecialchars_decode($this->record->BSClass));
        }

        // past implementation: ['exportColor'] = 0
        foreach ($this->userData as $name => $value) {
            $userData = SimpleXML::addChild($row, 'userdata', $value, null, true);
            if ($userData !== null) {
                SimpleXML::addAttribute($userData, 'name', $name, null, true);
            }
        }

        if ($this->treeColumn !== null) {
            $this->treeColumn->setDataBinding($this->dataBinding);
        }

        foreach ($this->columnProxies as $columnProxy) {
            $columnProxy->addColumnToXml($row, $this->record, $this->encryptedRowId, $this->localeCache, $this->accessType);
        }

        return $row;
    }
}