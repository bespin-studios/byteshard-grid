<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Grid;

use byteShard\Locale;
use byteShard\Settings;
use DateTimeZone;

class CalColumn extends Column
{
    private string       $serverDateFormat;
    private string       $clientDateFormat;
    private bool         $displayTime = false;
    private DateTimeZone $serverTimeZone;
    private DateTimeZone $clientTimeZone;

    /**
     * set the server date format for this object. To override the server date format for all objects, override the static method getDateFormat in the App Environment
     * @param string $format
     * @return $this
     */
    public function setServerDateFormat(string $format): self
    {
        $this->serverDateFormat = $format;
        return $this;
    }

    public function getServerDateFormat(): string
    {
        if (isset($this->serverDateFormat)) {
            return $this->serverDateFormat;
        }
        return (Settings::getDateFormat(get_class($this)));
    }

    /**
     * set the date/time format for this object. To override the format for all objects, update the app locale
     * @param string $format
     * @return $this
     */
    public function setClientDateFormat(string $format): self
    {
        $this->clientDateFormat = $format;
        return $this;
    }

    public function getClientDateFormat(): string
    {
        if (isset($this->clientDateFormat)) {
            return $this->clientDateFormat;
        }
        if ($this->displayTime === true) {
            $localTimeFormat = Locale::getArray('byteShard::date::grid.date_time.client');
            return $localTimeFormat['found'] === true ? $localTimeFormat['locale'] : 'Y-m-d H:i:s';
        }
        $localTimeFormat = Locale::getArray('byteShard::date::grid.date.client');
        return $localTimeFormat['found'] === true ? $localTimeFormat['locale'] : 'Y-m-d';
    }

    /**
     * enables or disables if the time is displayed in the cell
     * @param bool $displayTime
     * @return $this
     */
    public function setDisplayTime(bool $displayTime = true): self
    {
        $this->displayTime = $displayTime;
        return $this;
    }

    /**
     * @param DateTimeZone $timeZone
     * @return $this
     */
    public function setServerTimeZone(DateTimeZone $timeZone): self
    {
        $this->serverTimeZone = $timeZone;
        return $this;
    }

    public function getServerTimeZone(): ?DateTimeZone
    {
        return $this->serverTimeZone ?? null;
    }

    /**
     * @param DateTimeZone $timeZone
     * @return $this
     */
    public function setClientTimeZone(DateTimeZone $timeZone): self
    {
        $this->clientTimeZone = $timeZone;
        return $this;
    }

    public function getClientTimeZone(): ?DateTimeZone
    {
        return $this->clientTimeZone ?? null;
    }
}
