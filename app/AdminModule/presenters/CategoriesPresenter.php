<?php
	namespace AdminModule;
	
	use Nette\Utils\Strings;

	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;
use Nette\Application\UI\Multiplier;
		
	class CategoriesPresenter extends ContentPresenter {
		public $categories;
		public $category;
		public $pid;
		public $cats;
		
		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link('Categories:view', array($this->id));
		}
		
		public function getCategories($pid) {
			return $this->model->getCategories()->where(array('sections_id' => $this->id, 'pid' => $pid))->order('position ASC');
		}
		
		public function actionView ($id) {
			$this->actionDefault();
			
			$this->id = $id;
			$this->urlID = $id;
			$this->sid = $id;
			
			$this->categories = $this->model->getCategories()->where(array('sections_id' => $this->id))->order('position ASC');
		}
		
		public function actionAdd ($id, $pid) {
			$this->actionDefault();		
			
			$this->id = $id;
			$this->urlID = $id;
			$this->pid = $pid;
		}
		
		public function actionEdit ($id, $sid, $version) {
			$this->actionDefault();
			
			$this->id = $id;			
			$this->category = $this->model->getCategories()->wherePrimary($id)->fetch();
			$this->urlID = $this->category->sections_id;
			$this->sid = $this->category->sections_id;
		}
		
		public function actionGallery ($id, $sid, $urlID) {
			$this->actionDefault();
			
			if (!isset($this->request->getParameters()['gallery-grid-order'])) {
				$this->redirect('this', array('gallery-grid-order' => 'position ASC'));
			}
			
			$this->id = $id;
			$this->sid = $sid;
			$this->urlID = $sid;
		}
		
		public function actionPosition ($id, $pid) {
			$this->actionDefault();
			
			$this->id = $id;
			$this->urlID = $id;
			$this->categories = $this->getCategories($pid);
		}
		
		public function renderView () {
			$this->renderDefault();
			
			$this->template->categories = $this->categories;
		}
		
		public function renderAdd () {
			$this->renderDefault();
		}
		
		public function renderEdit () {
			$this->renderDefault();
			
			$this->setView('add');
		}
		
		public function renderGallery () {
			$this->renderDefault();
		}
		
		public function renderPosition () {
			$this->renderDefault();
			
			$this->template->categories = $this->categories;
		}
		
		public function createComponentAddForm () {
			return new Multiplier(function ($key) {
				$key = $key == 'cs' ? '' : '_'.$key;
				
				$form = new Form();
				
				$form->getElementPrototype()->addClass('form-horizontal');
				
				$form->addGroup('Základní informace');
				$form->addText('name'.$key, 'Jméno:')
					->setRequired('Vyplňte prosím jméno kategorie!')
					->setAttribute('class', 'input-name');
				
				$form->addText('url'.$key, 'URL:')
					->setRequired('Vyplňte prosím url stránky!')
					->setAttribute('class', 'input-url');
					
				$form->addText('title'.$key, 'Titulek:')
					->setRequired('Vyplňte prosím titulek stránky!');
				
				$form->addText('keywords'.$key, 'Klíčová slova:')
					->setRequired('Vyplňte prosím klíčová slova!');
					
				$form->addText('description'.$key, 'Meta popisek:')
					->setRequired('Vyplňte prosím meta popisek!');
				
				$form->addTextarea('text'.$key, 'Text:')
					->getControlPrototype()->class('tinymce');
				
				$form->addGroup()
					->setOption('container', 'fieldset class="submit"');
				$form->addSubmit('add', $this->category ? 'Upravit' : 'Vytvořit');
				
				$form->addHidden('referer', $this->getReferer());
					
				$form->onSuccess[] = callback($this, $this->category ? 'editCategory' : 'addCategory');
				
				if ($this->category) {
					$form->setValues($this->category);
				}
				
				$form->setRenderer(new BootstrapFormRenderer());
				
				return $form;
			});
		}
		
		public function addCategory ($form) {
			$values = $form->getValues();
			$referer = $values['referer'];
			
			$lastPosition = $this->model->getCategories()->where(array('sections_id' => $this->id, 'pid' => $this->pid == null ? 0 : $this->pid))->order('position DESC')->fetch();
			
			$values['sections_id'] = $this->id;
			$values['pid'] = $this->pid == null ? 0 : $this->pid;
			$values['url'] = Strings::webalize($values['name']);
			$values['position'] = !$lastPosition ? 0 : $lastPosition->position+1;
			$values['galleries_id'] = $this->model->getGalleries()->insert(array());
			
			unset($values['referer']);
			$this->model->getCategories()->insert($values);
			
			$this->flashMessage('Kategorie byla přidána');
			$this->redirectUrl($referer);
		}
		
		public function editCategory ($form, $values) {
			$referer = $values['referer'];
			
			$values['url'] = !isset($values['name']) ? $this->category->url : Strings::webalize($values['name']);
			unset($values['add']);
			unset($values['referer']);
			
			$this->model->getCategories()->wherePrimary($this->id)->update($values);
			
			$this->flashMessage('Kategorie byla upravena');
			$this->redirectUrl($referer);
		}

		public function createComponentSeo ($name) {
			return new Seo($this, $name);
		}
		
		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}
		
		public function handleVisibility($id, $cid, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getCategories()->where("id", $cid)->update(array("visibility" => $vis));
				
			$this->flashMessage('Nastavení zobrazení kategorie změněno!');
		}
		
		public function handleHighlight($id, $cid, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getCategories()->where('id', $cid)->update(array("highlight" => $vis));
		
			$this->flashMessage('Nastavení zvýraznění kategorie změněno!');
		}
		
		public function handleDelete($id, $cid) {
			$ids = (array)$cid;
			
			foreach ($ids as $val) {
				$category = $this->model->getCategories()->wherePrimary($val)->fetch();
				$imgs = $this->model->getGalleriesImages()->where('galleries_id', $category->galleries_id)->fetchPairs('id', 'id');
				
				$this['gallery']->handleDelete($category->galleries_id, $imgs);
				 
				$this->model->getGalleries()->wherePrimary($category->galleries_id)->delete();

				$this->model->getCategories()->where('pid', $category->id)->update(array('pid' => $category->pid, 'position' => 1000));
			}
			
			$this->model->getCategories()->where('id', $cid)->delete();
			$this->model->getArticlesCategories()->where('categories_id', $cid)->delete();
		
			$this->flashMessage('Položka byla smazána!');
		}
		
		public function handleChangeOrder () {
			$positions = $_GET['positions'];
				
			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->model->getCategories()->wherePrimary($value)->update($values);
			}
				
			$this->flashMessage('Pořadí bylo změněno');
		}
		
		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}
		
		public function createComponentGrid () {
			return new CategoriesGrid($this->categories);
		}
		
		/**
		 * call function and add prompt option
		 */
		public function getCategoriesSelect () {
			$this->cats[0] = '--Žádná--';
			$this->getCategoriesTree();
			
			return $this->cats;
		}
		
		/**
		 * create options for categories select in tree view
		 * @param int $pid
		 * @param string $level
		 */
		public function getCategoriesTree ($pid = 0, $level = '') {
			$categories = $this->model->getCategories()->where('sections_id', $this->sid)->where('pid', $pid);
			
			foreach ($categories as $category) {
				$this->cats[$category->id] = $level.$category->name;
				
				$this->getCategoriesTree($category->id, $level.'- ');
			}
		}
		
		/**
		 * move given categories as parents of category
		 * @param int $id
		 */
		public function handleMoveCategories ($id) {
			$ids = (array)$id;
			$pid = $_POST['grid']['action']['pid'];
			
			$this->model->getCategories()->where('id', $ids)->update(array('pid' => $pid));
		}
		
		public function handleCopy ($id, $sid, $lang) {
			foreach ($this->category as $key => $val) {
				if (Strings::match($key, '/_'.$lang.'/')) {
					$index = Strings::replace($key, '/_'.$lang.'/');
					
					$values[$key] = $this->category->$index;
				}
			}
			
			if (count($values)) {
				$this->category->update($values);
			}
			
			$this->redirect('this');
		}
	}