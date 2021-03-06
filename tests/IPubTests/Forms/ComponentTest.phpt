<?php
/**
 * Test: IPub\Forms\Compiler
 * @testCase
 *
 * @copyright      More in license.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:Forms!
 * @subpackage     Tests
 * @since          1.0.0
 *
 * @date           30.01.15
 */

declare(strict_types = 1);

namespace IPubTests\Forms;

use Nette;
use Nette\Application;
use Nette\Application\Routers;
use Nette\Application\UI;
use Nette\Utils;

use Tester;
use Tester\Assert;

use IPub\Forms;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

class ComponentTest extends Tester\TestCase
{
	/**
	 * @var Application\IPresenterFactory
	 */
	private $presenterFactory;

	/**
	 * @var Nette\DI\Container
	 */
	private $container;

	/**
	 * @var string
	 */
	private $doVar = '_do';

	/**
	 * @return array
	 */
	public function dataFormValues() : array
	{
		return [
			['John Doe', 'jdoe', '123456'],
			['Jane Doe', 'janedoe', '657987'],
			['Tester', 'someusername', NULL],
		];
	}

	/**
	 * @return array
	 */
	public function dataFormInvalidValues() : array
	{
		return [
			['John Doe', NULL, '123456', 'This field is required.'],
			[NULL, 'username', '123456', 'User full name is required.'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function setUp() : void
	{
		parent::setUp();

		$this->container = $this->createContainer();

		// Get presenter factory from container
		$this->presenterFactory = $this->container->getByType(Application\IPresenterFactory::class);

		$version = getenv('NETTE');

		if ($version !== 'default') {
			$this->doVar = 'do';
		}
	}

	public function testCreatingForm() : void
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'GET', ['action' => 'default']);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		$dq = Tester\DomQuery::fromHtml((string) $response->getSource());

		Assert::true($dq->has('input[name="username"]'));
		Assert::true($dq->has('input[name="password"]'));
		Assert::true($dq->has('input[name="name"]'));
	}

	/**
	 * @dataProvider dataFormValues
	 *
	 * @param string $name
	 * @param string $username
	 * @param string|NULL $password
	 */
	public function testProcessingForm(string $name, string $username, ?string $password) : void
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'POST', ['action' => 'process'], [
			$this->doVar => 'userForm-submit',
			'name'       => $name,
			'username'   => $username,
			'password'   => $password
		]);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		Assert::equal('Username:' . $username . '|Password:' . $password . '|Name:' . $name, (string) $response->getSource());
	}

	/**
	 * @dataProvider dataFormInvalidValues
	 *
	 * @param string|NULL $name
	 * @param string|NULL $username
	 * @param string $password
	 * @param string $expected
	 */
	public function testInvalidProcessingForm(?string $name, ?string $username, string $password, string $expected) : void
	{
		// Create test presenter
		$presenter = $this->createPresenter();

		// Create GET request
		$request = new Application\Request('Test', 'POST', ['action' => 'process'], [
			$this->doVar => 'userForm-submit',
			'name'       => $name,
			'username'   => $username,
			'password'   => $password
		]);
		// & fire presenter & catch response
		$response = $presenter->run($request);

		Assert::equal($expected, (string) $response->getSource());
	}

	/**
	 * @return Application\IPresenter
	 */
	protected function createPresenter() : Application\IPresenter
	{
		// Create test presenter
		$presenter = $this->presenterFactory->createPresenter('Test');
		// Disable auto canonicalize to prevent redirection
		$presenter->autoCanonicalize = FALSE;

		return $presenter;
	}

	/**
	 * @return Nette\DI\Container
	 */
	protected function createContainer() : Nette\DI\Container
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		Forms\DI\FormsExtension::register($config);

		$config->addConfig(__DIR__ . DS . 'files' . DS . 'presenters.neon');

		return $config->createContainer();
	}
}

class TestPresenter extends UI\Presenter
{
	/**
	 * @var Forms\IFormFactory
	 */
	protected $factory;

	/**
	 * @var string|NULL
	 */
	private $testResult;

	/**
	 * @return void
	 */
	public function renderDefault() : void
	{
		// Set template for component testing
		$this->template->setFile(__DIR__ . DS . 'templates' . DS . 'default.latte');
	}

	/**
	 * @return void
	 */
	public function renderProcess() : void
	{
		$this->sendResponse(new Application\Responses\TextResponse($this->testResult));
	}

	/**
	 * @param Forms\IFormFactory $factory
	 *
	 * @return void
	 */
	public function injectFormFactory(Forms\IFormFactory $factory) : void
	{
		$this->factory = $factory;
	}

	/**
	 * Create confirmation dialog
	 *
	 * @return UI\Form
	 */
	protected function createComponentUserForm() : UI\Form
	{
		// Init form object
		$form = $this->factory->create(UI\Form::class);

		$form->addText('username', 'Username')
			->setRequired('This field is required.');

		$form->addPassword('password', 'Password');

		$form->addText('name', 'Name')
			->setRequired('User full name is required.');

		$form->onSuccess[] = [$this, 'formSuccess'];
		$form->onError[] = [$this, 'formError'];

		return $form;
	}

	/**
	 * @return void
	 */
	public function formSuccess(UI\Form $form) : void
	{
		$values = $form->getValues();

		$this->testResult = 'Username:' . $values->username . '|Password:' . $values->password . '|Name:' . $values->name;
	}

	/**
	 * @return void
	 */
	public function formError(UI\Form $form) : void
	{
		foreach ($form->getErrors() as $error) {
			$this->testResult = $error;

			break;
		}
	}
}

class RouterFactory
{
	/**
	 * @return Application\IRouter
	 */
	public static function createRouter() : Application\IRouter
	{
		$router = new Routers\RouteList;
		$router[] = new Routers\Route('<presenter>/<action>[/<id>]', 'Test:default');

		return $router;
	}
}

\run(new ComponentTest());
