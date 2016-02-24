<?php		
	use Nette\Application\BadRequestException;

	use Nette\Application\UI\Presenter;

	class ErrorPresenter extends Presenter {
		public function renderDefault($exception) {			
          	if ($this->isAjax()) {
           		$this->getPayload()->error = TRUE;
             	$this->terminate();

           	} 
           	elseif ($exception instanceof BadRequestException) {
           		$code = $exception->getCode();
               	$this->setView($code);
			}
			else {
            	$this->setView('500');
           	}
	    }
	}