<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Grid\Column;

use byteShard\Cell;
use byteShard\Enum;
use byteShard\Grid;
use byteShard\Internal\ClientData\EncryptedObjectValueInterface;
use byteShard\Internal\Grid\Column;
use byteShard\Locale;
use byteShard\Session;
use Exception;

/**
 * Class Image
 * @package byteShard\CellContent\Grid\Column
 */
class Image extends Column implements EncryptedObjectValueInterface
{
    protected string             $type           = 'image';
    protected string             $dhxTypeRw      = Grid\Enum\Type::IMAGE;
    protected string             $dhxTypeRo      = Grid\Enum\Type::IMAGE;
    protected string             $sort           = Grid\Enum\Sort::IMAGE;
    protected string             $align          = Grid\Enum\Align::CENTER;
    protected string             $filter         = Grid\Enum\Filter::COMBO_ADVANCED;
    protected int                $width          = 100;
    protected Enum\DB\ColumnType $db_column_type = Enum\DB\ColumnType::VARCHAR;
    /**
     * @var Grid\Cell\Image[]
     */
    protected array $metadata = [];

    /**
     * Image constructor.
     * @param string $dbField
     * @param string|null $name
     * @param int|null $width
     * @param int $accessType
     */
    public function __construct(string $dbField, ?string $name = null, ?int $width = null, int $accessType = Enum\AccessType::R)
    {
        parent::__construct($dbField, $name, $width, $accessType);
    }

    /**
     * @param Grid\Cell\Image ...$imageMaps
     * @return $this
     */
    public function setMetadata(Grid\Cell\Image ...$imageMaps): self
    {
        foreach ($imageMaps as $imageMap) {
            if (!in_array($imageMap, $this->metadata, true)) {
                $this->metadata[] = $imageMap;
            }
        }
        return $this;
    }

    /**
     * @param Cell $cell
     * @return array
     * @throws Exception
     */
    public function getImageMap(Cell $cell): array
    {
        $imageMap = [];
        foreach ($this->metadata as $image) {
            $events = $image->getEvents();
            $jsLink = false;
            if (!empty($events)) {
                $eventId = $cell->getEventIDForInteractiveObject($this->field.':'.$image->value, false);
                if ($eventId['registered'] === false) {
                    foreach ($events as $event) {
                        $actions = $event->getActionArray();
                        foreach ($actions as $action) {
                            $action->initActionInCell($cell);
                        }
                        $cell->setEventForInteractiveObject($eventId['name'], $event);
                        if ($event instanceof Grid\Event\OnLinkClick) {
                            $jsLink = true;
                        }
                    }
                }
                $imageMap[$image->value]['eventId'] = $eventId['name'];
            }
            if ($image->tooltip === null) {
                $tooltip                          = Locale::getArray($this->getLocaleBaseToken().'Cell.Image.'.$image->value.'.Tooltip');
                $imageMap[$image->value]['value'] = $tooltip['found'] === true ? $image->image.'^'.$tooltip['locale'] : $image->image;
            } else {
                $imageMap[$image->value]['value'] = $image->image.'^'.$image->tooltip;
            }
            $imageMap[$image->value]['encryptedValue'] = Session::encrypt($image->value, $cell->getNonce());
            $imageMap[$image->value]['jsLink']         = $jsLink;
        }
        return $imageMap;
    }
}
