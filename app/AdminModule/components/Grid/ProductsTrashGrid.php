<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class ProductsTrashGrid extends Grid {
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
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/grid.latte');
			$this->paginate = true;
			$this->setWidth('100%');
			//$this->setDefaultOrder($this->presenter['grid']->getParameter('filter') ? 'position ASC' : 'name ASC');
			
			$this->addButton('variations', 'Obnovit')
				->setClass('fa fa-rotate-left')
				->setLink(function ($row) use ($self) {
					return $self->presenter->link('RemoveFromTrash!', array(0, $row['id']));
				});
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Delete!', array(0, $row['id']));
				})
				->setConfirmationDialog(function($row) use ($self) {
					return "Opravdu odstranit položku $row[title]?";
				});
			
			$this->addAction("delete","Smazat")
			    ->setCallback(function($id) use ($self){
			    	return $self->presenter->handleDelete($self->presenter->sid, $id);
			    })
			    ->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
			    
			$this->addAction("refresh","Obnovit")
			    ->setCallback(function($id) use ($self){
			    	return $self->presenter->handleRemoveFromTrash(0, $id);
			    });
			    
			$this->addSubGrid('variations', 'Zobrazit variace')
			    ->setGrid(new VariationsGrid($this->presenter->model->getProducts()->select(':products_properties.id AS ppid, products.*')->where('products.pid', $this->activeSubGridId)))
			    ->settings(function($grid){
			    	$grid->setWidth("90%");
			    })
			    ->setAjax(false);
			
			$this->setRowFormCallback(function ($values) use ($self) {		
				if ($self->presenter->presenterName == "Products") {				
					$product = $self->presenter->model->getProducts()->wherePrimary($values['id'])->fetch();
					
					if ($self->presenter->getParameter('category')) {
						$self->presenter->model->getProductsCategories()->where('products_id', $product->products_id)->where('categories_id', $self->presenter->category)->update(array('position' => $values['position']));
					}
					
					$self->presenter->lastEdited->rows[] = $values['id'];
					
					unset($values['id']);
					unset($values['position']);
					$product->update($values);
				}
				else {				
					$row = $self->presenter->model->getArticles()->wherePrimary($values['id'])->update($values);
					
					$self->presenter->lastEdited->rows[] = $values['id'];
						
// 					unset($values['id']);
// 					unset($values['position']);
				}
			});
		}
	}