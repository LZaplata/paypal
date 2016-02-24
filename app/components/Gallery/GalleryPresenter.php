<?php
	use Nette\Utils\Finder;

use AdminModule\FilesGrid;

use Nette\Application\UI\Control;

	use Nette\Utils\Image;

	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;

	class GalleryPresenter extends Control {
		public $gallery;
		public $images;
		public $image;
		public $article;
		public $dimension;
		public $dimensions;
		public $edit;

		public function __construct($parent, $name) {
			parent::__construct($parent, $name);

			$this->getImages();
		}

		public function getImages () {
			$this->gallery = $this->presenter->model->getGalleries()->wherePrimary($this->presenter->id)->fetch();
			$this->images = $this->presenter->model->getGalleriesImages()->where('galleries_id', $this->presenter->id)->order('position ASC');

			$this->dimension = $this->presenter->model->getSectionsThumbs()->where('sections_id', $this->presenter->sid)->order('dimension ASC')->fetch();
			$this->dimensions = $this->getDimensions();
		}

		public function getDimensions() {
			return $this->presenter->model->getSectionsThumbs()->where('sections_id', $this->presenter->sid);
		}

		public function getWidthHeight($dimension) {
			$info = preg_match('/([0-9]+).([0-9]+)/', $dimension, $dimensions);
			return $dimensions;
		}

		public function render () {
			if ($this->edit) {
				if ($this->edit !== true) {
					$this->template->setFile(__DIR__.'/position.latte');
				}
				else {
					$this->template->setFile(__DIR__.'/editImage.latte');
				}
			}
			else {
				$this->template->setFile(__DIR__.'/view.latte');
			}

			$this->template->images = $this->images;
			$this->template->thumb = $this->dimension ? $this->dimension->dimension : '';

			$this->template->render();
		}

		public function handleEditImage ($id) {
			$this->presenter->section = $this->presenter->sid != 0 ? $this->presenter->model->getSections()->wherePrimary($this->presenter->sid)->fetch() : $this->presenter->model->getShopSettings()->order('id ASC')->fetch();
			$this->image = $this->presenter->model->getGalleriesImages()->wherePrimary($id)->fetch();
			$this->presenter->id = $id;

			$this->edit = true;

			$this->template->image = $this->image;
			$this->template->extension = pathinfo($this->image->name, PATHINFO_EXTENSION);
			$this->template->dimensions = $this->presenter->model->getSectionsThumbs()->where('sections_id', $this->presenter->sid);
		}

		public function createComponentAddForm () {
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal');

			$form->addGroup('Nastavení galerie');
			$form->addText('name', 'Jméno:')
				->setAttribute('class', 'input-name');

			$form->addText('lmt', 'Limit:');
			$form->addSelect('order', 'Řadit podle:', array(1 => 'ID', 2 => 'Pořadí', 3 => 'Datum'));
			$form->addSelect('direction', 'Směr:', array(1 => 'Sestupně', 2 => 'Vzestupně'));

			$form->addCheckbox('paginator','Povolit stránkování?');

			$form->addGroup('')
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', !$this->gallery ? 'Vytvořit' : 'Upravit');

			$form->onSuccess[] = callback ($this, 'editGallery');

			if ($this->gallery) {
				$form->setValues($this->gallery);
			}

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function editGallery ($form) {
			$values = $form->getValues();

			$this->presenter->model->getGalleries()->wherePrimary($this->presenter->id)->update($values);

			$this->flashMessage('Galerie byla upravena');
			$this->redirect('this');
		}

		public function createComponentCropForm () {
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal');

			$form->addHidden('left');
			$form->addHidden('top');
			$form->addHidden('width');
			$form->addHidden('height');
			$form->addHidden('originalWidth');
			$form->addHidden('originalHeight');

			$form->addGroup('')
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('crop', 'Oříznout');

			$form->addHidden('id', $this->presenter->id);

			$form->onSuccess[] = callback ($this, 'cropImage');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function cropImage ($form) {
			$values = $form->getValues();

			$this->image = $this->presenter->model->getGalleriesImages()->wherePrimary($values['id'])->fetch();

			$extension = pathinfo($this->image->name, PATHINFO_EXTENSION);
			$thumb = Image::fromFile(WWW_DIR . '/files/galleries/originals/'.hash('sha512', $values['id']).'.'.$extension);
			$thumb->crop($values['left'], $values['top'], $values['width'], $values['height']);
			$thumb->resize($values['originalWidth'], $values['originalHeight'], Image::STRETCH);
			$thumb->save(WWW_DIR . '/files/galleries/'.$values["originalWidth"].'x'.$values["originalHeight"].'_g'.$this->image->galleries.'-'. $this->image->name);

// 			$this->invalidateControl('thumbs');
			$this->presenter->flashMessage('Oříznutí obrázku bylo změněno');
			$this->presenter->redirect('this');
		}

		public function handleUpload () {
			$httpRequest = $this->presenter->context->getService('httpRequest');

			$basePath = $httpRequest->url->basePath;

			$files = $httpRequest->getFiles();

			foreach ($files as $file) {
				$lastPosition = $this->presenter->model->getGalleriesImages()->where('galleries_id', $this->presenter->id)->order('position DESC')->fetch();

				$values['name'] = $file->getSanitizedName();
				$values['galleries_id'] = $this->presenter->id;
				$values['position'] = !$lastPosition ? 0 : $lastPosition->position+1;

				if (!$this->presenter->model->getGalleriesImages()->where(array('galleries_id' => $this->presenter->id, 'name' => $values['name']))->fetch()) {
					$lastID = $this->presenter->model->getGalleriesImages()->insert($values);
				}

				$original = $file->move(WWW_DIR . '/files/galleries/temp_g'.$this->presenter->id.'-' . $file->getSanitizedName(), 100);

				$image = Image::fromFile($original);
				$image->resize(1920, 1920, Image::SHRINK_ONLY);

				if ($this->presenter->section->watermark && file_exists(WWW_DIR.'/images/watermark.png')) {
					$watermark = Image::fromFile(WWW_DIR.'/images/watermark.png');

					$image->place($watermark, '98%', '98%');
				}

				$image->save(WWW_DIR . '/files/galleries/g'.$this->presenter->id.'-' . $file->getSanitizedName(), 100);

				$extension = pathinfo($original, PATHINFO_EXTENSION);
				$hash = Image::fromFile($original);
				$hash->resize(1920, 1920, Image::SHRINK_ONLY);
				$hash->save(WWW_DIR . '/files/galleries/originals/'.hash('sha512', $lastID).'.'.$extension);

				unlink($original);

				foreach ($this->presenter->model->getSectionsThumbs()->where('sections_id', $this->presenter->sid) as $thumbnail) {
					preg_match('/([0-9]+).([0-9]+)/', $thumbnail->dimension, $dimensions);

					$thumb = Image::fromFile(WWW_DIR . '/files/galleries/originals/'.hash('sha512', $lastID).'.'.$extension);

					if ($thumbnail->operation == 0) {
						$thumb->resize($dimensions[1], $dimensions[2], Image::SHRINK_ONLY);
					}
					else {
						$pixels = $dimensions[1] >= $dimensions[2] ? $dimensions[1] : $dimensions[2];

						if ($thumb->width >= $thumb->height) {
							$thumb->resize(null, $pixels, Image::SHRINK_ONLY);
						}
						else {
							$thumb->resize($pixels, null, Image::SHRINK_ONLY);
						}

						$thumb->resize($dimensions[1], $dimensions[2], Image::EXACT);

						//$thumb->crop(($thumb->width / 2)-($dimensions[1] / 2), ($thumb->height / 2)-($dimensions[2] / 2), $dimensions[1], $dimensions[2]);
					}

					if ($thumbnail->watermark != null && file_exists(WWW_DIR.'/images/'.$thumbnail->watermark)) {
						$watermark = Image::fromFile(WWW_DIR.'/images/'.$thumbnail->watermark);

						$thumb->place($watermark, '98%', '98%');
					}

					$thumb->save(WWW_DIR . '/files/galleries/'.$thumbnail->dimension. '_g'.$this->presenter->id.'-' . $file->getSanitizedName(), 100);
				}

				if (!$this->presenter->model->getGalleriesImages()->where(array('galleries_id' => $this->presenter->id, 'name' => $values['name']))->fetch()) {
					$this->presenter->model->getGalleriesImages()->insert($values);
				}
			}
		}

		public function handleCreateThumbs ($gid, $thumbnail) {
			$dir = WWW_DIR . '/files/galleries/';

			foreach (Finder::findFiles('g'.$gid.'-*')->in($dir) as $file) {
				preg_match('/([0-9]+).([0-9]+)/', $thumbnail->dimension, $dimensions);

				$thumb = Image::fromFile($file->getPathName());

				if ($thumbnail->operation == 0) {
					$thumb->resize($dimensions[1], $dimensions[2], Image::SHRINK_ONLY);
				}
				else {
					$pixels = $dimensions[1] >= $dimensions[2] ? $dimensions[1] : $dimensions[2];

					if ($thumb->width >= $thumb->height) {
						$thumb->resize(null, $pixels, Image::SHRINK_ONLY);
					}
					else {
						$thumb->resize($pixels, null, Image::SHRINK_ONLY);
					}

					$thumb->resize($dimensions[1], $dimensions[2], Image::EXACT);

					//$thumb->crop(($thumb->width / 2)-($dimensions[1] / 2), ($thumb->height / 2)-($dimensions[2] / 2), $dimensions[1], $dimensions[2]);
				}

				if ($thumbnail->watermark != null && file_exists(WWW_DIR.'/images/'.$thumbnail->watermark)) {
					$watermark = Image::fromFile(WWW_DIR.'/images/'.$thumbnail->watermark);

					$thumb->place($watermark, '98%', '98%');
				}

				$thumb->save($dir.$thumbnail->dimension. '_'.$file->getBaseName(), 90);
			}
		}

		public function handleVisibility ($id, $imageID, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->presenter->model->getGalleriesImages()->where('id', $imageID)->update(array("visibility" => $vis));

			$this->presenter->flashMessage('Nastavení zobrazení obrázku změněno!');
		}

		public function handleHighlight($id, $imageID, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->presenter->model->getGalleriesImages()->where('id', $imageID)->update(array("highlight" => $vis));

			$this->presenter->flashMessage('Nastavení zvýraznění obázku změněno!');
		}

		public function handleDelete ($id, $imageID) {
			$ids = (array)$imageID;
			$dir = WWW_DIR . '/files/galleries/';

			foreach ($ids as $val) {
				$image = $this->presenter->model->getGalleriesImages()->wherePrimary($val)->fetch();

				foreach (Finder::findFiles('*g'.$id.'-'.$image->name)->in($dir) as $file) {
					unlink($file->getPathName());
				}

				if (file_exists($file = $dir.'originals/'.hash('sha512', $image->id).'.jpg')) {
					unlink($file);
				}
			}

			$image = $this->presenter->model->getGalleriesImages()->where('id', array_values($ids))->delete();
		}

		public function handlePosition () {
			$this->edit = 'position';
		}

		public function handleChangeOrder () {
			$positions = $_GET['positions'];

			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->presenter->model->getGalleriesImages()->wherePrimary($value)->update($values);
			}

			$this->presenter->flashMessage('Pořadí bylo změněno');
		}

		public function createComponentGrid () {
			return new FilesGrid($this->images);
		}
	}