<?php

namespace E2E;

use App\Application;
use Facebook\WebDriver\Interactions\Internal\WebDriverDoubleClickAction;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverPoint;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoteWebDriver
     */
    protected $driver;

    /**
     * @var Application
     */
    protected $app;

    /**
     * The default Koel URL for E2E (server by `php artisan serve --port=8081`).
     *
     * @var string
     */
    protected $url = 'http://localhost:8081';
    protected $coverPath;

    /**
     * TestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createApp();
        $this->prepareForE2E();
        $this->driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', DesiredCapabilities::chrome());
        $this->driver->manage()->window()->setPosition(new WebDriverPoint(0, 0))
            ->setSize(new WebDriverDimension(1440, 900));
    }

    /**
     * @return Application
     */
    protected function createApp()
    {
        $this->app = require __DIR__.'/../../bootstrap/app.php';
        $this->app->make(Kernel::class)->bootstrap();

        return $this->app;
    }

    private function prepareForE2E()
    {
        // Make sure we have a fresh database.
        @unlink(__DIR__.'/../../database/e2e.sqlite');
        touch(__DIR__.'/../../database/e2e.sqlite');

        Artisan::call('migrate');
        Artisan::call('db:seed');
        Artisan::call('db:seed', ['--class' => 'E2EDataSeeder']);

        if (!file_exists($this->coverPath)) {
            @mkdir($this->coverPath, 0777, true);
        }
    }

    public function setUp()
    {
        $this->driver->get($this->url);
    }

    public function tearDown()
    {
        //$this->driver->quit();
    }

    protected function el($selector)
    {
        return $this->driver->findElement(WebDriverBy::cssSelector($selector));
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
        if (is_string($element)) {
            $element = $this->el($element);
        }
        $element->click()->clear();

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

    protected function doubleClick($element)
    {
        if (is_string($element)) {
            $element = $this->el($element);
        }
        $action = new WebDriverDoubleClickAction($this->driver->getMouse(), $element);

        return $action->perform();
    }

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

    /**
     * Log into Koel.
     *
     * @param string $username
     * @param string $password
     */
    protected function login($username = 'koel@example.com', $password = 'SoSecureK0el')
    {
        $this->typeIn("#app > div.login-wrapper > form > [type='email']", $username);
        $this->typeIn("#app > div.login-wrapper > form > [type='password']", $password);
        $this->enter();
    }

    /**
     * A helper to allow going to a specific screen.
     *
     * @param $screen
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    protected function goTo($screen)
    {
        if ($screen === 'favorites') {
            return $this->click('#sidebar .favorites a');
        } else {
            return $this->click("#sidebar a.$screen");
        }
    }

    protected function clearQueue()
    {
        $this->goTo('queue');
        if ($this->els('#queueWrapper .song-item')) {
            $this->click('#queueWrapper > h1 > div > button.btn.btn-red');
        }
    }
}
