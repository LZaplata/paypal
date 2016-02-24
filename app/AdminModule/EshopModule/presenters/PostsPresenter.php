<?php
	namespace AdminEshopModule;
	
	use AdminModule\BasePresenter;

	use AdminModule\PostsGrid;

	class PostsPresenter extends BasePresenter {
		public $urlID;
		public $posts;
		
		public function actionDefault () {
			$this->urlID = 0;
			$this->posts = $this->model->getPosts()->where('trash', 0)->where('posts_id', 0);
			
			if (!$this['posts']->getParameter('order')) {
				$this->redirect('this', array('posts-order' => 'update DESC'));
			}
		}
		
		public function createComponentPosts () {
			return new PostsGrid($this->posts);
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			
			$this->model->getPosts()->where('id', $ids)->update(array('trash' => 1));
			$this->model->getPosts()->where('posts_id', $ids)->update(array('trash' => 1));
			
			$this->flashMessage('Příspěvky byly smazány');
		}
		
		public function handleVisibility ($id, $vis) {
			$ids = (array)$id;
			$values['visibility'] = $vis;
			
			$this->model->getPosts()->where('id', $ids)->update($values);
			$this->model->getPosts()->where('posts_id', $ids)->update($values);			
			
			$this->flashMessage('Přečtení příspěvku bylo změněno');
		}
		
		public function handleView ($id, $view) {
			$ids = (array)$id;
			$values['view'] = $view;
			
			$this->model->getPosts()->where('id', $ids)->update($values);
			$this->model->getPosts()->where('posts_id', $ids)->update($values);			
			
			$this->flashMessage('Přečtení příspěvku bylo změněno');
		}
	}