<?php
	namespace AdminEshopModule;

	use AdminModule\BasePresenter;
	use AdminModule\CategoriesZboziGrid;

	class SettingsZboziPresenter extends BasePresenter {
		public $urlID;
		public $categories;

		public function actionDefault () {
			$this->urlID = 0;
			$this->categories = $this->model->getCategoriesZbozi();

			if (!$this['categories']->getParameter('order')) {
				$this->redirect('this', array('categories-order' => 'name ASC'));
			}
		}

		public function renderDefault () {
			$this->template->categories = $this->categories;
		}

		public function handleDeleteCategory ($id) {
			$ids = (array)$id;

			$this->model->getCategoriesZbozi()->where('id', $ids)->delete();
			$this->model->getCategories()->where('categories_zbozi_id', $ids)->update(array('categories_zbozi_id' => null));

			$this->invalidateControl('categories');
		}

		public function createComponentCategories () {
			return new CategoriesZboziGrid($this->categories);
		}
	}