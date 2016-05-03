<?php
	namespace AdminModule;

	use Nette\Utils\Strings;
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

			$this->addColumn('no', '#')
				->setTextFilter();

			$this->addColumn('email', 'E-mail')
				->setCellRenderer(function($row){return "font-size: 0.9em;"; })
				->setTextFilter();

			$this->addColumn('surname', 'Jméno / firma')
				->setTextFilter()
				->setRenderer(function ($row) use ($self) {
					return $row['company'] ? $row['company'] : $row['surname'].' '.$row['name'];
				});

// 			$this->addColumn('name', 'Jméno')
// 				->setTextFilter();

			$this->addColumn('date', 'Datum')
				->setDateFilter()
				->setCellRenderer(function($row){return "text-align: right; font-size: 0.9em;"; })
				->setRenderer(function ($row) use ($self) {
					return $row['date']->format('j. n. H:i');
				});
			$this->addColumn('price', 'Cena')
				->setTextFilter()
				->setCellRenderer(function($row){return "text-align: right; font-size: 0.9em;"; })
				->setRenderer(function ($row) use ($self) {
					return number_format(($row['price'] + $row["transport"]) / $row['rate'], $row['currency'] == 'czk' ? 0 : 2, ',', ' ').' '.($row['currency'] == 'czk' ? 'Kč' : $row['currency']);
				});

			$this->addColumn('state', 'Stav', '150px')
				->setSelectFilter($self->presenter->orderStates)
				->setSelectEditable($self->presenter->orderStates)
				->setRenderer(function ($row) use ($self) {
					if ($row["state"] == 0) {
						return Html::el("strong")->addAttributes(array("class" => "text-danger"))->setText($self->presenter->orderStates[$row["state"]]);
					} else {
						return $self->presenter->orderStates[$row['state']];
					}
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

			$this->addButton('request', 'Výzva k platbě')
				->setClass('fa fa-credit-card')
				->setLink(function($row) use ($self){return $self->presenter->link('PaymentRequest!', array($row['id']));});

			$this->addButton('pdf', 'Daňový doklad')
				->setClass('fa fa-file-pdf-o')
				->setLink(function($row) use ($self){
					return $self->presenter->link('GetPdf!', array($row['no']));
				})
				->setAjax(false);

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