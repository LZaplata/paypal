<?php
	namespace FrontModule;

	use Nette\Latte\Engine;

	class MailingPresenter extends \BasePresenter {
		public $email;
		
		public function actionView ($id) {
			$this->email = $this->model->getEmails()->wherePrimary($id)->fetch();
		}
		
		public function renderView () {
			$template = $this->createTemplate();
			$template->setFile(APP_DIR.'/FrontModule/templates/Mailing/layout.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->host = $this->context->parameters['host'];
			$template->email = $this->email;
			$template->contents = $this->email->related('emails_content')->order('position ASC');
			$template->editors = $this->model->getEditors();
			$template->model = $this->model;
			
			$this->template->html = $template;
		}
	}