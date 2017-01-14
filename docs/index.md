# Forms

Add ability to create forms in better way in [Nette Framework](http://nette.org/)

## Installation

The best way to install ipub/forms is using  [Composer](http://getcomposer.org/):

```sh
$ composer require ipub/forms
```

After that you have to register extension in config.neon.

```neon
extensions:
	extendedForms: IPub\Forms\DI\FormsExtension
```

## Configuration

In configuration neon you can define your forms with factories:

```neon
	extendedForms:
		yourFormName:
			class	: '\Your\Namespace\To\Form\Class'
			factory	: '\Your\Namespace\To\Form\Factory'
```

The first class should be your form class, eg. extended Nette\Application\UI\Form and the second factory class should be your factory to create UI form and should implement something like IPub\Forms\IFormFactory

This extension come with default two types of forms:

- Classic form
- Entity form based on [DoctrineForms](https://github.com/Kdyby/DoctrineForms) extensions

## Usage

You have several choices how to create forms. One is classic in Presenter or Component:

```php
class BasePresenter extends Nette\Application\UI\Presenter
{
	/**
	 * @inject
	 * @var \IPub\Forms\FormFactory
	 */
	public $formFactory;

	/**
	 * @inject
	 * @var \Path\To\Your\FormProcessor
	 */
	public $formProcessor;

	public function createComponentUserAccountForm()
	{
		// Create form with factory
		$form = $this->formFactory->create();

		// Now you can add form elements like in classic nette way
		$form->addText('username', 'Username');

		$form->addPassword('password', 'Password');

		$form->addText('name', 'Name');

		// And now you can assign form processor
		$form->addProcessor($this->formProcessor);
	}
}
```

## Forms processors

What are forms processors? Processors are services which have to handle all form events. This extension come with base form processor class to define the structure. Here you can see how to create your form processor:

```php
class YourFormProcessor extends IPub\Forms\Processors\FormProcessor
{
	/**
	 * @var YourModel
	 */
	protected $model;

	public function __construct(YourModel $mode)
	{
		$this->model = $model;
	}

	/**
	 * @param \Nette\Application\UI\Form $form
	 * @param \Nette\Utils\ArrayHash $values
	 */
	public function success(\Nette\Application\UI\Form $form, \Nette\Utils\ArrayHash $values)
	{
		if ($form->hasErrors()) {
			return;
		}

		try {
			$this->mode->save($values);

			// Store system message
			$form->getParent()->flashMessage("Item was successfully saved.", "success");

		} catch (\Exception $ex) {
			// Store system message
			$form->addError("Something went wrong....");
		}
	}
}
```

Of Course you have to define this class as a service in your neon file or in extension:

```neon
	services:
		- \Namespace\YourFormProcessor
```

So now you have created form by form factory and special form processor to handle all form events.