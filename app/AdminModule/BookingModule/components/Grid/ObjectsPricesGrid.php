<?php
	namespace AdminBookingModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class ObjectsPricesGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název', '22%')
				->setTextEditable()
				->setTextFilter();
			
			$this->addColumn('price', 'Cena', '22%')
				->setTextFilter()
				->setTextEditable()
				->setRenderer(function ($row) {
					return $row['price'].' czk';
				});
			
			$this->addColumn('dateFrom', 'Platnost od', '22%')
				->setDateFilter()
				->setDateEditable()
				->setRenderer(function ($row) {
					return $row['dateFrom']->format('j.n.Y');
				});
			
			$this->addColumn('dateTo', 'Platnost do', '22%')
				->setDateFilter()
				->setDateEditable()
				->setRenderer(function ($row) {
					return $row['dateTo']->format('j.n.Y');
				});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');		

			$this->addGlobalButton(Grid::ADD_ROW);

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fast-edit");
			
			$this->addButton('delete', 'Smazat')
				->setClass('del')
				->setLink(function($row) use ($self){
					return $self->link('DeletePrice!', array($row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit cenu?";});	
	
			$this->setRowFormCallback(function ($values) use ($self) {
				if (isset($values['id'])) {
					$price = $this->presenter->model->getBookingPrices()->wherePrimary($values['id'])->fetch();
					$prices = $this->presenter->model->getBookingPrices()->where('dateTo = ? AND dateFrom = ? AND price = ?',$price->dateTo,$price->dateFrom,$price->price)->where('booking_objects_id IN ?',$self->presenter->ids);
					unset($values['id']);
					$prices->update($values);
				}
				else {
					$data = array();
					if(!$this->presenter->model->getBookingPrices()->where('dateTo = ? AND dateFrom = ? AND price = ?',$values["dateTo"],$values["dateFrom"],$values["price"])->where('booking_objects_id IN ?',$self->presenter->ids)->count()){					
						foreach($self->presenter->objects as $object){
							$data['name'] = $values["name"];
							$data['price'] = $values["price"];
							$data['booking_objects_id'] = $object->id;
							$data['dateTo'] = $values["dateTo"];
							$data['dateFrom'] = $values["dateFrom"];
							$self->presenter->model->getBookingPrices()->insert($data);						
						}
					}				
				}				
			});
		}
		
		public function handleDeletePrice($id){
			$price = $this->presenter->model->getBookingPrices()->wherePrimary($id)->fetch();
			$prices = $this->presenter->model->getBookingPrices()->where('dateTo = ? AND dateFrom = ? AND price = ?',$price->dateTo,$price->dateFrom,$price->price)->where('booking_objects_id IN ?',$this->presenter->ids)->delete();			
		}
	}