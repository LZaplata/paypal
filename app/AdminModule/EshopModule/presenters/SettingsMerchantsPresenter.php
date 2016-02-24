<?php
	namespace AdminEshopModule;

	use AdminModule\BasePresenter;
	use AdminModule\CategoriesMerchantsGrid;
	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use LazyTaxonomyReader;

	class SettingsMerchantsPresenter extends BasePresenter {
		public $urlID;
		public $categories;
		public $activeCategories;
		public $reader;

		public function actionDefault () {
			$this->urlID = 0;

			if (!$this->isAjax()) {
				//$this->categories = $this->getCategoriesFromTxt();
				$this->categories = $this->getCategoriesFromXml();
			}

			$this->activeCategories = $this->model->getCategoriesMerchants();

			if (!$this['activeCategories']->getParameter('order')) {
				$this->redirect('this', array('activeCategories-order' => 'name ASC'));
			}
		}

		public function renderDefault () {
			$this->template->categories = $this->categories;
			$this->template->activeCategories = $this->activeCategories;
		}

		public function getCategoriesFromXml () {
			$content = file_get_contents('http://cdn.hucr.cz/google-merchants/taxonomy.xml');
			$xml = new \SimpleXMLElement($content);

			return $xml->xpath('/GOOGLE_MERCHANTS/CATEGORY');
		}

		/*
		public function getCategoriesFromTxt () {
			$this->reader = new LazyTaxonomyReader('http://www.google.com/basepages/producttype/taxonomy-with-ids.cs-CZ.txt');

			return $this->getTaxonomySubtree();
		}

		protected function getTaxonomySubtree($line = null) {
			$temp = array();

			foreach ($this->reader->getDirectDescendants($line) as $key => $category) {
				$obj = array();
				$obj['CATEGORY_ID'] = $key;
				$obj['CATEGORY_NAME'] = $this->reader->getLastNode($category);
				$obj['CATEGORY_FULLNAME'] = $category;
				$obj['CATEGORY'] = $this->getTaxonomySubtree($key);
				if(sizeof($obj['CATEGORY']) == 0) unset($obj['CATEGORY']);

				$temp[] = (object) $obj;
			}

			return $temp;
		}
		*/

		public function handleActivateCategory (array $category) {
			$values['merchants_id'] = $category['CATEGORY_ID'];

			if (!$this->model->getCategoriesMerchants()->where($values)->fetch()) {
				$values['name'] = $category['CATEGORY_NAME'];
				$values['name_full'] = $category['CATEGORY_FULLNAME'];

				$this->model->getCategoriesMerchants()->insert($values);
			}

			$this->invalidateControl('categories');
		}

		public function handleDeactivateCategory ($id) {
			$ids = (array)$id;

			$this->model->getCategoriesMerchants()->where('id', $ids)->delete();
			$this->model->getCategories()->where('categories_merchants_id', $ids)->update(array('categories_merchants_id' => null));

			$this->invalidateControl('categories');
		}

		public function createComponentMerchantsSetting(){
			$form = new Form();
			$form->getElementPrototype()->addClass('form-horizontal');
			$form->addText('merchantsVerification','Klíč Oveření:');
			$form->addText('merchantsConversion','Klíč Měření konverzí:');
			$form->addSubmit('submit','Nastavit');
			$form->onSuccess[] = $this->merchantsSetting;
			$form->setRenderer(new BootstrapFormRenderer());
			$form->setValues($this->vendorSettings);
			return $form;
		}

		public function merchantsSetting($form,$values){
			$this->vendorSettings->update($values);
			$this->redirect('this');
		}

		public function createComponentActiveCategories () {
			return new CategoriesMerchantsGrid($this->activeCategories);
		}
	}