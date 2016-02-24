<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class CategoriesHeurekaGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název');
			
			$this->addColumn('name_full', 'Celý název');
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
				
			$this->addButton('deactivate', 'Deaktivovat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeactivateCategory!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu deaktivovat?";});
			
			$this->addAction("deactivate","Deaktivovat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeactivateCategory($id);})
				->setConfirmationDialog("Opravdu deaktivovat všechny vybrané kategorie?");			

			$this->setRowFormCallback(function ($values) use ($self) {
				if (isset($values['id'])) {
					$self->presenter->editLang($values);
				}
				else {
					if (!empty($values['key'])) {
						$self->presenter->addLang($values);
					}
					else {
						$self->presenter->flashMessage('Musíte vyplnit zkratku jazyka');
					}
				}
			});
		}
	}