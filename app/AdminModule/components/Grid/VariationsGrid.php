<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;
use Nette\Utils\Strings;
	
	class VariationsGrid extends Grid {
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
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter->link('Visibility!', array(0, $row['products_id'], $row['visibility'] == 0 ? 0 : 1, true)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax');});
						
			$this->addColumn('name', 'Název', '40%')
				->setTextFilter()
				->setTextEditable();
			
			$this->addColumn('properties', 'Vlastnosti')
				->setRenderer(function ($row) use ($self) {
					$productProperties = $self->presenter->model->getProductsProperties()->where('products_id', $row['id'])->fetch();
					
					foreach ($self->presenter->properties as $key => $property) {
						$p = 'p_'.$key;
						
						if ($productProperties->$p) {
							$properties[] = $property;
						}	
					}
					
					return implode(', ', $properties);
				})
				->setSortable(false)
				->setTableName('ppid');
				
			$this->addColumn('price', 'Cena')
				->setTextEditable();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('edit', 'Upravit')
				->setClass('fa fa-pencil')
				->setLink(function ($row) use ($self) {
					return $self->presenter->link('Products:edit', array($row['products_id'], 0));
				})
				->setAjax(false);
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('Delete!', array(0, $row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku $row[title]?";});
			
			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 0);});
			
			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 1);});
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDelete($self->presenter->sid, $id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
			
			$this->setRowFormCallback(function ($values) use ($self) {
				$product = $self->presenter->model->getProducts()->wherePrimary($values['id'])->fetch();
				$values['price'] = Strings::replace($values['price'], '~,~', '.');
								
				unset($values['id']);
				$product->update($values);
			});
		}
	}