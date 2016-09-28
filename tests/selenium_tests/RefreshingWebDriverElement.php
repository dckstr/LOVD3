<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2016-09-27
 * Modified    : 2016-09-29
 * For LOVD    : 3.0-18
 *
 * Copyright   : 2016 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : M. Kroon <m.kroon@lumc.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/


use \Facebook\WebDriver\Exception\StaleElementReferenceException;
use \Facebook\WebDriver\WebDriverBy;
use \Facebook\WebDriver\WebDriver;


class RefreshingWebElement {

    // Wrapped webdriver element.
    protected $element;

    // WebDriver instance used for refreshing the element.
    protected $driver;

    // Locator used to generate $element (of WebDriverBy type).
    protected $locator;


    public function __construct(WebDriver $driver, WebDriverBy $locator)
    {
        $this->driver = $driver;
        $this->locator = $locator;
        $this->element = $driver->findElement($locator);
    }


    public function __call($name, $args)
    {
        $subMethod = array($this->element, $name);

        switch ($name) {
            case 'clear':
            case 'click':
            case 'getText':
            case 'sendKeys':
                $e = null;
                for ($i = 0; $i < MAX_TRIES_STALE_REFRESH; $i++) {
                    try {
                        return call_user_func_array($subMethod, $args);
                    } catch (StaleElementReferenceException $e) {
                        // Refresh the element so we can try again.
                        $this->refresh();
                    }
                }
                // Too many tries, rethrow the exception.
                throw $e;
                break;
            default:
                // For methods where we don't expect stale element references, just invoke the
                // method once.
                return call_user_func_array($subMethod, $args);
        }
    }


    private function refresh()
    {
        // Refresh this element by re-running findElement() using the locator.
        fwrite(STDERR, 'Refreshing element, locator = "' . $this->locator->getValue() . '" (' .
                       $this->locator->getMechanism() . ')' . PHP_EOL);
        $this->element = $this->driver->findElement($this->locator);
    }


    public function getWebElement()
    {
        return $this->element;
    }
}


