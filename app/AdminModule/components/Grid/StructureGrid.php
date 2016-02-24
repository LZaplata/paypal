<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class StructureGrid extends Grid {
		public $data;
		public $data2;
		public $pid;
		
		public function __construct($data, $pid = 0) {
			parent::__construct();
			
			$this->data = $data;
			$this->pid = $pid;
			
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data->where('pid', $this->pid));
			$this->setDataSource($dataSource);
		
			
			$self = $this;
			
// 			$this->addColumn('id','ID')
// 				->setSortable(false)
// 				->setWidth('20px');

			$this->addColumn('position', '', '20px')
				->setTextEditable();
			
			$this->addColumn('visibility', '', '20px')
				->setRenderer(function($row) use($self) {
					return Html::el('a')->href($self->presenter->link('Visibility!', array($row['id'], $row['visibility'] == 0 ? 0 : 1)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax');
				});
			
			$this->addColumn('highlight', '', '20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter->link('Highlight!', array($row['id'], $row['highlight'] == 0 ? 0 : 1)))->addClass($row['highlight'] == 0 ? 'fa fa-home text-danger' : 'fa fa-home text-success')->addClass('grid-ajax');});
			
			$this->addColumn('name', 'Název')
				->setTextEditable()
				->setTextFilter();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/categoriesGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			$this->addButton('add', 'Přidat')
				->setClass('fa fa-plus')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Structure:add', array($row['id']));
				})
				->setAjax(false);
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('edit', 'Editovat')
				->setClass('fa fa-pencil')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Structure:edit', array($row['id']));
				})
				->setAjax(false);
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Delete!', array($row['id']));
				})
				->setConfirmationDialog(function($row){
					return "Opravdu odstranit stránku $row[name]?";
				});
				
			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){
					return $self->presenter->handleVisibility($id, 0);
				});
			
			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){
					return $self->presenter->handleVisibility($id, 1);
				});
			
			$this->addAction("highlight","Zvýraznit")
				->setCallback(function($id) use ($self){
					return $self->presenter->handleHighlight($id, 0);
				});
				
			$this->addAction("unhighlight","Odzvýraznit")
				->setCallback(function($id) use ($self){
					return $self->presenter->handleHighlight($id, 1);
				});
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){
					return $self->presenter->handleDelete($id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané stránky?");
			
			$this->addSubGrid('subpages', 'Zobrazit podstránky')
				->setGrid(new StructureGrid($this->presenter->model->getPages()->where('pid', $this->activeSubGridId),$this->activeSubGridId))
				->settings(function($grid){
					$grid->setWidth("90%");
					$grid->setDefaultOrder('position ASC');
				})
				->setAjax(false);
				

			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id']);
			
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$row->update($values);
			});
		}
	}