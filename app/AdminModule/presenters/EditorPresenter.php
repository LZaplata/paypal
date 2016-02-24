<?php
	namespace AdminModule;

	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use Nette\Application\UI\Multiplier;
	use Nette\Utils\Strings;

	class EditorPresenter extends ContentPresenter {
		public $editor;
		public $fields;
		public $versions;

		public function actionEdit ($id, $sid, $version = null) {
			if (!$this->acl->isAllowed($this->user->getIdentity()->role, 'section_'.$sid)) {
				$this->error();
			}

			$this->section = $this->model->getSections()->wherePrimary($sid)->fetch();

			if ($this->section->versions && $this->action == 'edit') {
				$row = $this->model->getEditors()->where('pages_modules_id', $id)->where('pid != ?', 0)->order('date DESC')->fetch();
				if ($version == null && $row) {
					$this->redirect('this', array('version' => $row['id']));
				}
			}

			$this->actionDefault();
			$this->id = $id;

			if ($version != null && $this->section->versions) {
				$this->editor = $this->model->getEditors()->wherePrimary($version)->fetch();
			}
			else {
				$this->editor = $this->model->getEditors()->where('pages_modules_id', $id)->fetch();
			}

			$this->fields = $this->model->getSectionsFields()->where('sections_id', $sid)->where('visibility', 1)->order('position ASC');

			if ($this->editor) {
				$this->versions = $this->model->getEditors()->select('users.*, editors.*')->where('pages_modules_id', $this->editor->pages_modules_id)->order('date DESC');
			}

			$this->urlID = $id;
		}

		public function renderEdit () {
			$this->renderDefault();
		}

		public function actionGallery ($id, $sid, $pid) {
			$this->actionEdit($pid, $sid);

			if (!isset($this->request->getParameters()['gallery-grid-order'])) {
				$this->redirect('this', array('gallery-grid-order' => 'position ASC'));
			}

			$this->id = $id;
			$this->sid = $sid;
			$this->urlID = $pid;
			$this->section = $this->model->getSections()->wherePrimary($sid)->fetch();
		}

		public function renderGallery () {
			$this->renderDefault();
		}

		public function actionFiles ($id, $sid, $pid) {
			$this->actionEdit($pid, $sid);

			if (!isset($this->request->getParameters()['files-grid-order'])) {
				$this->redirect('this', array('files-grid-order' => 'position ASC'));
			}

			$this->id = $id;
			$this->sid = $sid;
			$this->urlID = $pid;
		}

		public function renderFiles () {
			$this->renderDefault();
		}

		public function createComponentAddForm() {
			return new Multiplier(function ($key) {
				$key = $key == 'cs' ? '' : '_'.$key;

				$form = new Form();

				$form->getElementPrototype()->class('form-horizontal');

				$form->addGroup('Základní informace');
	 			if ($this->section->visName) {
					$form->addText('name'.$key, 'Jméno:')
						->setRequired('Vyplňte prosím název článku!')
						->setAttribute('class', 'input-name');
		// 				->setAttribute('onkeyup', 'createUrl()');
	 			}

	// 			$form->addText('url', 'URL:')
	// 				->setRequired('Vyplňte prosím url stránky!')
	// 				->setAttribute('class', 'input-url');

				/*if ($this->section->title) {
					$form->addText('title'.$this->lang, 'Titulek:')
						->setRequired('Vyplňte prosím titulek stránky!');
				}*/

				/*if ($this->section->keywords) {
					$form->addText('keywords'.$this->lang, 'Klíčová slova:')
						->setRequired('Vyplňte prosím klíčová slova!');
				}*/

				if ($this->section->date) {
					$form->addText('altDate', 'Datum:')
						->setValue($this->editor ? $this->editor->date->format('j.n.Y') : date("j.n.Y"))
						->getControlPrototype()->class('date');
				}

				$form->addHidden('date', !$this->editor ? date('Y-m-d G:i') : $this->editor->date);

				if ($this->section->text) {
					$form->addGroup('Text');
					$form->addTextarea('text'.$key, 'Text:')
						->getControlPrototype()->class('tinymce');
				}

				if (count($this->fields)) {
					$form->addGroup('Speciální položky');
					foreach ($this->fields as $field) {
						switch ($field->type) {
							case 1:
								$form->addText($field->title.$key, $field->name);
								break;
							case 2:
								$form->addTextarea($field->title.$key, $field->name);
								break;
							case 3:
								$form->addCheckbox($field->title.$key, $field->name);
								break;
							case 4:
								preg_match_all('~[,;]?([a-z0-9]+)[,;]?~', $field->values, $values);

								$form->addSelect($field->title.$key, $field->name, $values[1])
									->setPrompt('--Vyberte možnost--');
								break;
							case 5:
								$form->addTextarea($field->title.$key, $field->name)
									->getControlPrototype()->class('tinymce');
						}
					}
				}

				$form->addHidden('users_id', $this->user->id);

				$form->addGroup()
					->setOption('container', 'fieldset class="submit"');

				if ($this->section->versions && $this->editor) {
					$form->addSubmit('addVersion', 'nová verze')
						->onClick[] = callback($this, 'addVersion');
				}
				$form->addSubmit('add', $this->editor ? 'Upravit' : 'Vytvořit')
					->onClick[] = callback($this, $this->editor ? 'editEditor' : 'addEditor');

				if ($this->editor) {
	// 				unset($this->editor->users_id);

					$form->setValues($this->editor);
					$form->setValues(array('date' => $this->editor->date->format('Y-m-d G:i')));
				}

				$form->setRenderer(new BootstrapFormRenderer());

				return $form;
			});
		}

		public function addEditor ($button) {
			$values = $button->parent->values;
			$values['sections_id'] = $this->section->id;
			$values['pages_modules_id'] = $this->id;

			$values['galleries_id'] = $this->model->getGalleries()->insert(array());
			$values['filestores_id'] = $this->model->getFilestores()->insert(array());

			unset($values['altDate']);

			$this->model->getEditors()->insert($values);

			$this->flashMessage('Textové pole bylo uloženo!');
			$this->redirect('this');
		}

		public function editEditor ($button) {
			$values = $button->parent->httpData;
			$values['sections_id'] = $this->section->id;

			unset($values['altDate']);
			unset($values['add']);
			unset($values['do']);

			$this->editor->update($values);

			$this->flashMessage('Textové pole bylo změněno!');
			$this->redirect('this');
		}

		public function addVersion ($form) {
			$values = $form->parent->httpData;
			$values['sections_id'] = $this->section->id;

			unset($values['altDate']);
			unset($values['addVersion']);
			unset($values['date']);

			if (!$this->model->getEditors()->where($values)->where('id', $this->editor->id)->fetch()) {
				$values['pid'] = $this->id;
				$values['date'] = date('Y-m-d H:i:s');

				$editor = $this->editor->toArray();
				unset($editor['id']);

				$version = $this->model->getEditors()->insert($editor);

				$version->update($values);

				$this->redirect('this', array('version' => $version->id));
			}
			else $this->redirect('this');
		}

		public function handleDeleteVersion ($id, $vid) {
			$this->presenter->model->getEditors()->wherePrimary($vid)->delete();

			$this->presenter->redirect('this', array('version' => null));
		}

		public function createComponentSeo ($name) {
			return new Seo($this, $name);
		}

		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}

		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}

		public function createComponentFiles ($name) {
			return new \FilesPresenter($this, $name);
		}

		public function createComponentVersions () {
			return new VersionsGrid($this->versions);
		}

		public function handleCopy ($id, $sid, $lang) {
			foreach ($this->editor as $key => $val) {
				if (Strings::match($key, '/_'.$lang.'/')) {
					$index = Strings::replace($key, '/_'.$lang.'/');

					$values[$key] = $this->editor->$index;
				}
			}

			if (count($values)) {
				$this->editor->update($values);
			}

			$this->redirect('this');
		}
	}