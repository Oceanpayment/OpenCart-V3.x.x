<?php 
class ControllerExtensionPaymentOPGooglePay extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/op_googlepay');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->load->model('setting/setting');
			
			$this->model_setting_setting->editSetting('payment_op_googlepay', $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=payment', 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_pay'] = $this->language->get('text_pay');
		$data['text_test'] = $this->language->get('text_test');	
		$data['text_pay_iframe'] = $this->language->get('text_pay_iframe');
		$data['text_pay_redirect'] = $this->language->get('text_pay_redirect');
		$data['text_select_all'] = $this->language->get('text_select_all');
		$data['text_unselect_all'] = $this->language->get('text_unselect_all');
		$data['text_logs_true'] = $this->language->get('text_logs_true');
		$data['text_logs_false'] = $this->language->get('text_logs_false');
		$data['text_pay_sandbox'] = $this->language->get('text_pay_sandbox');
		
		$data['entry_account'] = $this->language->get('entry_account');
		$data['entry_terminal'] = $this->language->get('entry_terminal');
		$data['entry_securecode'] = $this->language->get('entry_securecode');
		$data['entry_transaction'] = $this->language->get('entry_transaction');
		$data['entry_pay_mode'] = $this->language->get('entry_pay_mode');
	
		$data['entry_default_order_status'] = $this->language->get('entry_default_order_status');	
		$data['entry_success_order_status']=$this->language->get('entry_success_order_status');
		$data['entry_failed_order_status']=$this->language->get('entry_failed_order_status');
		$data['entry_pending_order_status']=$this->language->get('entry_pending_order_status');
		
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		
		$data['entry_location'] = $this->language->get('entry_location');
		$data['entry_locations'] = $this->language->get('entry_locations');
		$data['entry_entity'] = $this->language->get('entry_entity');
		$data['entry_entitys'] = $this->language->get('entry_entitys');
		$data['entry_logs'] = $this->language->get('entry_logs');

		$data['text_show'] = $this->language->get('text_show');
		$data['text_hide'] = $this->language->get('text_hide');

		$data['text_shows'] = $this->language->get('text_shows');
		$data['text_hides'] = $this->language->get('text_hides');



 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

 		if (isset($this->error['account'])) {
			$data['error_account'] = $this->error['account'];
		} else {
			$data['error_account'] = '';
		}

		if (isset($this->error['terminal'])) {
			$data['error_terminal'] = $this->error['terminal'];
		} else {
			$data['error_terminal'] = '';
		}		
		
 		if (isset($this->error['securecode'])) {
			$data['error_securecode'] = $this->error['securecode'];
		} else {
			$data['error_securecode'] = '';
		}

  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
   			'text' => $this->language->get('text_home'),
       		'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
   		);

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('text_payment'),
   			'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'),
   		);

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('heading_title'),
   			'href' => $this->url->link('extension/payment/op_googlepay', 'user_token=' . $this->session->data['user_token'], 'SSL'),
   		);

		$data['action'] = $this->url->link('extension/payment/op_googlepay', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=payment', 'SSL');

		if (isset($this->request->post['payment_op_googlepay_account'])) {
			$data['payment_op_googlepay_account'] = $this->request->post['payment_op_googlepay_account'];
		} else {
			$data['payment_op_googlepay_account'] = $this->config->get('payment_op_googlepay_account');
		}

		if (isset($this->request->post['payment_op_googlepay_terminal'])) {
			$data['payment_op_googlepay_terminal'] = $this->request->post['payment_op_googlepay_terminal'];
		} else {
			$data['payment_op_googlepay_terminal'] = $this->config->get('payment_op_googlepay_terminal');
		}

		if (isset($this->request->post['payment_op_googlepay_securecode'])) {
			$data['payment_op_googlepay_securecode'] = $this->request->post['payment_op_googlepay_securecode'];
		} else {
			$data['payment_op_googlepay_securecode'] = $this->config->get('payment_op_googlepay_securecode');
		}
		
		
		$data['callback'] = HTTP_CATALOG . 'index.php?route=extension/payment/op_googlepay/callback';

		
		if (isset($this->request->post['payment_op_googlepay_transaction'])) {
			$data['payment_op_googlepay_transaction'] = $this->request->post['payment_op_googlepay_transaction'];
		} else {
			$data['payment_op_googlepay_transaction'] = $this->config->get('payment_op_googlepay_transaction');
		}
		
		if (isset($this->request->post['payment_op_googlepay_pay_mode'])) {
			$data['payment_op_googlepay_pay_mode'] = $this->request->post['payment_op_googlepay_pay_mode'];
		} else {
			$data['payment_op_googlepay_pay_mode'] = $this->config->get('payment_op_googlepay_pay_mode');
		}
		
		if (isset($this->request->post['payment_op_googlepay_logs'])) {
			$data['payment_op_googlepay_logs'] = $this->request->post['payment_op_googlepay_logs'];
		} else {
			$data['payment_op_googlepay_logs'] = $this->config->get('payment_op_googlepay_logs');
		}
		
		if (isset($this->request->post['payment_op_googlepay_default_order_status_id'])) {
			$data['payment_op_googlepay_default_order_status_id'] = $this->request->post['payment_op_googlepay_default_order_status_id'];
		} else {
			$data['payment_op_googlepay_default_order_status_id'] = $this->config->get('payment_op_googlepay_default_order_status_id'); 
		} 
		/* add status */
		if (isset($this->request->post['payment_op_googlepay_success_order_status_id'])) {
			$data['payment_op_googlepay_success_order_status_id'] = $this->request->post['payment_op_googlepay_success_order_status_id'];
		} else {
			$data['payment_op_googlepay_success_order_status_id'] = $this->config->get('payment_op_googlepay_success_order_status_id');
		}
		if (isset($this->request->post['payment_op_googlepay_failed_order_status_id'])) {
			$data['payment_op_googlepay_failed_order_status_id'] = $this->request->post['payment_op_googlepay_failed_order_status_id'];
		} else {
			$data['payment_op_googlepay_failed_order_status_id'] = $this->config->get('payment_op_googlepay_failed_order_status_id');
		}
		if (isset($this->request->post['payment_op_googlepay_pending_order_status_id'])) {
			$data['payment_op_googlepay_pending_order_status_id'] = $this->request->post['payment_op_googlepay_pending_order_status_id'];
		} else {
			$data['payment_op_googlepay_pending_order_status_id'] = $this->config->get('payment_op_googlepay_pending_order_status_id');
		}
		
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_op_googlepay_geo_zone_id'])) {
			$data['payment_op_googlepay_geo_zone_id'] = $this->request->post['payment_op_googlepay_geo_zone_id'];
		} else {
			$data['payment_op_googlepay_geo_zone_id'] = $this->config->get('payment_op_googlepay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['payment_op_googlepay_status'])) {
			$data['payment_op_googlepay_status'] = $this->request->post['payment_op_googlepay_status'];
		} else {
			$data['payment_op_googlepay_status'] = $this->config->get('payment_op_googlepay_status');
		}
		
		if (isset($this->request->post['payment_op_googlepay_sort_order'])) {
			$data['payment_op_googlepay_sort_order'] = $this->request->post['payment_op_googlepay_sort_order'];
		} else {
			$data['payment_op_googlepay_sort_order'] = $this->config->get('payment_op_googlepay_sort_order');
		}
		
		if (isset($this->request->post['payment_op_googlepay_location'])) {
          $data['payment_op_googlepay_location'] = $this->request->post['payment_op_googlepay_location'];
		  } else {
			  $data['payment_op_googlepay_location'] = $this->config->get('payment_op_googlepay_location');
		  }

		  if (isset($this->request->post['payment_op_googlepay_locations'])) {
			  $data['payment_op_googlepay_locations'] = $this->request->post['payment_op_googlepay_locations'];
		  } else {
			  $data['payment_op_googlepay_locations'] = $this->config->get('payment_op_googlepay_locations');
		  }

		  if (isset($this->request->post['payment_op_googlepay_entity'])) {
			  $data['payment_op_googlepay_entity'] = $this->request->post['payment_op_googlepay_entity'];
		  } else {
			  $data['payment_op_googlepay_entity'] = $this->config->get('payment_op_googlepay_entity');
		  }

		  if (isset($this->request->post['payment_op_googlepay_entitys'])) {
			  $data['payment_op_googlepay_entitys'] = $this->request->post['payment_op_googlepay_entitys'];
		  } else {
			  $data['payment_op_googlepay_entitys'] = $this->config->get('payment_op_googlepay_entitys');
		  }

		

			
			
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('extension/payment/op_googlepay', $data));
		}
		

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/op_googlepay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_op_googlepay_account']) {
			$this->error['account'] = $this->language->get('error_account');
		}

		if (!$this->request->post['payment_op_googlepay_terminal']) {
			$this->error['terminal'] = $this->language->get('error_terminal');
		}

		if (!$this->request->post['payment_op_googlepay_securecode']) {
			$this->error['securecode'] = $this->language->get('error_securecode');
		}
		
		return !$this->error;
	}
}
?>
