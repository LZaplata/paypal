<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class ProductsGrid extends Grid {

		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název')
				->setTextEditable()
				->setTextFilter();
				
			$this->addColumn('price', 'Cena/ks');
				
			$this->addButton('add', 'Přidat')
				->setClass('add')
				->setLink(function($row) use ($self){return $self->presenter->link('addProductToOrder!', array($row['id'],$self->presenter->order->id));})
				->setConfirmationDialog(function($row){return "Opravdu přidat položku?";});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
		}
	}