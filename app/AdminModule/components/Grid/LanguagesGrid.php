<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class LanguagesGrid extends Grid {
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
			
			$this->addColumn('visibility', '', '20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter->link('Visibility!', array($row['id'], $row['visibility'] == 0 ? 0 : 1)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax');});
			
			$this->addColumn('highlight', '', '20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter->link('Highlight!', array($row['id'], $row['highlight'] == 0 ? 0 : 1)))->addClass($row['highlight'] == 0 ? 'fa fa-star-o text-danger' : 'fa fa-star text-success')->addClass('grid-ajax');});
			
			$this->addColumn('name', 'Název')
				->setTextEditable()
				->setTextFilter();
			
			$this->addColumn('key', 'Zkratka')
				->setTextEditable()
				->setTextFilter();

			$this->addColumn("rate", "Kurz")
				->setTextEditable();

			$this->addColumn("currency", "Měna")
				->setTextEditable();

			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			/*$this->addButton('edit', 'Editovat')
				->setClass('edit')
				->setLink(function($row) use ($self){return $self->presenter->link('Settings:editLanguage', array($row['id']));})
				->setAjax(false);*/
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
				
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit jazyk $row[name] a všechny jeho překlady?";});;
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDelete($id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané jazyky a jejich překlady?");			

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