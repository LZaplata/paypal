<?php
	namespace FrontModule;
	
	use Nette\Utils\Finder;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;

	class Currencies extends Control {	
		public $rates = array();
		public $currencies;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->getRates();
			
			$this->currencies = array('czk' => 'CZK', 'eur' => '€');
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/currencies.latte');
			
			$this->template->currencies = $this->currencies;
			
			$this->template->render();
		}
		
		/**
		 * @deprecated
		 */
// 		public function createComponentCurrencies () {			
// 			$form = new Form();
				
// 			$form->addSelect('currency', '', array('czk' => 'CZK', 'eur' => 'EUR'))
// 				->setAttribute('onchange', 'changeCurrency()')
// 				->setValue($this->presenter->parameters['currency']);
				
// 			return $form;
// 		}
		
		/**
		 * @deprecated
		 */
// 		public function handleChangeCurrency () {
// 			$values = $_GET;
// 			$data['currency'] = $values['currency'];
			
// 			$this->presenter['cart']->updateOrder($data);
// 			$this->presenter->redirect('this', array('currency' => $values['currency']));
// 		}
		
		public function getRates () {
//			$txt = false;
//			foreach (Finder::findFiles('denni_kurz.txt')->date('>', '- 1 day')->in(WWW_DIR.'/../temp/') as $key => $file) {
//				$txt = true;
//			}
//
//			if (!$txt) {
//				$handle = fopen(WWW_DIR.'/../temp/denni_kurz.txt', 'w');
//				fwrite($handle, file_get_contents('http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt'));
//				fclose($handle);
//			}
//
//			$file = file(WWW_DIR.'/../temp/denni_kurz.txt');
//
//			unset($file[0]);
//			foreach ($file as $rate) {
//				$data = explode('|', $rate);
//
//				$this->rates[strtolower($data[3])] = $data[4];
//			}

			foreach ($this->presenter->model->getLanguages() as $lang) {
				$this->rates[strtolower($lang->currency)] = $lang->rate;
			}
			
			$this->rates['czk'] = 1;
		}
	}