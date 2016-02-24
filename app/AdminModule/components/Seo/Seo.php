<?php
	namespace AdminModule;

	use Nette\Application\UI\Control;
	use Nette\Application\UI\Multiplier;

	class Seo extends Control {

		public $presenterName;
		public $keywords;
		public $keywordsExtended;
		public $langs;
		public $elements;
		public $allowedBaseElements = array('name', 'title', 'meta_description');
		public $allowedContentElements = array('text', 'description');

		public function __construct($parent, $name) {
			parent::__construct($parent, $name);

			$this->presenterName = $this->presenter->presenterName;

			// pro jistotu nejakyho foreach udelame ze vseho pole
			$this->langs = $this->keywords = $this->keywordsExtended = $this->elements = array();

			foreach ($this->presenter->components as $component) {
				if ($component instanceof \Nette\Application\UI\Multiplier) {
					foreach ($component->getComponents() as $lang) {
						// vytaham vsechny jazyky a k nim prislusna klicova slova
						$this->langs[] = $lang->name;
						$this->keywords[$lang->name] = $this->getKeywords($lang->name);
						$this->keywordsExtended[$lang->name] = $this->getKeywordsExtended($lang->name);

						// vytaham vsechny rozumny pole ze zakladniho nastaveni
						if($lang->getGroups()['Základní informace']){
							foreach ($lang->getGroups()['Základní informace']->getControls() as $control) {
								if(in_array(preg_replace('/_[a-z]{2}$/i', '', $control->name), $this->allowedBaseElements)) {
									$this->elements[$lang->name]['base'][] = (object) array('name' => $control->name, 'label' => $control->label, 'title' => (preg_replace('/_[a-z]{2}$/i', '', $control->name) == 'title' || preg_replace('/_[a-z]{2}$/i', '', $control->name) == 'name'));
								}
								if(in_array(preg_replace('/_[a-z]{2}$/i', '', $control->name), $this->allowedContentElements)) {
									$this->elements[$lang->name]['content'][] = (object) array('name' => $control->name, 'label' => $control->label, 'tinymce' => (sizeof(array_keys($control->getControlPrototype()->class, 'tinymce', true)) > 0) ? true : false);
								}
							}
						}

						// vytaham vsechny rozumny pole z textovy casti
						if($lang->getGroups()['Text']){
							foreach ($lang->getGroups()['Text']->getControls() as $control) {
								if(in_array(preg_replace('/_[a-z]{2}$/i', '', $control->name), $this->allowedContentElements)) {
									$this->elements[$lang->name]['content'][] = (object) array('name' => $control->name, 'label' => $control->label, 'tinymce' => (sizeof(array_keys($control->getControlPrototype()->class, 'tinymce', true)) > 0) ? true : false);
								}
							}
						}
					}
				}
			}
		}

		public function render () {
			$this->template->setFile(__DIR__.'/seo.latte');

			$this->template->langs = $this->langs;
			$this->template->kw = $this->keywords;
			$this->template->kwe = $this->keywordsExtended;
			$this->template->elements = $this->elements;
			$this->template->allowedBaseElements = $this->allowedBaseElements;
			$this->template->allowedContentElements = $this->allowedContentElements;

			$this->template->render();
		}

		/**
		 * @return array
		 */
		public function getKeywords ($lang) {
			$kw = array();
			switch ($this->presenterName) {
				case "Editor":
					$kw = $this->parseKeywordsFromString($this->presenter->editor->pages_modules->pages->{'keywords'.($lang == 'cs' ? '' : '_'.$lang)});
					break;
 				case "Articles": case "Categories":
 					if($this->presenterName == "Articles") $contentType = 'article';
 					if($this->presenterName == "Categories") $contentType = 'category';

 					$pagesModules = $this->presenter->pagesModel->getModulesBySid($this->presenter->{$contentType}->sections_id)->fetchPairs('id', 'pages_id');
 					$pages = $this->presenter->pagesModel->getAllByID(array_values($pagesModules));
 					$kw = array();

 					foreach ($pages as $page) {
 						$kw = array_merge($kw, $this->parseKeywordsFromString($page->{'keywords'.($lang == 'cs' ? '' : '_'.$lang)}));
 					}
 					break;
			}

			$kw = array_unique($kw);
			sort($kw);
			return $kw;
		}

		/**
		 * @return array
		 */
		public function getKeywordsExtended($lang) {
			$ext = array();

			// pro cizi jazyky to zatim neresime, pze nemame kde krast slovnik
			if($lang != 'cs') return $ext;

			foreach ($this->keywords[$lang] as $kw) {
				// zjistim, jestli najdu primo koren/stem
				$rows = $this->presenter->model->getSeoKw()->where('main', $kw)->fetchAll();
				if($rows) {
					// pokud jo, tak vsechny jeho variace nacpu do vysledku (tady bez korene, pze ten uz je v hlavnich slovech a stejne bych ho pak na konci smazal)
					foreach ($rows as $row) $ext[] = $row->vars;
				} else {
					// pokud ne, tak se podivam, jestli mam dany slovo ulozeny jako variaci
					$row = $this->presenter->model->getSeoKw()->where('vars', $kw)->fetch();
					if($row) {
						// pokud jo, tak vytaham vsechny variace, ktery jsou s danym slovem spojeny pres koren/stem
						$rows = $this->presenter->model->getSeoKw()->where('main', $row->main)->fetchAll();
						// pridam je do vysledku
						foreach ($rows as $row) $ext[] = $row->vars;
						// a pridam i koren/stem, pres ktery jsem nasel spojeni
						$ext[] = $row->main;
					} else {
						// pokud ne, koukam na pravidla po variantach
						\phpQuery::newDocumentFileHTML('http://www.pravidla.cz/hledej.php?qr='.urlencode(iConv("UTF-8", "CP1250", $kw)), 'ISO-8859-2');

						// ziskam koren/stem
						$main = pq('div.dcap b')->text();
						// ziskam varianty
						$vars = explode(' ', trim(str_replace(' ~ ', ' ', pq('div.dcap i')->text())));

						/*
						dump('http://www.pravidla.cz/hledej.php?qr='.urlencode(iConv("UTF-8", "CP1250", $kw)));
						dump($kw);
						dump($main);
						dump($vars);
						*/
						if($kw == $main || in_array($kw, $vars)) {
							// pokud aspon jedno z nalezenych slov odpovida hledanemu, tak to vsechno pridam do DB
							// a zaroven vsechny nalezeny pridam do vysledku
							// duplicitu ve vysledku neresim, pze nevim, jestli hledany slovo na pravidlech bylo primo koren/stem nebo varianta
							// duplicitu ve vysledku komplet vyresim az nakonec
							foreach ($vars as $var) {
								$ext[] = $var;
								$this->presenter->context->database->query("INSERT INTO seo_kw (main,vars) VALUES ('$main', '$var');");
							}

							// a jeste ten koren/stem
							$ext[] = $main;
						} else {
							// pokud je to slovo (napr. "e-shop"), na ktery sice mozna nejaky vysledky mam, ale nic z nalezenyho se s tim mym neshoduje,
							// tak pridam jen to svoje jako koren/stem i variaci, abych ho priste uz na pravidlech nehledal
							// a takovy slovo ani nepridavam do vysledku, pze je jen v hlavnich slovech
							$this->presenter->context->database->query("INSERT INTO seo_kw (main,vars) VALUES ('$kw', '$kw');");
						}
					}
				}
			}

			// vratim vsechny nalezeny slova unikatni a navic bez hlavnich klicovejch slov
			$ext = array_values(array_diff(array_unique($ext), $this->keywords[$lang]));
			sort($ext);
			return $ext;
		}

		/**
		 *
		 * @param string $s
		 * @return array
		 */
		public function parseKeywordsFromString ($s) {
			$kws = array();

			foreach (explode(',', $s) as $item) {
				foreach (explode(' ', trim($item)) as $word) {
					$kws[] = trim($word);
				}
			}

			return $kws;
		}
	}