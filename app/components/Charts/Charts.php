<?php
	use Nette\Application\UI\Control;

	class Charts extends Control {
		public $type;
		public $columns;
		public $rows;
		public $annotationColumns = array();
		public $options;
		public $maxValue;
		public $minValue;
		public $controls = array();
		public $dependentControls;
		
		public function __construct() {
			$this->options = new stdClass();
		}
		
		public function createLineChart () {
			$this->type = 'LineChart';
		}
		
		/**
		 * Type can be one of (CategoryFilter, ChartRangeFilter) 
		 * @param string $type
		 * @param string $name
		 * @return object chartControl
		 */
		
		public function createControl ($type, $name) {
			 return $this->controls[] = new chartControl($type, $name);
		}
		
		public function addColumn ($type, $label, $isFilter = false, $isInChart = true) {
			$this->columns[] = (object) array('type' => $type, 'label' => $label, 'visibility' => $isInChart);
			if ($isFilter) {
				$this->options->filterColumnIndex = $label;
			}
		}
		
		public function addAnnotationColumn () {
			$this->columns[] = (object) array('type' => 'string', 'role' => 'annotation', 'visibility' => true);
			$this->columns[] = (object) array('type' => 'string', 'role' => 'annotationText', 'visibility' => true);
		}
		
		public function addRow () {
			$values = func_get_args();
			
			$this->rows[] = $values;
		} 
		
		public function setDependentControls ($parent, $child) {
			$this->dependentControls[] = (object) array('parent' => $parent, 'child' => $child);
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/chart.latte');
			
			$this->template->type = $this->type;
			$this->template->columns = $this->columns;
			$this->template->rows = $this->rows;
			$this->template->options = $this->options;
			$this->template->minValue = (int) $this->minValue;
			$this->template->maxValue = (int) $this->maxValue;
			$this->template->chartControls = $this->controls;
			$this->template->dependentControls = $this->dependentControls;
			
			$this->template->render();
		}
	}