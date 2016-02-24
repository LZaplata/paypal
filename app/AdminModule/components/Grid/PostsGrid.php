<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;
	
	use Nette\Utils\Html;
		
	class PostsGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('visibility')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {
					return Html::el('a')->href($self->presenter->link('Visibility!', $row['id'], $row['visibility'] == 1 ? 0 : 1))->addClass($row['visibility'] == 0 ? 'invisible' : 'visible')->addClass('grid-ajax')->title('Změnit viditelnost');
				});
				
			$this->addColumn('view')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {
					return Html::el('a')->href($self->presenter->link('View!', $row['id'], $row['view'] == 0 ? 1 : 0))->addClass($row['view'] == 0 ? 'invisible' : 'visible')->addClass('grid-ajax')->title('Změnit přečtení');
				});
			
			$this->addColumn('text', 'Příspěvek', '48%')
				->setTextEditable()
				->setRenderer(function ($row) {
					return Html::el('div')->setHtml($row['text']);
				});
			
			$this->addColumn('email', 'E-mail', '10%');
			
			$this->addColumn('surname', 'Příjmení', '10%');
			
			$this->addColumn('update', 'Změna', '13%')
				->setRenderer(function ($row) {
					if ($row['update']) {
						return $row['update']->format('j.n.Y H:i');
					}
					else return $row['date']->format('j.n.Y H:i');
				});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Delete!', array($row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit příspěvek?";});
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){
					if ($self->presenter->presenterName == 'Accounts') {
						return $self->presenter->handleDelete($id);
					}
					else return $self->presenter->handleLogout($id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané uživatele?");			

			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id']);
			
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$row->update($values);
			});
		}
	}