<?php

namespace E2E;

use App\Application;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    use WebDriverShortcuts;

    /**
     * @var Application
     */
    protected $app;

    /**
     * ID of the current screen wrapper (with leading #).
     *
     * @var string
     */
    public $wrapperId;

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
        $this->resetData();
        $this->driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', DesiredCapabilities::chrome());
        $this->driver->manage()->window()->setSize(new WebDriverDimension(1440, 900));
        $this->driver->manage()->window()->setPosition(new WebDriverPoint(0, 0));
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

    /**
     * Reset the test data for E2E tests.
     */
    protected function resetData()
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

    /**
     * Log into Koel.
     *
     * @param string $username
     * @param string $password
     *
     * @return $this
     */
    protected function login($username = 'koel@example.com', $password = 'SoSecureK0el')
    {
        $this->typeIn("#app > div.login-wrapper > form > [type='email']", $username);
        $this->typeIn("#app > div.login-wrapper > form > [type='password']", $password);
        $this->enter();

        return $this;
    }

    /**
     * Log in and wait for the app to finish loading.
     *
     * @throws \Exception
     *
     * @return $this
     */
    protected function loginAndWait()
    {
        $this->login()
            ->seeText('Koel Admin', '#userBadge > a.view-profile');

        return $this;
    }

    /**
     * Go to a specific screen.
     *
     * @param $screen
     *
     * @throws \Exception
     *
     * @return $this
     */
    protected function goto($screen)
    {
        $this->wrapperId = "#{$screen}Wrapper";

        if ($screen === 'favorites') {
            $this->click('#sidebar .favorites a');
        } else {
            $this->click("#sidebar a.$screen");
        }

        $this->see($this->wrapperId);

        return $this;
    }

    /**
     * Log in and go to a specific screen.
     *
     * @param $screen string
     *
     * @throws \Exception
     *
     * @return $this
     */
    protected function loginAndGoTo($screen)
    {
        return $this->loginAndWait()->goto($screen);
    }

    /**
     * Wait for the user to press ENTER key before continuing.
     */
    protected function waitForUserInput()
    {
        if (trim(fgets(fopen('php://stdin', 'rb'))) !== chr(13)) {
            return;
        }
    }

    protected function focusIntoApp()
    {
        $this->click('#app');
    }

    public function setUp()
    {
        $this->driver->get($this->url);
    }

    public function tearDown()
    {
        $this->driver->quit();
    }
}
