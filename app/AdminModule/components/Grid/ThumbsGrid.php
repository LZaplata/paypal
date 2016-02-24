<?php
	namespace AdminModule;

	use Nette\Utils\Finder;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class ThumbsGrid extends Grid {
		public $data;
		public $places;
		public $operations;
		public $watermarks;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
			$this->places = array('Nerozhoduje', 'Úvodní obrázek', 'Galerie', 'Kategorie');
			$this->operations = array('Zmenšit', 'Oříznout');
			$this->watermarks[null] = '--Žádný--';
			
			foreach (Finder::findFiles('watermark*.png')->in(WWW_DIR.'/images') as $key => $file) {
				$this->watermarks[$file->getBasename()] = $file->getBasename();
			}
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('dimension', 'Rozměr', '22%')
				->setRenderer(function($row) use($self) {
					return $row['dimension']." px";
				})
				->setTextEditable();
				
			$this->addColumn('operation', 'Operace', '22%')
				->setRenderer(function ($row) {
					return $this->operations[$row['operation']];
				})
				->setSelectEditable($this->operations);
			
			$this->addColumn('place', 'Umístění', '22%')
				->setRenderer(function($row) use($self) {
					return $this->places[$row['place']];
				})
				->setSelectEditable($this->places);
				
			$this->addColumn('watermark', 'Vodoznak', '22%')
				->setSelectEditable($this->watermarks);
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteThumb!', array($self->presenter->id, $row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit rozměr $row[dimension]?";});;
				
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeleteThumb($self->presenter->id, $id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané rozměry?");
						
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');		
			$this->addGlobalButton(Grid::ADD_ROW);

			$this->setRowFormCallback(function ($values) use ($self) {
				$values['sections_id'] = $self->presenter->id;
				
				if (isset($values['id'])) {
					$row = $self->data->wherePrimary($values['id'])->fetch();
				
					$self->presenter->lastEdited->rows[] = $values['id'];
					
					$this->presenter->handleDeleteThumb($self->presenter->id, $values['id'], true);
				
					unset($values['id']);
					$row->update($values);
					
					$self->presenter->handleCreateThumbs($row);
				}
				else {
					if (preg_match('~[0-9]+x[0-9]+~', $values['dimension'])) {
						$lastID = $self->presenter->model->getSectionsThumbs()->insert($values);
						
						$self->presenter->handleCreateThumbs($lastID);
					}
					else $self->presenter->flashMessage('Zadávejte rozměry ve tvaru ŠÍŘKAxVÝŠKA');					
				}
			});
		}
	}