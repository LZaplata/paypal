<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	use Nette\Utils\Finder;

	use Nette\Utils\Strings;

	class FilesGrid extends Grid {
		public $data;

		public function __construct($data) {
			parent::__construct();

			$this->data = $data;
		}

		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);

			$self = $this;

			$this->addColumn('position')
				->setWidth('20px')
				->setTextEditable();
			$this->addColumn('visibility')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter[$self->presenter->action]->link('Visibility!', array($self->presenter->id, $row['id'], $row['visibility'] == 0 ? 0 : 1)))->addClass($row['visibility'] == 0 ? 'fa fa-eye-slash text-danger' : 'fa fa-eye text-success')->addClass('grid-ajax')->title('Viditelnost na webu');});
			$this->addColumn('highlight')
				->setWidth('20px')
				->setRenderer(function($row) use($self) {return Html::el('a')->href($self->presenter[$self->presenter->action]->link('Highlight!', array($self->presenter->id, $row['id'], $row['highlight'] == 0 ? 0 : 1)))->addClass($row['highlight'] == 0 ? 'fa fa-star-o text-danger' : 'fa fa-star text-success')->addClass('grid-ajax')->title('Zvýraznění položky');});
			if ($this->presenter->action == 'gallery') {
				$this->addColumn('id')
					->setWidth('50px')
					->setRenderer(function($row) use($self) {
						$path = $self->presenter->context->httpRequest->url->basePath;
						return Html::el('img')->addAttributes(array('src' => preg_replace('~/$~', '', $path).'/files/galleries/'.($self->presenter['gallery']->dimension ? $self->presenter['gallery']->dimension->dimension.'_' : '').'g'.$row['galleries_id'].'-'.$row['name']));
					})
					->setSortable(false);
			}
			$this->addColumn('name', 'název')
				->setWidth('300px')
				->setTextEditable();
			$this->addColumn('title', 'titulek')
				->setWidth('300px')
				->setTextEditable();

			if ($this->presenter->action == 'files') {
				$this->addColumn('tag', 'tag')
					->setWidth('200px')
					->setTextEditable();
			}

			$this->setTemplate(APP_DIR.'/components/Grid/filesGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->setDefaultOrder('position ASC');

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			if ($this->presenter->action == 'gallery') {
				$this->addButton('edit', 'Editovat')
					->setClass('fa fa-pencil')
					->setLink(function($row) use ($self){
						return $self->presenter[$self->presenter->action]->link('EditImage!', array($row['id']));
					})
					->setAjax(false);
			}

			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter[$self->presenter->action]->link('Delete!', array($self->presenter->id, $row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku ".$row['name']."?";});

			$this->addAction("visible","Zviditelnit")
				->setCallback(function($id) use ($self){
					return $self->presenter[$self->presenter->action]->handleVisibility($self->presenter->id, $id, 0);
				});

			$this->addAction("invisible","Skrýt")
				->setCallback(function($id) use ($self){
					return $self->presenter[$self->presenter->action]->handleVisibility($self->presenter->id, $id, 1);
				});

			$this->addAction("highlight","Zvýraznit")
				->setCallback(function($id) use ($self){
					return $self->presenter[$self->presenter->action]->handleHighlight($self->presenter->id, $id, 0);
				});

			$this->addAction("unhighlight","Odzvýraznit")
				->setCallback(function($id) use ($self){
					return $self->presenter[$self->presenter->action]->handleHighlight($self->presenter->id, $id, 1);
				});

			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){
					return $self->presenter[$self->presenter->action]->handleDelete($self->presenter->id, $id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");

			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id'])->fetch();
				$values['name'] = Strings::webalize($values['name'], '.');

				if ($row->name != $values['name']) {
					if ($self->presenter->action == 'gallery') {
						$dir = WWW_DIR . '/files/galleries/';
						$prefix = 'g';
					}
					else {
						$dir = WWW_DIR . '/files/files/';
						$prefix = 'f';
					}

					foreach (Finder::findFiles('*'.$prefix.$self->presenter->id.'-'.$row->name)->in($dir) as $file) {
						$newName = Strings::replace($file->getPathName(), '/'.$row->name.'/', $values['name']);

						rename($file->getPathName(), $newName);
					}
				}

				$self->presenter->lastEdited->rows[] = $values['id'];

				unset($values['id']);
				$row->update($values);
			});
		}
	}