<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class MailingContentGrid extends Grid {
		public $data;
		public $module;
		public $modules;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
			$this->modules = array(1 => 'Textový editor', 2 => 'Články', 3 => 'E-shop');
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('position', '', '20px')
				->setTextEditable();
			
			$this->addColumn('name', 'Název', '41%')
				->setTextFilter()
				->setRenderer(function ($row) use ($self) {	
					if ($row['editors_id']) {
						$this->module = $this->modules[1];
						
						return $self->presenter->model->getEditors()->wherePrimary($row['editors_id'])->fetch()->name;
					}	
					elseif ($row['articles_id']) {
						$this->module = $this->modules[2];
					
						return $self->presenter->model->getArticles()->wherePrimary($row['articles_id'])->fetch()->name;
					}
					elseif ($row['products_id']) {
						$this->module = $this->modules[3];
						
						return $self->presenter->model->getProducts()->wherePrimary($row['products_id'])->fetch()->name;
					}		
				})
				->setSortable(false);
				
			$this->addColumn('module', 'Modul', '41%')
// 				->setSelectFilter($this->modules)
				->setRenderer(function ($row) {
					return $this->module;
				})
				->setSortable(false);
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
				
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteMailContent!', array($self->presenter->id, $row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/mailingContentsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeleteMailContent($self->presenter->id, $id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané uživatele?");

			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id']);
			
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$row->update($values);
			});
		}
	}