<?php

namespace E2E;

use Facebook\WebDriver\Interactions\Internal\WebDriverDoubleClickAction;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

trait WebDriverShortcuts
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;

    /**
     * @param $selector WebDriverElement|string
     *
     * @return WebDriverElement
     */
    protected function el($selector)
    {
        if (is_string($selector)) {
            return $this->driver->findElement(WebDriverBy::cssSelector($selector));
        }

        return $selector;
    }

    /**
     * Get a list of elements by a selector.
     *
     * @param $selector
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement[]
     */
    protected function els($selector)
    {
        return $this->driver->findElements(WebDriverBy::cssSelector($selector));
    }

    /**
     * Type a string.
     *
     * @param $string
     *
     * @return $this
     */
    protected function type($string)
    {
        $this->driver->getKeyboard()->sendKeys($string);

        return $this;
    }

    /**
     * Type into an element.
     *
     * @param $element
     * @param $string
     *
     * @return $this
     */
    protected function typeIn($element, $string)
    {
        $this->click($element)->clear();

        return $this->type($string);
    }

    /**
     * Press a key.
     *
     * @param string $key
     *
     * @return $this
     */
    protected function press($key = WebDriverKeys::ENTER)
    {
        $this->driver->getKeyboard()->pressKey($key);

        return $this;
    }

    /**
     * Press enter.
     *
     * @return $this
     */
    protected function enter()
    {
        return $this->press();
    }

    /**
     * Click an element.
     *
     * @param $element
     *
     * @return WebDriverElement
     */
    protected function click($element)
    {
        return $this->el($element)->click();
    }

    /**
     * Right-click an element.
     *
     * @param $element
     *
     * @return \Facebook\WebDriver\Remote\RemoteMouse
     */
    protected function rightClick($element)
    {
        return $this->driver->getMouse()->contextClick($this->el($element)->getCoordinates());
    }

    /**
     * Double-click and element.
     *
     * @param $element
     *
     * @return $this
     */
    protected function doubleClick($element)
    {
        (new WebDriverDoubleClickAction($this->driver->getMouse(), $this->el($element)))->perform();

        return $this;
    }

    /**
     * Sleep (implicit wait) for some seconds.
     *
     * @param $seconds
     *
     * @return $this
     */
    protected function sleep($seconds)
    {
        $this->driver->manage()->timeouts()->implicitlyWait($seconds);

        return $this;
    }

    /**
     * Wait until a condition is met.
     *
     * @param     $func    (closure|WebDriverExpectedCondition)
     * @param int $timeout
     *
     * @throws \Exception
     *
     * @return $this
     */
    protected function waitUntil($func, $timeout = 10)
    {
        $this->driver->wait($timeout)->until($func);

        return $this;
    }

    /**
     * Wait and validate an element to be visible.
     *
     * @param $selector
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function see($selector)
    {
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector($selector)
        ));

        return $this;
    }

    /**
     * Wait and validate an element to be invisible.
     *
     * @param $selector string The element's CSS selector.
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function notSee($selector)
    {
        $this->waitUntil(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::cssSelector($selector)
        ));

        return $this;
    }

    /**
     * Wait and validate a text to be visible in an element.
     *
     * @param $text
     * @param $selector string The element's CSS selector.
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function seeText($text, $selector)
    {
        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector($selector), $text
        ));

        return $this;
    }

    /**
     * Navigate back.
     *
     * @return $this
     */
    protected function back()
    {
        $this->driver->navigate()->back();

        return $this;
    }

    /**
     * Navigate forward.
     *
     * @return $this
     */
    protected function forward()
    {
        $this->driver->navigate()->forward();

        return $this;
    }

    /**
     * Refresh the page.
     *
     * @return $this
     */
    protected function refresh()
    {
        $this->driver->navigate()->refresh();

        return $this;
    }
}
