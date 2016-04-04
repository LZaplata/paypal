<?php
	namespace AdminEshopModule;
	
	use Nette\Utils\Html;

	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class MethodsRelations extends Grid {
		public $data;
		public $pid;
		
		public function __construct($data) {
			parent::__construct();
		
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
				
			$self = $this;
			$transports = $this->presenter->model->getShopMethods()->where('type', array(0, 3))->fetchPairs('id', 'name');
			$payments = $this->presenter->model->getShopMethods()->where('type', array(1, 2, 4))->fetchPairs('id', 'name');
		
			$this->addColumn('id');
			$this->addColumn('highlight')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->link('Highlight!', array($row['id'], $row['highlight'] == 0 ? 0 : 1)))->addClass($row['highlight'] == 0 ? 'fa fa-star-o text-danger' : 'fa fa-star text-success')->addClass('grid-ajax');});
			$this->addColumn('shop_methods_id', 'Doprava')
				->setSelectEditable($transports)
				->setRenderer(function ($row) use ($transports) {
					return $transports[$row['shop_methods_id']];
				});
			$this->addColumn('id_shop_methods', 'Platba')
				->setSelectEditable($payments)
				->setRenderer(function ($row) use ($payments) {
					return $payments[$row['id_shop_methods']];
				});
			$this->addColumn('price', 'Cena')
				->setTextEditable();
			$this->addColumn('max', 'Maximální cena')
				->setTextEditable();		
//			$this->addColumn('country', 'Stát')
//				->setSelectEditable(array('CZ' => 'CZ', 'SK' => 'SK'));	
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
				
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/componentsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
// 			$this->setDefaultOrder('id ASC');
			$this->addGlobalButton(Grid::ADD_ROW);
					
			$this->setRowFormCallback(function($values) use($self) {
				if (isset($values['id'])) {
					$self->data->wherePrimary($values['id'])->update($values);	
				}
				else {
					$self->presenter->model->getShopMethodsRelations()->insert($values);
				}
			});
		}
		
		public function handleDelete ($id) {
			$this->presenter->model->getShopMethodsRelations()->wherePrimary($id)->delete();
			
			$this->presenter->flashMessage('Položka byla smazána');
		}
		
		public function handleHighlight($id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			
			$this->presenter->model->getShopMethodsRelations()->update(array('highlight' => 0));
			$this->presenter->model->getShopMethodsRelations()->wherePrimary($id)->update(array("highlight" => $vis));
		
			$this->presenter->flashMessage('Nastavení defaultní relace změněno!');
		}
	} 