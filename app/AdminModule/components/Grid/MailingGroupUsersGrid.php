<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class MailingGroupUsersGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('visibility')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {
					return Html::el('a')->href($self->presenter->link('SwitchUser!', array($self->presenter->group->id, $row['id'], 0)))->addClass($row['visibility'] && $row['visibility'] == $self->presenter->group->id ? 'fa fa-eye text-success' : 'fa fa-eye-slash text-danger')->addClass('grid-ajax');
				});
			
			$this->addColumn('email', 'E-mail', '33%')
				->setTextFilter();
			
			/*$this->addColumn('name', 'Jméno', '22%')
				->setTextFilter();*/
			
			$this->addColumn('surname', 'Příjmení', '33%')
				->setTextFilter();
			
			$this->addColumn('vis', '')
				->setRenderer(function ($row) use ($self) {					
// 					return $row['visibility'];
					return null;
				})
				->setSelectFilter(array($self->presenter->getParameter('id') => 'Uživatelé ze skupiny'))
				->setSortable(false)
				->setTableName('visibility');
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->setDefaultOrder('visibility DESC');
			
			$this->addAction('visible', 'Přidat')
				->setCallback(function ($id) use ($self) {
					return $self->presenter->handleSwitchUser($self->presenter->group->id, $id, 1);
				});
				
			$this->addAction('invisible', 'Odebrat')
				->setCallback(function ($id) use ($self) {
					return $self->presenter->handleSwitchUser($self->presenter->group->id, $id, 0);
				});
		}
	}