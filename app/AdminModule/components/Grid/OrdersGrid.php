<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class OrdersGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('no', '#', '60px');
			
			$this->addColumn('email', 'E-mail', '250px')
				->setTextFilter();
			
			$this->addColumn('surname', 'Příjmení', '150px')
				->setTextFilter();
			
			$this->addColumn('name', 'Jméno', '150px')
				->setTextFilter();
			
			$this->addColumn('date', 'Datum', '150px')
				->setDateFilter()
				->setRenderer(function ($row) use ($self) {
					return $row['date']->format('j. n. Y H:i');
				});
			$this->addColumn('price', 'Cena', '150px')
				->setTextFilter()
				->setRenderer(function ($row) use ($self) {
					return number_format($row['price'] / $row['rate'], $row['currency'] == 'czk' ? 0 : 2, ',', ' ').' '.$row['currency'];
				});
				
			$this->addColumn('state', 'Stav', '150px')
				->setSelectFilter($self->presenter->orderStates)
				->setSelectEditable($self->presenter->orderStates)
				->setRenderer(function ($row) use ($self) {					
					return $self->presenter->orderStates[$row['state']];
				});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/grid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->primaryKey = 'id';
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
				
			$this->addButton('edit', 'Editace')
				->setClass('fa fa-pencil')
				->setLink(function($row) use ($self){return $self->presenter->link('edit', array($row['id']));})
				->setAjax(FALSE);

			$this->addButton('reques', 'Výzva k platbě')
				->setClass('fa fa-credit-card')
				->setLink(function($row) use ($self){return $self->presenter->link('PaymentRequest!', array($row['id']));});

			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
			
			$this->addAction("delete","Smazat")
			    ->setCallback(function($id) use ($self){return $self->presenter->handleDelete($id);})
			    ->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
			
			$this->addSubGrid('products')
				->setGrid(new OrdersProductsGrid($self->presenter->model->getOrdersProducts()->select('orders.*, orders_products.*, products.name')->where('orders_id', $this->activeSubGridId)))
				->settings(function($grid){
					$grid->setWidth("90%");
				})
				->setAjax(false);
			
			$this->setRowFormCallback(function ($values) use ($self) {				
				$row = $this->data->wherePrimary($values['id'])->fetch();
				$values['date'] = $row['date'];
				$oldState = $row['state'];
				
				$self->presenter->updateOrderProductsStates($row->id, $values['state']);
				$row->update($values);
				
				if ($oldState != $values['state']) {
					$self->presenter->sendMail($row);
				}
				
				$self->presenter->lastEdited->rows[] = $values['id'];
			});
		}
	}