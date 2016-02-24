<?php
	namespace AdminEshopModule;

	use AdminModule\BasePresenter;
	use AdminModule\CategoriesHeurekaGrid;
	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;

	class SettingsHeurekaPresenter extends BasePresenter {
		public $urlID;
		public $categories;
		public $activeCategories;

		public function actionDefault () {
			$this->urlID = 0;

			if (!$this->isAjax()) {
				$this->categories = $this->getCategoriesFromXml();
			}

			$this->activeCategories = $this->model->getCategoriesHeureka();

			if (!$this['activeCategories']->getParameter('order')) {
				$this->redirect('this', array('activeCategories-order' => 'name ASC'));
			}
		}

		public function renderDefault () {
			$this->template->categories = $this->categories;
			$this->template->activeCategories = $this->activeCategories;
		}

		public function getCategoriesFromXml () {
			$content = file_get_contents('http://www.heureka.cz/direct/xml-export/shops/heureka-sekce.xml');
			$xml = new \SimpleXMLElement($content);

			return $xml->xpath('/HEUREKA/CATEGORY');
		}

		public function handleActivateCategory (array $category) {
			$values['heureka_id'] = $category['CATEGORY_ID'];

			if (!$this->model->getCategoriesHeureka()->where($values)->fetch()) {
				$values['name'] = $category['CATEGORY_NAME'];
				$values['name_full'] = $category['CATEGORY_FULLNAME'];

				$this->model->getCategoriesHeureka()->insert($values);
			}

			$this->invalidateControl('categories');
		}

		public function handleDeactivateCategory ($id) {
			$ids = (array)$id;

			$this->model->getCategoriesHeureka()->where('id', $ids)->delete();
			$this->model->getCategories()->where('categories_heureka_id', $ids)->update(array('categories_heureka_id' => null));

			$this->invalidateControl('categories');
		}

		public function createComponentHeurekaSetting(){
			$form = new Form();
			//$form->getElementPrototype()->addClass('form-horizontal');
			$form->addText('heurekaVerification','Klíč Oveření zákazníky:');
			$form->addText('heurekaConversion','Klíč Měření konverzí:');
			$form->addSubmit('submit','Nastavit');
			$form->onSuccess[] = $this->heurekaSetting;
			$form->setRenderer(new BootstrapFormRenderer());
			$form->setValues($this->vendorSettings);
			return $form;
		}

		public function heurekaSetting($form, $values){
			$this->vendorSettings->update($values);
			$this->redirect('this');
		}

		public function createComponentActiveCategories () {
			return new CategoriesHeurekaGrid($this->activeCategories);
		}
	}