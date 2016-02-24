<?php		
	namespace Form;

	use Nette\Forms\Controls\BaseControl;
	
	use Nette\Application\UI\Form;
	
	class CaptchaControl extends BaseControl {
		public function __construct($label = null) {
			parent::__construct($label);
			$this->addRule(Form::FILLED, 'Date is invalid.');
		}
		
		public function getSeparatorPrototype () {
			
		}
	}