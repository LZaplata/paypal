<?php
	namespace FrontModule;
	
	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;
use Nette\Application\Responses\JsonResponse;
use Nette\Utils\Strings;
		
	class Search extends Control {			
		public $eshop;
		
		public function __construct($parent, $name) {
			parent::__construct();
			
			$this->eshop = $parent->model->getPagesModules()
												->where('modules_id', 3)
												->where('pages_modules.position', 1)
												->where('pages.highlight', 0)
												->fetch()->pages->url;
		}
		
		public function createComponentSearchForm () {
			$form = new Form(); 
			
			$form->getElementPrototype()/*->class('navbar-form navbar-right')*/;
			
			$form->addText('q', '')
				->setValue($this->presenter->getParameter('q'))
				->setAttribute('autocomplete', 'off');
			
			$form->addHidden('id');
			
			$form->addSubmit('search', '');
			
			$form->onSuccess[] = $this->search;
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function search ($form) {
			$values = $form->values;
			$params['q'] = $values['q'];
			$params['category'] = null;
			$params['cid'] = null;
			$params['product'] = null;
			$params['pid'] = null;
			$params['eshop'] = $this->eshop;
			
			$data['query'] = $values['q'];
			$data['ip'] = $_SERVER['SERVER_ADDR'];
			
			if (!$this->presenter->logsModel->getSearchLog($data)->where('date > ?', date('Y-m-d H:i:s', strtotime('-1 minute')))->fetch()) {
				$data['date'] = date('Y-m-d H:i:s');
				
				$this->presenter->logsModel->getSearchLog()->insert($data);
			}
			
			if ($product = $this->presenter->model->getProducts()->wherePrimary($values['id'])->fetch()) {
				$category = $this->getProductCategory($product);
				
				$params['pid'] = $product->id;
				$params['product'] = $product->url;
				$params['cid'] = $category->id;
				$params['category'] = $category->url;
				
				unset($params['q']);
				
				$this->presenter->redirect(':FrontEshop:Products:view', $params);
			}
			else {
				$this->presenter->redirect(':FrontEshop:Homepage:', $params);
			}
		}
		
		public function render () {			
			$this->template->setFile(__DIR__.'/search.latte');
			
			$this->template->currency = $this->presenter->currency;
			$this->template->decimals = $this->presenter->currency == 'czk' ? 0 : 2;
			$this->template->eshop = $this->eshop;

			$this->template->render();
		}
		
		public function getProductCategory ($product) {
			return $this->presenter->model->getProductsCategories()->select('categories.*')->where('products_id', $product->products_id)->fetch();
		}
		
		public function handleAutocomplete () {
			$values = $_GET;
			$response = array();
			$products = $this->presenter->model->getProducts()
													->where('name LIKE ? OR code LIKE ?', '%'.$values['text'].'%', '%'.$values['text'].'%')
													->where('pid IS NULL')
													->where('visibility', 1)
													->where('trash', 0);
				
			foreach ($products as $product) {
				$thumb = $this->getThumb(2);
				$image = $product->galleries->related('galleries_images', 'galleries_id')->order('highlight DESC, position ASC')->fetch();
				$currency = $this->presenter->currency == 'czk' ? $this->presenter->context->parameters['currency'] : $this->presenter->currency;
				$decimals = $this->presenter->currency == 'czk' ? 0 : 2;
				
				$p = $product->toArray();
				$p['price'] = number_format($p['price'], $decimals, ',', ' ').' '.$currency;
				
				if ($thumb && $image) {
					$p['image'] = $this->presenter->context->httpRequest->url->basePath.'files/galleries/'.$thumb.'_g'.$product->galleries_id.'-'.$image->name;
				}
				else {
					$p['image'] = '';
				}
				
				array_push($response, $p);
			}
			
			$this->presenter->sendResponse(new JsonResponse(array_values($response)));
		}
		
		public function getThumb($place) {
			if ($thumbs = $this->presenter->model->getSectionsThumbs()->where('sections_id', 0)->where('place', array(0, $place))->order('place DESC')->fetch()) {
				return $thumbs->dimension;
			}
			else return false;
		}
	}