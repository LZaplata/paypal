<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class VersionsGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
						
			$this->addColumn('date', 'Datum')
				->setRenderer(function ($row) {
					return $row['date']->format('j. n. Y G:i');
				})
				->setSortable(false);
				
			$this->addColumn('email', 'Uložil')
				->setSortable(false);
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			$this->addButton('show', 'Zobrazit verzi')
				->setClass('fa fa-binoculars')
				->setLink(function ($row) use ($self) {
					return $self->presenter->link('this', array('version' => $row['id']));
				})
				->setAjax(false);
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteVersion!', array($self->presenter->id, $row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit vybranou verzi?";})
				->filterRows(function ($row) use ($self) {					
					if (($self->presenter->presenterName == 'Products' && $row['id'] != $row['products_id']) || ($self->presenter->presenterName != 'Products' && $row['pid'] != $row['id'])) {
						$resource = $row['users_id'] == $self->presenter->user->id ? 'ownPost' : 'post';
						
						return $self->presenter->acl->isAllowed($self->presenter->user->identity->role, $resource, 'delete');
					}
					return false;
				})
				->setAjax(false);
			
// 			$this->addAction("delete","Smazat")
// 				->setCallback(function($id) use ($self){return $self->presenter->handleDelete($id);})
// 				->setConfirmationDialog("Opravdu smazat všechny vybrané uživatele?");
		}
	}