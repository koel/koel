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

    protected function els($selector)
    {
        return $this->driver->findElements(WebDriverBy::cssSelector($selector));
    }

    protected function type($string)
    {
        return $this->driver->getKeyboard()->sendKeys($string);
    }

    protected function typeIn($element, $string)
    {
        $this->click($element)->clear();

        return $this->type($string);
    }

    protected function press($key = WebDriverKeys::ENTER)
    {
        return $this->driver->getKeyboard()->pressKey($key);
    }

    protected function enter()
    {
        return $this->press();
    }

    protected function click($element)
    {
        return $this->el($element)->click();
    }

    protected function rightClick($element)
    {
        return $this->driver->getMouse()->contextClick($this->el($element)->getCoordinates());
    }

    protected function doubleClick($element)
    {
        $action = new WebDriverDoubleClickAction($this->driver->getMouse(), $this->el($element));

        $action->perform();
    }

    /**
     * Sleep (implicit wait) for some seconds.
     *
     * @param $seconds
     */
    protected function sleep($seconds)
    {
        $this->driver->manage()->timeouts()->implicitlyWait($seconds);
    }

    /**
     * Wait until a condition is met.
     *
     * @param     $func (closure|WebDriverExpectedCondition)
     * @param int $timeout
     *
     * @return mixed
     * @throws \Exception
     */
    protected function waitUntil($func, $timeout = 10)
    {
        return $this->driver->wait($timeout)->until($func);
    }

    public function waitUntilSeen($selector)
    {
        return $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
            WebDriverBy::cssSelector($selector)
        ));
    }

    public function waitUntilNotSeen($selector)
    {
        return $this->waitUntil(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::cssSelector($selector)
        ));
    }

    public function waitUntilTextSeenIn($text, $selector)
    {
        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector($selector), $text
        ));
    }

    protected function back()
    {
        return $this->driver->navigate()->back();
    }

    protected function forward()
    {
        return $this->driver->navigate()->forward();
    }

    protected function refresh()
    {
        return $this->driver->navigate()->refresh();
    }


}
