<?php
	namespace AdminModule;
	
	use Nette\Utils\Html;

	use Nette\Utils\Strings;

	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class Fields extends Grid {		
		public $data;
		public $types;
		
		public function __construct($data) {
			parent::__construct();
		
			$this->data = $data;
			$this->types = array(1 => 'Text input', 2 => 'Text area', 3 => 'Checkbox', 4 => 'Select', 5 => 'Text area (tinymce)');
		}
		
		public function configure($presenter) {			
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
				
			$self = $this;
						
			$this->addColumn('position')
				->setWidth('20px')
				->setTextEditable();
			
			$this->addColumn('visibility')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->link('Visibility!', array($row['id'], $row['visibility'] == 0 ? 0 : 1)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax');});
			
			$this->addColumn('name', 'Název')
				->setTextEditable();
			
			$this->addColumn('title', 'Název v databázi')
				->setTextEditable();
			
			$this->addColumn('type', 'Typ')
				->setSelectEditable($this->types)
				->setRenderer(function ($row) {
					return $this->types[$row['type']];
				});
			
			$this->addColumn('values', 'Hodnoty')
				->setTextEditable();
			
			$this->addColumn('default', 'Výchozí hodnota')
				->setTextEditable();
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
			
			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){return $self->handleVisibility($id, 0);});
			
			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){return $self->handleVisibility($id, 1);});
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->handleDelete($id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
				
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->setDefaultOrder('id ASC');
			$this->addGlobalButton(Grid::ADD_ROW);
				
			$this->setRowFormCallback(function($values) use($self) {
				$values['title'] = Strings::webalize(Strings::replace($values['title'], '[ ]', '_'), '[a-z0-9_]');
				
				if (isset($values['id'])) {
					$row = $self->data->wherePrimary($values['id'])->fetch();
					$title = $self->presenter->model->getSectionsFields()->where('title', $values['title'])->fetch();
					$sibling = $self->presenter->model->getSectionsFields()->where('title', $row->title)->where('sections_id != ?', $self->presenter->sid)->fetch();
					
					if ($sibling) {
						if ($row->title != $values['title']) {				
							$self->addColumns($values);
						}						
					}
					else {
						if ($title && $title['id'] != $row['id']) {
							$data['title'] = $row->title;
							
							$self->deleteColumns($data);
						} 
						else {
							$self->editColumns($row, $values);
						}
					}					

					$row->update($values);
				}
				else {
					if ($self->presenter->model->getSectionsFields()->where('title', $values['title'])->fetch()) {						
						if (!$self->presenter->model->getSectionsFields()->where('title', $values['title'])->where('sections_id', $self->presenter->sid)->fetch()) {
							$values['sections_id'] = $self->presenter->sid;
							
							$self->presenter->model->getSectionsFields()->insert($values);
						}
					}
					else {
						$values['sections_id'] = $self->presenter->sid;
						
						$self->presenter->model->getSectionsFields()->insert($values);
						$self->addColumns($values);
					}
				}
			});
		}
		
		public function getTable () {
			if ($module = $this->presenter->model->getSections()->wherePrimary($this->presenter->sid)->fetch()) {
				return $module->modules_id == 1 ? "editors" : "articles";
			}
			else return 'products';
		}
		
		public function addColumns ($fields) {
			$type = array(
					1 => 'VARCHAR (255)',
					2 => 'TEXT',
					3 => 'TINYINT (3)',
					4 => 'VARCHAR (255)',
					5 => 'TEXT'
			);
		
			$langs = array_merge($this->presenter->langs->fetchPairs('id', 'key'), array(0 => 'cs'));
		
			foreach ($langs as $lang) {				
				$key = $lang == 'cs' ? '' : '_'.$lang;
				
				if ($fields['default'] == '') {
					$this->presenter->context->database->query('ALTER TABLE '.$this->getTable().'
							ADD '.$fields["title"].$key.' '.$type[$fields["type"]].' NULL
					');
				}
				else {
					$this->presenter->context->database->query('ALTER TABLE '.$this->getTable().' 
							ADD '.$fields["title"].$key.' '.$type[$fields["type"]].' NOT NULL DEFAULT "'.$fields["default"].'"
					');
				}
			}
		}
		
		public function editColumns ($old, $new) {
			$type = array(
					1 => 'VARCHAR (255)',
					2 => 'TEXT',
					3 => 'TINYINT (3)',
					4 => 'VARCHAR (255)',
					5 => 'TEXT'
			);
		
			$langs = array_merge($this->presenter->langs->fetchPairs('id', 'key'), array(0 => 'cs'));
		
			foreach ($langs as $lang) {
				$key = $lang == 'cs' ? '' : '_'.$lang;
		
				if ($new['default'] == '') {
					$this->presenter->context->database->query('ALTER TABLE '.$this->getTable().'
							CHANGE '.$old->title.$key.' '.$new["title"].$key.' '.$type[$new["type"]].' NULL
					');
				}
				else {
					$this->presenter->context->database->query('ALTER TABLE '.$this->getTable().'
							CHANGE '.$old->title.$key.' '.$new["title"].$key.' '.$type[$new["type"]].' NOT NULL DEFAULT "'.$new["default"].'"
					');
				}
			}
		}

		public function handleDelete ($id) {
			$ids = (array)$id;
			
			foreach ($ids as $id) {
				$row = $this->presenter->model->getSectionsFields()->where('id', $id)->fetch();
				
				if (!$this->presenter->model->getSectionsFields()->where('title', $row->title)->where('sections_id != ?', $this->presenter->sid)->fetch()) {
					$this->deleteColumns($row);
				}
				
				$row->delete();
			}
		}
		
		public function deleteColumns ($fields) {			
			$langs = array_merge($this->presenter->langs->fetchPairs('id', 'key'), array(0 => 'cs'));
			
			foreach ($langs as $lang) {
				$key = $lang == 'cs' ? '' : '_'.$lang;
			
				$this->presenter->context->database->query('ALTER TABLE '.$this->getTable().'
					DROP '.$fields["title"].$key.'
				');
			}
		}
		
		public function handleVisibility ($fid, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->presenter->model->getSectionsFields()->where('id', $fid)->update(array('visibility' => $vis));
		}
	}