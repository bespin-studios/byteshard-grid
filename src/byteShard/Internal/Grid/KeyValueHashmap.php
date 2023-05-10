<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

/**
 * Trait KeyValueHashmap
 * @package byteShard\Internal\Grid
 */
trait KeyValueHashmap
{
    public array $keyValueHashmap = [];

    public function initializeKeyValueHashmap(): void
    {
        if (isset($this->readOnlyKeyValueColumn)) {
            $this->readOnlyKeyValueColumn = true;
        }
        if (isset($this->comboOptions, $this->comboOptions['options']) && is_array($this->comboOptions['options']) && !empty($this->comboOptions['options'])) {
            foreach ($this->comboOptions['options'] as $option) {
                if (isset($option['text'], $option['value'])) {
                    $this->keyValueHashmap[$option['value']] = $option['text'];
                }
            }
        }
    }
}
