<?php
	namespace AdminModule;

	use Nette\Application\Application;

	use Nette\Utils\Image;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Form;

	use Nette\Caching\Cache;

	use Nette\Environment;

	use Nette\Forms\Rendering\BootstrapFormRenderer;
use Nette\Utils\ArrayHash;

	class SettingsPresenter extends BasePresenter {
		public $langs;
		public $lang;
		public $id;
		public $urlID;
		public $urls = array();

		public function startup() {
			parent::startup();
		}

		public function actionDefault () {
			$this->urlID = 0;
		}

		public function actionLanguages () {
			$this->langs = $this->model->getLanguages()->order('position ASC');

			$params = $this->request->getParameters();
			if(!isset($params["grid-order"]) && $this->action == 'languages'){
				unset($params["action"]);
				$params["grid-order"] = "name ASC";
				$this->redirect("Settings:languages",$params);
			}

			$this->urlID = 0;
		}

		public function actionCommon () {
			$this->urlID = 0;
		}

		public function actionRewriteUrl () {
			$this->urlID = 0;

			if ($this->context->parameters['environment'] == 'development') {
				$this->urls['old'] = $this->urls['new'] = null;
			}
			else {
				$url = $this->context->httpRequest->url;

				if ($url->host == "vyvoj.hucr.cz") {
					$this->urls['old'] = 'http://localhost'.$url->basePath;
					$this->urls['new'] = 'http://vyvoj.hucr.cz'.$url->basePath;
				}
				else {
					$this->urls['old'] = 'http://vyvoj.hucr.cz'.$url->basePath;
					$this->urls['new'] = 'http://'.$url->host.'/';
				}
			}
		}

		public function actionAdmin () {
			if (!$this->acl->isAllowed($this->user->identity->role, 'adminSettings')) {
				$this->error('Nemáte oprávnění prohlížet tuto stránku!', '403');
			}

			$this->urlID = 0;
		}

		public function handleDeleteCache () {
			$cache = Environment::getCache('FrontModule');
			$cache->clean(array(Cache::ALL => true));

			$dirCSS = $this->context->parameters['webloader']['cssDefaults']['tempDir'];
			$dirJS = $this->context->parameters['webloader']['jsDefaults']['tempDir'];

			foreach (Finder::findFiles('*.css', '*.js')->in($dirCSS, $dirJS) as $file) {
				unlink($file->getPathname());
			}

			$this->flashMessage('Cache byla smazána');
		}

		public function renderLanguages () {
			$this->template->langs = $this->langs;
		}

		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link('Settings:languages', array($this->id));
		}

		public function addLang ($values) {
			$values['key'] = Strings::lower($values['key']);
			$database = $this->context->database;

			$database->query('ALTER TABLE articles
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD url_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255),
				ADD keywords_'.$values["key"].' VARCHAR(255),
				ADD meta_description_'.$values["key"].' VARCHAR(255),
				ADD description_'.$values["key"].' TEXT,
				ADD text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE categories
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD url_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255),
				ADD keywords_'.$values["key"].' VARCHAR(255),
				ADD description_'.$values["key"].' TEXT,
				ADD text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE editors
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255),
				ADD keywords_'.$values["key"].' VARCHAR(255),
				ADD text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE filestores_files
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE filestores_files_tags
				ADD name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE galleries
				ADD name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE galleries_images
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE galleries_images_tags
				ADD name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE pages
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD url_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255),
				ADD keywords_'.$values["key"].' VARCHAR(255),
				ADD description_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE sections
				ADD name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE sections_fields
				ADD name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE products
				ADD name_'.$values["key"].' VARCHAR(255),
				ADD url_'.$values["key"].' VARCHAR(255),
				ADD title_'.$values["key"].' VARCHAR(255),
				ADD keywords_'.$values["key"].' VARCHAR(255),
				ADD meta_description_'.$values["key"].' VARCHAR(255),
				ADD description_'.$values["key"].' TEXT,
				ADD text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE shop_methods
				ADD name_'.$values["key"].' VARCHAR(255)
			');

			$this->model->getLanguages()->insert($values);

			$this->flashMessage('Jazyk byl přidán');
		}

		public function editLang ($values) {
			$values['key'] = Strings::lower($values['key']);
			$this->lang = $this->model->getLanguages()->wherePrimary($values['id'])->fetch();
			$key = $this->lang->key;
			$database = $this->context->database;

			$database->query('ALTER TABLE articles
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE url_'.$key.' url_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255),
				CHANGE keywords_'.$key.' keywords_'.$values["key"].' VARCHAR(255),
				CHANGE meta_description_'.$key.' meta_description_'.$values["key"].' TEXT,
				CHANGE description_'.$key.' description_'.$values["key"].' TEXT,
				CHANGE text_'.$key.' text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE categories
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE url_'.$key.' url_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255),
				CHANGE keywords_'.$key.' keywords_'.$values["key"].' VARCHAR(255),
				CHANGE description_'.$key.' description_'.$values["key"].' TEXT,
				CHANGE text_'.$key.' text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE editors
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255),
				CHANGE keywords_'.$key.' keywords_'.$values["key"].' VARCHAR(255),
				CHANGE text_'.$key.' text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE filestores_files
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE filestores_files_tags
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE galleries
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE galleries_images
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE galleries_images_tags
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE pages
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE url_'.$key.' url_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255),
				CHANGE keywords_'.$key.' keywords_'.$values["key"].' VARCHAR(255),
				CHANGE description_'.$key.' description_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE sections
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE sections_fields
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255)
			');

			$database->query('ALTER TABLE products
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255),
				CHANGE url_'.$key.' url_'.$values["key"].' VARCHAR(255),
				CHANGE title_'.$key.' title_'.$values["key"].' VARCHAR(255),
				CHANGE keywords_'.$key.' keywords_'.$values["key"].' VARCHAR(255),
				CHANGE meta_description_'.$key.' meta_description_'.$values["key"].' VARCHAR(255),
				CHANGE description_'.$key.' description_'.$values["key"].' TEXT,
				CHANGE text_'.$key.' text_'.$values["key"].' TEXT
			');

			$database->query('ALTER TABLE shop_methods
				CHANGE name_'.$key.' name_'.$values["key"].' VARCHAR(255)
			');

			$this->model->getLanguages()->wherePrimary($this->lang->id)->update($values);

			foreach ($this->model->getLocalization()->where('lang', '_'.$key) as $localization) {
				$localization->update(array('lang' => '_'.$values['key']));
			}

			$this->flashMessage('Jazyk byl upraven');
		}

		public function handleDelete ($id) {
			$ids = (array)$id;

			foreach ($ids as $id) {
				$key = $this->model->getLanguages()->wherePrimary($id)->fetch()->key;

				$database = $this->context->database;

				$database->query('ALTER TABLE articles
					DROP name_'.$key.',
					DROP url_'.$key.',
					DROP title_'.$key.',
					DROP keywords_'.$key.',
					DROP meta_description_'.$key.',
					DROP description_'.$key.',
					DROP text_'.$key
				);

				$database->query('ALTER TABLE categories
					DROP name_'.$key.',
					DROP url_'.$key.',
					DROP title_'.$key.',
					DROP keywords_'.$key.',
					DROP description_'.$key.',
					DROP text_'.$key
				);

				$database->query('ALTER TABLE editors
					DROP name_'.$key.',
					DROP title_'.$key.',
					DROP keywords_'.$key.',
					DROP text_'.$key
				);

				$database->query('ALTER TABLE filestores_files
					DROP name_'.$key.',
					DROP title_'.$key
				);

				$database->query('ALTER TABLE filestores_files_tags
					DROP name_'.$key
				);

				$database->query('ALTER TABLE galleries
					DROP name_'.$key
				);

				$database->query('ALTER TABLE galleries_images
					DROP name_'.$key.',
					DROP title_'.$key
				);

				$database->query('ALTER TABLE galleries_images_tags
					DROP name_'.$key
				);

				$database->query('ALTER TABLE pages
					DROP name_'.$key.',
					DROP url_'.$key.',
					DROP title_'.$key.',
					DROP keywords_'.$key.',
					DROP description_'.$key
				);

				$database->query('ALTER TABLE sections
					DROP name_'.$key
				);

				$database->query('ALTER TABLE sections_fields
					DROP name_'.$key
				);

				$database->query('ALTER TABLE products
					DROP name_'.$key.',
					DROP url_'.$key.',
					DROP title_'.$key.',
					DROP keywords_'.$key.',
					DROP meta_description_'.$key.',
					DROP description_'.$key.',
					DROP text_'.$key
				);

				$database->query('ALTER TABLE shop_methods
					DROP name_'.$key
				);

				$this->model->getLanguages()->wherePrimary($id)->delete();

				$texts = array();

				foreach ($this->model->getLocalization()->where('lang', '_'.$key) as $localization) {
					$localization->delete();
					$texts[] = $localization->text_id;
				}

				$this->model->getLocalizationText()->where('id', $texts)->delete();

				$this->flashMessage('Jazyk(y) byl smazán');
			}
		}

		public function handleVisibility($id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getLanguages()->wherePrimary($id)->update(array("visibility" => $vis));

			$this->flashMessage('Zobrazení jazyku změněno');
			$this->invalidateControl('languages');
		}

		public function handleHighlight($id, $vis) {
			$this->model->getLanguages()->update(array('highlight' => 0));

			$vis = $vis == 1 ? 0 : 1;
			$this->model->getLanguages()->wherePrimary($id)->update(array("highlight" => $vis));

			$this->flashMessage('Defaultní jazyk změněn');
			$this->invalidateControl('languages');
		}

		public function createComponentFavicon () {
			$form = new Form();

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup('Nahrání favicon');
			$form->addUpload('favicon', 'Ikona:');
// 				->addRule(Form::MIME_TYPE, 'Vyberte pouze soubor s příponou .png, nebo .ico', array('image/png', 'image/x-icon'))
// 					->addRule(Form::MAX_FILE_SIZE, 'Maximální velikost souboru může být 2MB', 2 * 1024 * 1024);

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('upload', 'Nahrát');

			$form->onSuccess[] = callback($this, 'uploadFavicon');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function uploadFavicon ($form) {
			$this->handleDeleteFavicon(false);

			$files = $this->context->httpRequest->files;
			$temp = $files['favicon']->move(WWW_DIR.'/'.$files['favicon']->getSanitizedName());

			$favicon = Image::fromFile($temp);
			$favicon->resize(32, 32);
			$favicon->save(WWW_DIR.'/favicon.ico', 100, Image::PNG);

			unlink($temp);

			$this->redirect('this');
		}

		public function handleDeleteFavicon ($redirect = true) {
			if (file_exists(WWW_DIR.'/favicon.ico')) {
				unlink(WWW_DIR.'/favicon.ico');
			}

			if ($redirect) {
				$this->redirect('this');
			}
		}

		/**
		 * Formulář pro přepsání adres při přechodu na ostrý server
		 * @return Form
		 */
		public function createComponentRewrite () {
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal');

			$form->addGroup('Přepsání adres');
			$form->addText('old', 'Stará adresa:')
				->setRequired()
				->setDefaultValue($this->urls['old']);

			$form->addText('new', 'Nová adresa')
				->setRequired()
				->setDefaultValue($this->urls['new']);

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('rewrite', 'Přepsat');

			$form->onSuccess[] = callback($this, 'rewriteUrl');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		/**
		 * Fukce pro přepsání adres při přechodu na ostrý server
		 * @param Form
		 */
		public function rewriteUrl ($form) {
			$values = $form->values;

			foreach ($this->context->database->query('SHOW TABLES') as $table) {
				foreach ($this->context->database->query('SHOW COLUMNS FROM '.$table[0]) as $column) {
					if ($column->Type == 'text') {
						foreach ($this->context->database->query('SELECT id, '.$column->Field.' FROM '.$table[0].' WHERE '.$column->Field.' LIKE "%'.$values['old'].'%"') as $row) {
							$field = $column->Field;
							$url = Strings::replace($row->$field, '('.$values['old'].')', $values['new']);

							$this->context->database->query('UPDATE '.$table[0].' SET '.$column->Field.'="'.addslashes($url).'" WHERE id="'.$row->id.'"');
						}
					}
				}
			}
		}

		public function createComponentGrid () {
			return new LanguagesGrid($this->langs);
		}

		public function createComponentGa () {
			$form = new Form();

			$url = $this->context->httpRequest->url;

			if ($url->host == "vyvoj.hucr.cz") {
				$analyticsUrl = str_replace(array('www.', 'www/', '/'), '', $url->basePath);
			} else {
				$analyticsUrl = str_replace('www.', '', $url->host);
			}

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup('Google');
			$form->addText('analyticsUID', 'Analytics kód:');
			$form->addText('analyticsURL', 'Analytics adresa:')
				->setDefaultValue($analyticsUrl)
				->setOption('description', 'Zadejte ve tvaru '.$analyticsUrl.' (bez www.)');
			$form->addText('googleAPIKey', 'Google API key:');
			$form->addText('webmasterToolsVerification', 'Webmaster Tools:');

			$form->addGroup('Seznam.cz');
			$form->addText('sklikConversion', 'Sklik klíč pro konverze');

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', $this->settings ? 'Upravit' : 'Přidat');

			$form->onSuccess[] = callback($this, $this->settings ? 'editGa' : 'saveGa');

			if ($this->settings) {
				$form->setValues($this->settings);
			}

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function saveGa ($form) {
			$values = $form->values;

			$this->model->getSettings()->insert($values);

			$this->flashMessage('Kód Google Analytics byl úspěšně přidán');
			$this->redirect('this');
		}

		public function editGa ($form) {
			$values = $form->values;

			$this->settings->update($values);

			$this->flashMessage('Kód Google Analytics byl úspěšně upraven');
			$this->redirect('this');
		}



		/* socialni site*/
		public function createComponentSocial () {
			$form = new Form();

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup('AddThis');

			$form->addCheckbox('addthisActive', 'Aktivní');
			$form->addText('addthis', 'ID:')
				//->setRequired(); //neni potreba, resp. pak to nejde vymazat, coz by asi bylo vhodny
				;

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', $this->settings ? 'Upravit' : 'Přidat');

			$form->onSuccess[] = callback($this, $this->settings ? 'editSocial' : 'saveSocial');

			if ($this->settings) {
				$form->setValues($this->settings);
			}

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function saveSocial ($form) {
			$values = $form->values;

			$this->model->getSettings()->insert($values);

			$this->flashMessage('Kód AddThis byl úspěšně přidán');
			$this->redirect('this');
		}

		public function editSocial ($form) {
			$values = $form->values;

			$this->settings->update($values);

			$this->flashMessage('Kód AddThis byl úspěšně upraven');
			$this->redirect('this');
		}

		/**
		 * factory for module visibility switch
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentAdminSettingsForm () {
			$form = new Form();

			$form->addGroup('Moduly');
			$form->addCheckbox('eshop', 'E-shop');

			//$form->addCheckbox('mailing', 'Mailing');

			//$form->addCheckbox('booking', 'Rezervace');

			$form->addGroup('Chování webu');

			$form->addCheckbox('singlepage', 'Single page web');

			$form->addGroup('Komponenty');

			$form->addCheckbox('seo_assist', 'SEO Asistent');

			$form->addCheckbox('context_help', 'Kontextová nápověda');

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', 'Upravit');

			$form->onSuccess[] = $this->adminSettingsFormSucceeded;

			$form->setRenderer(new BootstrapFormRenderer());

			if ($this->settings) {
				$form->setValues($this->settings);
			}

			return $form;
		}

		/**
		 * switch modules
		 * @param \Nette\Application\UI\Form $form
		 * @param ArrayHash $values
		 */
		public function adminSettingsFormSucceeded ($form, $values) {
			if ($this->settings) {
				$this->settings->update($values);
			}
			else {
				$this->model->getSettings()->insert($values);
			}

			$this->redirect('this');
		}

		/**
		 * change email addresses for sending contact form
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentContactFormEmailsForm () {
			$form = new Form();

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup('E-maily pro kontaktní formulář');
			$form->addText('contact_to', 'Komu:')
// 				->setRequired('Vyplňte prosím e-mail!')
				->addCondition(Form::FILLED)
					->addRule(Form::EMAIL, 'Chybný formát e-mailu');

			$form->addText('contact_cc', 'Kopie:')
				->addCondition(Form::FILLED)
					->addRule(Form::EMAIL, 'Chybný formát e-mailu');

			$form->addText('contact_bcc', 'Skrytá kopie:')
				->addCondition(Form::FILLED)
					->addRule(Form::EMAIL, 'Chybný formát e-mailu');

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', 'Upravit');

			$form->onSuccess[] = $this->adminSettingsFormSucceeded;

			$form->setRenderer(new BootstrapFormRenderer());

			if ($this->settings) {
				$form->setDefaults($this->settings);
			}

			return $form;
		}

		/**
		 * factory for changing page headers
		 */
		public function createComponentTitles () {
			$form = new Form();

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup('Doplňky pro titulky');
			$form->addText('title_editors', 'Stránka');

			$form->addText('title_articles', 'Článek');

			$form->addText('title_articles_categories', 'Kategorie článků');

			$form->addText('title_products', 'Produkt');

			$form->addText('title_products_categories', 'Kategorie produktů');

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', 'Upravit');

			$form->onSuccess[] = $this->adminSettingsFormSucceeded;

			$form->setRenderer(new BootstrapFormRenderer());

			if ($this->settings) {
				$form->setDefaults($this->settings);
			}

			return $form;
		}
	}