<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class MailingMailQueueGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Skupina','41%')
				->setSelectEditable($self->presenter->model->getUsersCategories()->select('categories.id AS id, categories.name AS name')->fetchPairs('id', 'name'));
			
			$this->addColumn('date', 'Datum a čas', '41%')
				->setDateEditable()
				->setRenderer(function ($row) {
					return $row['date']->format('j.n.Y G:i');
				});
			
// 			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
// 				->setClass("fast-edit");
				
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteMailQueue!', array($self->presenter->id, $row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit skupinu a smazat tím údaje ze statistik?";});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeleteMailQueue($self->presenter->id, $id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané skupiny a smazat tím údaje ze statistik?");
			
			$this->setRowFormCallback(function ($values) use ($self) {
				$i=0;		
				$pid = 0;									
				foreach ($self->presenter->model->getUsersCategories()->where('categories_id', $values['name']) as $user) {
					$data['emails_id'] = $self->presenter->email->id;
					$data['users_id'] = $user->users_id;
					$data['categories_id'] = $user->categories_id;
					$data['date'] = $values['date'];
					if ($i > 0 && $i < 2) {
						$data['pid'] = $pid;
					}
					
					if ($row = $self->presenter->model->getEmailsQueue()->where($data)->fetch()) {
						$pid = $row->id;
						
						$self->presenter->lastEdited->rows[] = $values['id'];
						
						$row->update($data);
					}
					else {
						$queue['emails_id'] = $self->presenter->email->id;
						$queue['users_id'] = $user->users_id;
						
						if (!$self->presenter->model->getEmailsQueue()->where($queue)->fetch()) {
							$row = $self->presenter->model->getEmailsQueue()->insert($data);
							$pid = $row;
						}
					}
					
					$i++;	
				}
			});
		}
	}