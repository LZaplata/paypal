<?php
	namespace AdminModule;

	use Nette\Application\UI\Form;

	class StatisticsPresenter extends BasePresenter {
		public $urlID;
		public $keywords;
		public $annotations;
		
		public function actionKeywords () {
			$this->urlID = 0;
			
			$this->keywords = $this->context->params['keywords'];
			$this->annotations = $this->model->getStatistics()->where('flagGoogle != "" OR flagSeznam != ""');
		}
		
		public function renderKeywords () {
			$this->template->keywords = $this->keywords;
			$this->template->annotations = $this->annotations;
		}
		
		public function createComponentGraph () {
			$chart = new \Charts();
			
			$chart->createLineChart();
			
			$chart->addColumn('date', 'Datum', true);
			$chart->addColumn('number', 'Pozice na Google');
			$chart->addAnnotationColumn();
			$chart->addColumn('number', 'Pozice na Seznam');
			$chart->addAnnotationColumn();
			$chart->addColumn('string', 'Klíčové slovo', false, false);
			
			$i = 'A';
			foreach ($this->model->getStatistics()->order('date ASC') as $position) {
				$chart->addRow(
						$position->date, 
						$position->positionGoogle, 
						$position->flagGoogle ? $i : null,
						$position->flagGoogle ? $position->flagGoogle : null,
						$position->positionSeznam, 
						$position->flagSeznam ? $i : null,
						$position->flagSeznam ? $position->flagSeznam : null,
						$position->keyword
				);
				
				if ($position->flagGoogle || $position->flagSeznam) {
					$i++;
				}
			}
			
			$control = $chart->createControl('CategoryFilter', 'categoryFilter');
			$control->filterColumn = 'Klíčové slovo';
			$control->defaultValue = 'wellness hotel';
				
			$control2 = $chart->createControl('ChartRangeFilter', 'rangeFilter');
			$control2->filterColumn = 'Datum';
			
			$chart->setDependentControls($control, $control2);
			
			return $chart;
		}
		
		public function createComponentAddForm () {
			$form = new Form();
			
			$form->addGroup('Poznámka');
			$form->addText('flag', 'Text:')
				->setRequired('Vyplňte prosím text poznámky!');
			
			$form->addSelect('search', 'Vyhledávač:', array('Google', 'Seznam'));
			
			$form->addText('altDate', 'Datum:')
				->setValue(date("j.n.Y"))
				->getControlPrototype()->class('date');
			
			$form->addHidden('date', date('Y-m-d'));
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', 'vytvořit');
			
			$form->onSuccess[] = callback($this, 'addFlag');
			
			return $form;
		}
		
		public function addFlag ($form) {
			$values = $form->getValues();
			
			$values['search'] = $values['search'] == 0 ? 'Google' : 'Seznam';
			
			$data['flag'.$values['search']] = $values['flag'];
			
			$this->model->getStatistics()->where('date LIKE ?', $values['date'].'%')->update($data);
			
			$this->flashMessage('Poznámky byla přidána');
			$this->redirect('this');
		}
		
		public function handleDelete ($id) {
			$values['flagSeznam'] = null;
			$values['flagGoogle'] = null;
			
			$this->model->getStatistics()->find($id)->update($values);
			
			$this->flashMessage('Poznámka byla smazána!');
			$this->redirect('this');
		}
	}