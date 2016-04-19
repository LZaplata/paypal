<?php
	namespace AdminModule;

	use WebLoader\Filter\VariablesFilter;

	use WebLoader\Nette\JavaScriptLoader;

	use WebLoader\Nette\CssLoader;

	use WebLoader\Compiler;

	use WebLoader\FileCollection;

	class BasePresenter extends SecuredPresenter {
		/** @var \WebLoader\Nette\LoaderFactory @inject */
		public $webLoader;

		/** @var \Nette\Database\Table\ActiveRow */
		public $settings;

		public function startup() {
			parent::startup();

			$this->template->settings = $this->settings = $this->model->getSettings()->fetch();
			$this->template->shopSettings = $this->model->getShopSettings()->fetch();

			if($this->settings->seo_assist || $this->settings->context_help) {
				$this->template->rightbar = true;
			} else {
				$this->template->rightbar = false;
			}
		}

		/** @return CssLoader */
		protected function createComponentCss () {
			return $this->webLoader->createCssLoader('admin');
		}

		/** @return JavaScriptLoader */
		protected function createComponentJs () {
			return $this->webLoader->createJavaScriptLoader('admin');
		}

		/**
		 * create breadcrumb component
		 * @return \AdminModule\Breadcrumb
		 */
		public function createComponentBreadcrumb ($name) {
			return new Breadcrumb($this, $name);
		}
	}