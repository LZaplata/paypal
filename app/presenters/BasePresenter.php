<?php
	use Nette\Mail\IMailer;

	use WebLoader\Filter\LessFilter;

	use WebLoader\Nette\JavaScriptLoader;

	use AdminModule\Breadcrumb;

	use WebLoader\Nette\CssLoader;
	use WebLoader\Compiler;

	use Nette\Utils\Finder;

	use WebLoader\FileCollection;

	use Latte\Macros\MacroSet;
	use Latte\Engine;

	/**
	 * Základní presenter pro všechny ostatní presentery
	 */

	abstract class BasePresenter extends Nette\Application\UI\Presenter {
		/** @persistent */
		public $lang;

		/** @persistent */
		public $currency;

		/** @var \LiveTranslator\Translator @inject */
		public $translator;

		/** @var App\Model\Db @inject */
		public $model;

		/** @var App\Model\Orders @inject */
		public $ordersModel;

		/** @var App\Model\Products @inject */
		public $productsModel;

		/** @var App\Model\Logs @inject */
		public $logsModel;

		/** @var App\Model\Pages @inject */
		public $pagesModel;

		public $layouts;
		public $layoutsBlocks;
		public $layoutsBooking;
		public $countModulesLayouts;
		public $countModulesDetailLayouts;
		public $langs;
		public $presenterName;
		public $moduleName;
		public $transport;
		public $payment;
		public $contact;
		public $orderStates;
		public $lastEdited;
		public $mailer;
		public $vendorSettings;
		public $partner;

		public function startup(){
			parent::startup();

			if ($this->context->container->parameters['consoleMode']) {
				$this->model = $this->context->getService('model');
			}

			$this->layouts = $this->context->parameters['layouts'];
			$this->layoutsBlocks = $this->context->parameters['layoutsBlocks'];
			$this->layoutsBooking = $this->context->parameters['layoutsBooking'];
			$this->countModulesLayouts = $this->context->parameters['countModulesLayouts'];
			$this->countModulesDetailLayouts = $this->context->parameters['countModulesDetailLayouts'];
			$this->contact = (object) $this->context->parameters['contact'];
			$this->orderStates = $this->context->parameters['orderStates'];

			$this->langs = $this->model->getLanguages()->order('position ASC');

			$langs = array();
			foreach ($this->langs as $lang) {
				$langs['_'.$lang->key] = "nplurals=2; plural=(n==1) ? 0 : 1;";
			}

			$this->translator->setAvailableLanguages(array_merge(array('cs' => 'nplurals=3; plural=((n==1) ? 0 : (n>=2 && n<=4 ? 1 : 2));'), $langs));


			preg_match('/(.*):(.*)/', $this->presenter->name, $names);
			$this->presenterName = isset($names[2]) ? $names[2] : "";
			$this->moduleName = isset($names[1]) ? $names[1] : "";

			$this->vendorSettings = $this->template->vendorSettings = $this->model->getSettings()->fetch();

			$this->lastEdited = $this->session->getSection('rows');

			$this->partner = $this->session->getSection("partner");
			$this->partner->setExpiration("15 minutes");

			if (($partner = $this->getParameter("partner"))) {
				$this->partner->id = $partner;
				$this->partner->beg = $this->getParameter("beg");
				$this->partner->fta = $this->getParameter("fta");
			}
		}

		public function beforeRender() {
			$this->template->today = time();
			$this->template->hostName = $_SERVER['HTTP_HOST'];

			if ($this->isAjax()) {
				$this->invalidateControl('flashMessages');
			}

			$this->lang = $this->getParameter('lang');
		}

		public function injectMailer (IMailer $mailer) {
			$this->mailer = $mailer;
		}

		/**
		 * Akce odhlášení
		 */
		public function handleSignOut() {
			$this->getUser()->logout();
			$this->flashMessage('Odhlášení proběhlo v pořádku!');

			if ($this->moduleName == "Admin" || $this->moduleName == "AdminEshop" || $this->moduleName == "AdminBooking") {
				$this->redirect(':Admin:Homepage:');
			}
			else {
				$order = $this->context->session->getSection('order');
				$order->remove();

// 				$this->redirect(':FrontEshop:Homepage:');
				$this->redirectUrl($this->defaultLink(':Front:Page:'));
			}
		}

		public function formatCurrent ($array) {
			foreach ($array as $key => $val) {
				if (preg_match('/(.*):(.*):(.*) (.*)/', $val, $params)) {
					$array[$key] = $params[1].":".($params[2] == '*' ? $this->presenterName : $params[2]).":".($params[3] == '*' ? $this->action : $params[3])." ".$params[4];
				}
				elseif (preg_match('/(.*):(.*):(.*)/', $val, $params)) {
					$array[$key] = $params[1].":".($params[2] == '*' ? $this->presenterName : $params[2]).":".($params[3] == '*' ? $this->action : $params[3])." ".$this->presenter->urlID;
				}
				elseif (preg_match('/(.*):(.*) (.*)/', $val, $params)) {
					$array[$key] = $this->moduleName.":".$params[1].":".($params[2] == '*' ? $this->action : $params[2])." ".$params[3];
				}
				elseif (preg_match('/(.*):(.*)/', $val, $params)) {
					$array[$key] = $this->moduleName.":".$params[1].":".($params[2] == '*' ? $this->action : $params[2])." ".$this->presenter->urlID;
				}
			}

			return $array;
		}

		public function setLastEdited($table) {
			if ($this->lastEdited->table != $table) {
				$this->lastEdited->rows = array();
				$this->lastEdited->table = $table;
			}
		}

		protected function createTemplate($class = NULL)
		{
			$template = parent::createTemplate($class);

			$template->setTranslator($this->translator);
			$template->addFilter('nbsp', function ($s) {
				return Helpers::nbsp($s);
			});
			$template->addFilter('ago', function ($time) {
				return Helpers::ago($time);
			});
// 			$template->addFilter('nopersist', function ($s) {
// 				return Helpers::nopersist($s);
// 			});

			return $template;
		}

		// to have translated even forms add this method too
		protected function createComponent($name)
		{
			$component = parent::createComponent($name);
			if ($component instanceof \Nette\Forms\Form) {
				$component->setTranslator($this->translator);
			}
			return $component;
		}

		public function defaultLink ($destination, $parameters=array()) {
			return Macros::defaultLink($this, $destination, $parameters);
		}
	}