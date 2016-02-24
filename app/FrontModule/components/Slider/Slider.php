<?php
	namespace FrontModule;

	use Nette\Application\UI\Control;
	use Tracy\Debugger;

	class Slider extends Control {
		public $id;

		public function __construct($parent, $name) {
			parent::__construct();
		}

		public function getImages () {
			return $this->presenter->model->getGalleriesImages()->where('galleries_id', $this->id)->where('visibility', 1)->order('highlight DESC, position ASC');
		}

		public function getThumbsDimensions ($place) {
			if ($thumb = $this->presenter->model->getArticles()->where('galleries_id', $this->id)->fetch()->sections->related('sections_thumbs')->where('place', array(0, $place))->fetch()) {
				return $thumb->dimension;
			}
			else return false;
		}

		public function getArticles () {
			$allArticles = $this->presenter->model->getArticlesPages()->fetchPairs('id', 'articles_id');
			$pageArticles = $this->presenter->model->getArticlesPages();

			if ($this->presenter->isLinkCurrent(':Front:Page:')) {
				if (count($this->presenter->pages) > 1) {
					$pageArticles->where('pages.url', $this->presenter->pages[0]);
				}
				else {
					$pageArticles->where('pages_id', $this->presenter->page->id);
				}
			}
			else {
				$pageArticles->where('pages.url', $this->presenter->eshop);
			}

			$pageArticles = $pageArticles->fetchPairs('id', 'articles_id');
			$articles = $this->presenter->model->getArticles()
													->where('sections_id', $this->id)
													->where('visibility', 1)
													->order('highlight DESC, position ASC');

			$articles->where('id NOT IN ? OR id IN ?', array_values($allArticles), array_values($pageArticles));

			return $articles;
		}

		public function render ($gid) {
			$this->id = $gid;

			$this->template->setFile(__DIR__.'/slider.latte');

			$this->template->images = $this->getImages();
			$this->template->thumb = $this->getThumbsDimensions(1);

			$this->template->render();
		}

		public function renderArticles ($sid = null) {
			if (!is_null($sid)) {
				// pokud nastavim sid primo v sablone
				$this->id = $sid;
			} else {
				// pokud ne, pokusim se najit takovou sekci
				$sid = $this->presenter->model->getSections()->where('slider', 1)->fetch()->id;
			}

			if(!is_null($sid)) {
				// pokud mam sid, tak zobrazim slider
				$this->template->setFile(__DIR__.'/sliderArticles.latte');

				$this->template->images = $this->getImages();
				$this->template->articles = $this->getArticles();

				$this->template->render();
			} else {
				// pokud nemam, hodim o tom hlasku
				Debugger::log('Nenalezena sekce pro slider.');
				throw new \Exception("Nenalezena sekce pro slider.");
			}
		}
	}