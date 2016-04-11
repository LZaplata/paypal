<?php
	use Nette\Mail\IMailer;

	use Nette\Templating\Helpers;
	
	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Mail\Message;

	use Nette\Application\UI\Presenter;
use Nette\Utils\Strings;
	
	class CliPresenter extends BasePresenter {
		public $model;
		public $mailer;
		public $locs = array();
		public $elements = array();
		
		public function startup() {
			parent::startup();
			
			$this->model = $this->context->container->model;
		}
		
		public function injectMailer (IMailer $mailer) {
			$this->mailer = $mailer;
		}
		
		public function actionDefault () {				
			foreach ($this->model->getEmailsQueue()->where('send', null)->where('date <= ?', date('Y-m-d G:i')) as $queue) {
				$template = $this->createTemplate();
				$template->setFile(APP_DIR.'/FrontModule/templates/Mailing/layout.latte');
				$template->registerFilter(new Engine());
				$template->registerHelperLoader('Nette\Templating\Helpers::loader');
				$template->email = $queue->emails;
				$template->contents = $queue->emails->related('emails_content')->order('position ASC');
				$template->editors = $this->model->getEditors();
				$template->model = $this->model;
				$template->client = $queue;
				$template->host = $this->context->parameters['host'];
				
				$mail = new Message();
				$mail->setFrom($this->context->parameters['contact']['email'], $this->context->parameters['contact']['name']);
				$mail->addTo($queue->users->email);
				$mail->setSubject($queue->emails->subject);
				$mail->setHtmlBody($template);		
				
				foreach ($queue->emails->filestores->related('filestores_files') as $file) {
					$mail->addAttachment(WWW_DIR.'/files/files/f'.$file->filestores_id.'-'.$file->name);
				}
				
				$this->mailer->send($mail);
				
				$queue->update(array('send' => date('Y-m-d G:i')));
				
				sleep(2);
			}
			
			$this->terminate();
		}
				
		public function actionSitemap () {
			$sitemap = new sitemap();
			$sitemap->set_ignore(array(
				'.jpg', '.png', '.jpeg', '.pdf', 'mailto', '/admin/'
			));
			$sitemap->get_links($this->context->parameters['host']);
			
			$xml = fopen(WWW_DIR.'/sitemap.xml', 'w');
			fwrite($xml, $sitemap->generate_sitemap());
			fclose($xml);
		}

		public function actionExport () {
			$orders = $this->model->getOrders()->where('state >= ?', 0);

			foreach ($orders as $order) {
				$file = WWW_DIR.'/pohoda/orders/'.$order->no.'.xml';

				if (!file_exists($file)) {
					$xml = new Engine();
					$params = array(
						"order" => $order,
						"methods" => $this->model->getShopMethods()->fetchPairs('id', 'name'),
						"products" => $order->related("orders_products"),
						"presenter" => $this,
						"relation" => $this->model->getShopMethodsRelations()->where("shop_methods_id", $order->transport_id)->where("id_shop_methods", $order->payment_id)->fetch()
					);

//					$xml = $this->createTemplate();
//					$xml->setFile(APP_DIR.'/templates/Cli/export.latte');
//					$xml->registerFilter(new Engine());
//					$xml->registerHelperLoader('Nette\Templating\Helpers::loader');
//					$xml->order = $order;
//					$xml->methods = $this->model->getShopMethods()->fetchPairs('id', 'name');
//					$xml->products = $order->related("orders_products");
//					$xml->presenter = $this;

					$handle = fopen($file, 'w');
					fwrite($handle, $xml->renderToString(APP_DIR."/templates/Cli/export.latte", $params));
					fclose($handle);

//					system('ncftpput -u xml-smiledesign -p xmlsmile  win.humlnet.cz orders  /home/creative/www.designwear.cz/www/pohoda/orders/'.$order->no.'.xml');
				}
			}

			$this->terminate();
		}
	}