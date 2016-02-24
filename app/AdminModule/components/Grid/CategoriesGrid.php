<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class CategoriesGrid extends Grid {
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
			
			$this->addColumn('position')
				->setWidth('20px')
				->setTextEditable();
			$this->addColumn('visibility')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {
					return Html::el('a')->href($self->presenter->link('Visibility!', array($row['sections_id'], $row['id'], $row['visibility'] == 0 ? 0 : 1)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax');				
				});
			$this->addColumn('highlight')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {
					return Html::el('a')->href($self->presenter->link('Highlight!', array($row['sections_id'], $row['id'], $row['highlight'] == 0 ? 0 : 1)))->addClass($row['highlight'] == 0 ? 'fa fa-star-o text-danger' : 'fa fa-star text-success')->addClass('grid-ajax');
				});
			$this->addColumn('name', 'Název')
				->setTextEditable()
				->setTextFilter();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/categoriesGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->setDefaultOrder('position ASC');
			$this->addButton('add', 'Přidat podkategorii')
				->setClass('fa fa-plus')
				->setLink(function($row) use ($self) {
					if ($self->presenter->moduleName == 'Admin') {
						return $self->presenter->link('Categories:add', array($row['sections_id'], $row['id']));
					}
					else return $self->presenter->link('Categories:add', array($row['id']));
				})
				->setAjax(false);
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('edit', 'Editovat')
				->setClass('fa fa-pencil')
				->setLink(function($row) use ($self){return $self->presenter->link('Categories:edit', array($row['id']));})
				->setAjax(false);
			$this->addButton('gallery', 'Galerie')
				->setClass('fa fa-camera')
				->setLink(function($row) use ($self){return $self->presenter->link('Categories:gallery', array($row['galleries_id'], $row['sections_id']));})
				->setAjax(false);
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Delete!', array($row['sections_id'], $row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku $row[title]?";});;
			
			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 0);});
				
			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 1);});
				
			$this->addAction("highlight","Zvýraznit")
				->setCallback(function($id) use ($self){return $self->presenter->handleHighlight($self->presenter->sid, $id, 0);});
			
			$this->addAction("unhighlight","Odzvýraznit")
				->setCallback(function($id) use ($self){return $self->presenter->handleHighlight($self->presenter->sid, $id, 1);});
				
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self) {
					return $self->presenter->handleDelete($self->presenter->getParameter('id'), $id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
			
			$this->addAction("move", "Přesunout pod...")
				->setCallback(function ($id) use ($self) {
					return $self->presenter->handleMoveCategories($id);
				})
				->setConfirmationDialog("Opravdu přesunout vybrané položky?");
			
			$this->addSubGrid('subcategories', 'Zobrazit podkategorie')
				->setGrid(new CategoriesGrid($this->presenter->model->getCategories()->where('sections_id', ($self->presenter->moduleName == 'Admin' ? $self->presenter->id : 0)), $this->activeSubGridId))
				->settings(function($grid){
        			$grid->setWidth("90%");
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