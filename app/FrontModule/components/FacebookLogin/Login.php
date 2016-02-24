<?php
	namespace FrontModule;
	
	use Nette\Application\UI\Control;

	use Nette\Application\UI\Presenter;
	
	use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
					
	class FacebookLogin extends Control {
		public $facebook;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$facebook = $this->presenter->context->parameters['facebook'];
			$redirectUrl = $this->presenter->link('//this', array('do' => 'facebookLogin-login'));
			
			FacebookSession::setDefaultApplication($facebook['appId'], $facebook['appSecret']);			
			
			$this->facebook = new FacebookRedirectLoginHelper($redirectUrl);
		}
		
		public function getUrl () {			
			$scope = array (
				'email'
			);
			
			return $this->facebook->getLoginUrl($scope);
		}
		
		public function handleLogin () {
			$identity = array();
			
			try {
				$session = $this->facebook->getSessionFromRedirect();
			}
			catch (FacebookRequestException $ex) {
				$this->presenter->flashMessage('Propojení s Facebookem se nezdařilo', 'danger');
			}
			catch(\Exception $ex) {
				// When validation fails or other local issues
			}
			
			if ($session) {
				$request = new FacebookRequest($session, 'GET', '/me');
				$response = $request->execute();
				$me = $response->getGraphObject(GraphUser::className());
				$user = array (
						'name' => $me->getFirstName(),
						'surname' => $me->getLastName(),
						'email' => $me->getEmail()				
				);
				$identity = $this->presenter->context->authenticator->authenticateFb($user);
				
				$this->presenter->user->login($identity);
				
				$this->presenter->flashMessage('Přihlášení Facebookem proběhlo úspěšně', 'info');
			}
			
			$this->presenter->redirect('this');
		}
		
		public function render() {			
			$this->template->setFile(__DIR__.'/login.latte');
			$this->template->url = $this->getUrl();
			
			$this->template->render();
		}
	}