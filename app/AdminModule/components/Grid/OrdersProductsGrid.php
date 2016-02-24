<?php
	namespace AdminModule;

	use Nette\Application\UI\Form;

	use FrontEshopModule\Cart;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class OrdersProductsGrid extends Grid {
		public $data;
		public $states;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			$self->states = $self->presenter->orderStates;
				
			unset($self->states[2]);
					
			$this->addColumn('code', 'Kód');
			
			$this->addColumn('name', 'Název', '40%')
				->setRenderer(function ($row) use ($self) {
					return $row['name'].' ('.$self->presenter->getProductProperties($row['products_id']).')';
				});
			
			$this->addColumn('price', 'Cena/ks')
				->setRenderer(function ($row) use ($self) {					
					return number_format($row['price'] / $row['rate'], $row['currency'] == 'czk' ? 0 : 2, ',', ' ').' '.$row['currency'];
				});
				
			$this->addColumn('amount', 'Množství')
				->setTextEditable();
			
			$this->addColumn('total', 'Celkem')
				->setRenderer(function ($row) use ($self) {
					return number_format($row['price'] / $row['rate'] * $row['amount'], $row['currency'] == 'czk' ? 0 : 2, ',', ' ').' '.$row['currency'];
				});
			
			$this->addColumn('state', 'Stav', '150px')
				->setSelectEditable($self->states)
				->setRenderer(function ($row) use ($self) {					
					return $self->presenter->orderStates[$row['state']];
				});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			if(isset($self->presenter->order)){
				$this->addButton('delete', 'Odebrat')
					->setClass('fa fa-trash text-danger')
					->setLink(function($row) use ($self){return $self->presenter->link('removeProductFromOrder!', array($row['id']));})
					->setConfirmationDialog(function () {
						return "Opravdu smazat produkt z objednávky?";
					});		
			}
			/*$this->addButton('edit', 'Editovat')
				->setClass('edit')
				->setLink(function($row) use ($self){return $self->presenter->link($self->presenter->presenterName.':edit', array($self->presenter->presenterName == 'Products' ? $row['products_id'] : $row['pid'], $row['sections_id']));})
				->setAjax(false);
			if ($this->presenter->section->gallery) {
				$this->addButton('gallery', 'Galerie')
					->setClass('gallery')
					->setLink(function($row) use ($self){return $self->presenter->link($self->presenter->presenterName.':gallery', array($row['galleries_id'], $row['sections_id']));})
					->setAjax(false);
			}
			if ($this->presenter->section->files) {
				$this->addButton('files', 'Soubory')
					->setClass('files')
					->setLink(function($row) use ($self){return $self->presenter->link($self->presenter->presenterName.':files', array($row['filestores_id'], $row['sections_id']));})
					->setAjax(false);
			}
			$this->addButton('delete', 'Smazat')
				->setClass('del')
				->setLink(function($row) use ($self){return $self->presenter->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
			
			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 0);});
			
			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 1);});
			
			$this->addAction("highlight","Zvýraznit")
				->setCallback(function($id) use ($self){return $self->presenter->handleHighlight($self->presenter->sid, $id, 0);});
				
			$this->addAction("unhighlight","Odzvýraznit")
				->setCallback(function($id) use ($self){return $self->presenter->handleHighlight($self->presenter->sid, $id, 1);});
			
			$this->addAction("delete","Smazat")
			    ->setCallback(function($id) use ($self){return $self->presenter->handleDelete($id);})
			    ->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
			
			$this->addSubGrid('products')
				->settings(function($grid){
					$grid->setWidth("90%");
				})
				->setAjax(false);
			*/
			$this->setRowFormCallback(function ($values) use ($self) {
				$orderProduct = $this->data->wherePrimary($values['id'])->fetch();
				$product = $self->presenter->model->getProducts()->wherePrimary($orderProduct->products_id)->fetch();
				$discountPrice = $self->presenter['cart']->getProductDiscountPrice ($product->id, $product->price, $values['amount']);				
				$values['price'] = $discountPrice;
				
				$orderProduct->update($values);
				
				$self->presenter['cart']->updateOrder(array('id' => $orderProduct->orders->id, 'currency' => $orderProduct->orders->currency));
				$self->presenter->updateOrderState($orderProduct->orders_id);
				
				$self->presenter->lastEdited->rows[] = $values['id'];
			});
		}
		
// 		public function createComponentCart ($name) {
// 			return new Cart($this, $name);
// 		}
	}