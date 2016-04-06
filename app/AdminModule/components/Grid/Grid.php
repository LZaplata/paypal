<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;
use Nette\Utils\Strings;

	class DataGrid extends Grid {
		public $data;

		public function __construct($data) {
			parent::__construct();

			$this->data = $data;
		}

		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);

			if (!$this->presenter->section) {
				$this->presenter->redirect('Settings:');
			}

			$self = $this;

			if ($self->presenter->presenterName != 'Products' || ($self->presenter->presenterName == 'Products' && $self->presenter->category)) {
				$this->addColumn('position')
					->setWidth('20px')
					->setTextEditable();
			}
			$this->addColumn('visibility')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter->link('Visibility!', array($self->presenter->presenterName == 'Products' ? 0 : $row['sections_id'], $self->presenter->presenterName == 'Products' ? $row['products_id'] : $row['pid'], $row['visibility'] == 0 ? 0 : 1)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax')->title('Viditelnost na webu');});
			$this->addColumn('highlight')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter->link('Highlight!', array($self->presenter->presenterName == 'Products' ? 0 : $row['sections_id'], $self->presenter->presenterName == 'Products' ? $row['products_id'] : $row['pid'], $row['highlight'] == 0 ? 0 : 1)))->addClass($row['highlight'] == 0 ? 'fa fa-star-o text-danger' : 'fa fa-star text-success')->addClass('grid-ajax')->title('Zvýraznění položky');});
			$this->addColumn('name', 'Název')
				->setTextEditable()
				->setTextFilter();

			if ($self->presenter->presenterName != 'Products') {
// 				$this->addColumn('categories_id', 'Kategorie')
// 					->setRenderer(function($row) use ($self) {
// 						if ($self->presenter['grid']->getParameter('filter')) {
// 							return $self->presenter->model->getCategories()->wherePrimary($self->presenter['grid']->getParameter('filter')['categories_id'])->fetch()->name;
// 						}
// 						else {
// 							if ($self->presenter->presenterName == 'Articles') {
// 								$categories = $self->presenter->model->getArticlesCategories()->select('*, categories.name AS catName')->where('articles_categories.articles_id', $row['pid'])->fetchPairs('catName', 'catName');
// 							}
// 							else {
// 								$categories = $self->presenter->model->getProductsCategories()->select('*, categories.name AS catName')->where('products_categories.products_id', $row['products_id'])->fetchPairs('catName', 'catName');
// 							}

// 							return implode(', ', array_values($categories));
// 						}
// 					})
// 					->setTableName($self->presenter->presenterName == 'Products' ? 'categories_id' : /*':articles_categories.categories_id'*/'')
// 					->setSelectFilter($self->presenter->model->getCategories()->where('sections_id', ($self->presenter->presenterName == 'Products' ? 0 : $self->presenter->id))->fetchPairs('id', 'name'), '--Všechny kategorie--');

				if (!$self->presenter->section->slider) {
					$this->addColumn('categories_id', 'Kategorie')
						->setRenderer(function($row) use ($self) {
							if ($self->presenter['grid']->getParameter('filter')) {
								return $self->presenter->model->getCategories()->wherePrimary($self->presenter['grid']->getParameter('filter')['categories_id'])->fetch()->name;
							}
							else {
								if ($self->presenter->presenterName == 'Articles') {
									$categories = $self->presenter->model->getArticlesCategories()->select('*, categories.name AS catName')->where('articles_categories.articles_id', $row['pid'])->fetchPairs('catName', 'catName');
								}
								else {
									$categories = $self->presenter->model->getProductsCategories()->select('*, categories.name AS catName')->where('products_categories.products_id', $row['products_id'])->fetchPairs('catName', 'catName');
								}

								return implode(', ', array_values($categories));
							}
						})
						->setTableName($self->presenter->presenterName == 'Products' ? 'categories_id' : /*':articles_categories.categories_id'*/'')
						->setSelectFilter($self->presenter->model->getCategories()->where('sections_id', ($self->presenter->presenterName == 'Products' ? 0 : $self->presenter->id))->fetchPairs('id', 'name'), '--Všechny kategorie--');
				}
				else {
					$this->addColumn('pages_id', 'Stránky')
					->setRenderer(function($row) use ($self) {
						$pages = $self->presenter->model->getArticlesPages()->select('*, pages.name AS page')->where('articles_pages.articles_id', $row['pid'])->fetchPairs('page', 'page');

						return implode(', ', array_values($pages));
					});
				}
			}
			else {
				$this->addColumn('pohodacode', 'Pohoda')
					->setTextEditable()
					->setTextFilter();

				$this->addColumn('price', 'Cena')
					->setRenderer(function($row) {return number_format($row['price'], 2, '.', '');})
					->setTextEditable();
			}

			$this->addColumn('date', 'Datum')
				->setWidth('109px')
				->setRenderer(function($row) {return $row['date']->format('j. n. Y');})
				->setDateEditable()
				->setDateFilter();

			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/grid.latte');
			$this->paginate = true;
			$this->setWidth('100%');
			//$this->setDefaultOrder($this->presenter['grid']->getParameter('filter') ? 'position ASC' : 'name ASC');

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('edit', 'Editovat')
				->setClass('fa fa-pencil')
				->setLink(function($row) use ($self){return $self->presenter->link($self->presenter->presenterName.':edit', array($self->presenter->presenterName == 'Products' ? $row['products_id'] : $row['pid'], $self->presenter->presenterName == 'Products' ? 0 : $row['sections_id']));})
				->setAjax(false);
			if ($this->presenter->section->gallery) {
				$this->addButton('gallery', 'Galerie')
					->setClass('fa fa-camera')
					->setLink(function($row) use ($self){return $self->presenter->link($self->presenter->presenterName.':gallery', array($row['galleries_id'], $self->presenter->presenterName == 'Products' ? 0 : $row['sections_id']));})
					->setAjax(false);
			}
			if ($this->presenter->section->files) {
				$this->addButton('files', 'Soubory')
					->setClass('fa fa-file-o')
					->setLink(function($row) use ($self){return $self->presenter->link($self->presenter->presenterName.':files', array($row['filestores_id'], $self->presenter->presenterName == 'Products' ? 0 : $row['sections_id']));})
					->setAjax(false);
			}

			if ($self->presenter->presenterName == 'Products' && count($self->presenter->properties)) {
				$this->addButton('variations', 'Přidat variace')
					->setClass('fa fa-random')
					->setLink(function ($row) use ($self) {return $self->presenter->link($self->presenter->presenterName.':variations', array($row['id'], $self->presenter->presenterName == 'Products' ? 0 : $row['sections_id']));})
					->setAjax(false);
			}

			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					if ($self->presenter->presenterName == 'Products') {
						return $self->presenter->link('ToTrash!', array(0, $row['id']));
					}
					else return $self->presenter->link('Delete!', array($row['sections_id'], $row['pid']));
				})
				->setConfirmationDialog(function($row) use ($self) {
					if ($self->presenter->presenterName == 'Products') {
						return "Opravdu vyhodit položku $row[title] do koše?";
					}
					else return "Opravdu odstranit položku $row[title]?";
				});

			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 0);});

			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){return $self->presenter->handleVisibility($self->presenter->sid, $id, 1);});

			$this->addAction("highlight","Zvýraznit")
				->setCallback(function($id) use ($self){return $self->presenter->handleHighlight($self->presenter->sid, $id, 0);});

			$this->addAction("unhighlight","Odzvýraznit")
				->setCallback(function($id) use ($self){return $self->presenter->handleHighlight($self->presenter->sid, $id, 1);});

			$this->addAction("delete","Smazat")
			    ->setCallback(function($id) use ($self){
			    	if ($self->presenter->presenterName == 'Products') {
			    		return $self->presenter->handleToTrash($self->presenter->sid, $id);
			    	}
			    	else return $self->presenter->handleDelete($self->presenter->sid, $id);
			    })
			    ->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");

			if ($self->presenter->presenterName == 'Products') {
				$this->addSubGrid('variations', 'Zobrazit variace')
					->setGrid(new VariationsGrid($this->presenter->model->getProducts()->select(':products_properties.id AS ppid, products.*')->where('products.pid', $this->activeSubGridId)))
					->settings(function($grid){
						$grid->setWidth("90%");
					})
					->setAjax(false);

				$this->addAction("remove", "Odebrat z kategorie")
					->setCallback(function ($id) use ($self) {
						return $self->presenter->handleRemoveFromCategory($self->presenter->sid, $id);
					})
					->setConfirmationDialog("Opravdu odebrat všechny vybrané položky z kategorie?");

				$this->addAction("add", "Přidat do kategorie...")
					->setCallback(function ($id) use ($self) {
						return $self->presenter->handleAddToCategory($self->presenter->sid, $id);
					})
					->setConfirmationDialog("Opravdu přidat všechny vybrané položky do vybrané kategorie?");
			}

			$this->setRowFormCallback(function ($values) use ($self) {
				if ($self->presenter->presenterName == "Products") {
					$product = $self->presenter->model->getProducts()->wherePrimary($values['id'])->fetch();
					$values['price'] = Strings::replace($values['price'], '~,~', '.');

					if ($self->presenter->getParameter('category')) {
						$self->presenter->model->getProductsCategories()->where('products_id', $product->products_id)->where('categories_id', $self->presenter->category)->update(array('position' => $values['position']));
					}

					if ($values['price'] != $product->price && $product->pid == null) {
						foreach ($self->presenter->model->getProducts()->where('pid', $product->id) as $prod) {
							$prod->update(array('price' => $values['price']));
						}
					}

					if ($values['name'] != $product->name && $product->pid == null) {
						foreach ($self->presenter->model->getProducts()->where('pid', $product->id) as $prod) {
							$prod->update(array('name' => $values['name']));
						}
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