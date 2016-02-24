<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class AccountsGrid extends Grid {
		public $data;
		public $data2;
		public $pid;
		
		public function __construct($data, $pid = 0) {
			parent::__construct();
			
			$this->data = $data;
			$this->pid = $pid;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('email', 'E-mail', '29%')
				->setTextEditable()
				->setTextFilter();
			
			$this->addColumn('name', 'Jméno', '29%')
				->setTextEditable()
				->setTextFilter();
			
			$this->addColumn('surname', 'Příjmení','30%')
				->setTextEditable()
				->setTextFilter();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			if ($self->presenter->acl->isAllowed($self->presenter->user->identity->role, 'post', 'edit')) {
				$this->addButton(Grid::ROW_FORM, "Rychlá editace")
					->setClass("fa fa-pencil-square-o");
				
				if ($self->presenter->presenterName == 'Accounts') {
					$this->addButton('edit', 'Editovat')
						->setClass('fa fa-pencil')
						->setLink(function($row) use ($self){return $self->presenter->link('Accounts:edit', array($row['id']));})
						->setAjax(false);
				
					$this->addButton('privileges', 'Práva')
						->setClass('fa fa-lock')
						->setLink(function($row) use ($self){return $self->presenter->link('Accounts:privileges', array($row['id']));})
						->filterRows(function($row){
							return $row['role'] != 'admin' && $row['role'] != 'user';
						})
						->setAjax(false);
				}
				
				$this->addButton('delete', 'Smazat')
					->setClass('fa fa-trash text-danger')
					->setLink(function($row) use ($self){
						if ($self->presenter->presenterName == 'Accounts') {
							return $self->presenter->link('Delete!', array($row['id']));
						}
						else return $self->presenter->link('Logout!', array($row['id']));
					})
					->setConfirmationDialog(function($row){return "Opravdu odstranit uživatele $row[email]?";});
				
				$this->addAction("delete","Smazat")
					->setCallback(function($id) use ($self){
						if ($self->presenter->presenterName == 'Accounts') {
							return $self->presenter->handleDelete($id);
						}
						else return $self->presenter->handleLogout($id);
					})
					->setConfirmationDialog("Opravdu smazat všechny vybrané uživatele?");
				
			}			

			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id']);
			
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$row->update($values);
			});
		}
	}