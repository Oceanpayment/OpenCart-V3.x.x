<?php 
class ControllerExtensionPaymentOPAlipay extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/op_alipay');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->load->model('setting/setting');
			
			$this->model_setting_setting->editSetting('payment_op_alipay', $this->request->post);
			
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
		$data['text_select_currency'] = $this->language->get('text_select_currency');
		$data['text_select_all'] = $this->language->get('text_select_all');
		$data['text_unselect_all'] = $this->language->get('text_unselect_all');
		
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
   			'href' => $this->url->link('extension/payment/op_alipay', 'user_token=' . $this->session->data['user_token'], 'SSL'),
   		);

		$data['action'] = $this->url->link('extension/payment/op_alipay', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=payment', 'SSL');

		if (isset($this->request->post['payment_op_alipay_account'])) {
			$data['payment_op_alipay_account'] = $this->request->post['payment_op_alipay_account'];
		} else {
			$data['payment_op_alipay_account'] = $this->config->get('payment_op_alipay_account');
		}

		if (isset($this->request->post['payment_op_alipay_terminal'])) {
			$data['payment_op_alipay_terminal'] = $this->request->post['payment_op_alipay_terminal'];
		} else {
			$data['payment_op_alipay_terminal'] = $this->config->get('payment_op_alipay_terminal');
		}

		if (isset($this->request->post['payment_op_alipay_securecode'])) {
			$data['payment_op_alipay_securecode'] = $this->request->post['payment_op_alipay_securecode'];
		} else {
			$data['payment_op_alipay_securecode'] = $this->config->get('payment_op_alipay_securecode');
		}
		
	
		
		
		$data['callback'] = HTTP_CATALOG . 'index.php?route=extension/payment/op_alipay/callback';

		
		if (isset($this->request->post['payment_op_alipay_transaction'])) {
			$data['payment_op_alipay_transaction'] = $this->request->post['payment_op_alipay_transaction'];
		} else {
			$data['payment_op_alipay_transaction'] = $this->config->get('payment_op_alipay_transaction');
		}
		
		if (isset($this->request->post['payment_op_alipay_pay_mode'])) {
			$data['payment_op_alipay_pay_mode'] = $this->request->post['payment_op_alipay_pay_mode'];
		} else {
			$data['payment_op_alipay_pay_mode'] = $this->config->get('payment_op_alipay_pay_mode');
		}
		
		if (isset($this->request->post['payment_op_alipay_default_order_status_id'])) {
			$data['payment_op_alipay_default_order_status_id'] = $this->request->post['payment_op_alipay_default_order_status_id'];
		} else {
			$data['payment_op_alipay_default_order_status_id'] = $this->config->get('payment_op_alipay_default_order_status_id');
		} 
		/* add status */
		if (isset($this->request->post['payment_op_alipay_success_order_status_id'])) {
			$data['payment_op_alipay_success_order_status_id'] = $this->request->post['payment_op_alipay_success_order_status_id'];
		} else {
			$data['payment_op_alipay_success_order_status_id'] = $this->config->get('payment_op_alipay_success_order_status_id');
		}
		if (isset($this->request->post['payment_op_alipay_failed_order_status_id'])) {
			$data['payment_op_alipay_failed_order_status_id'] = $this->request->post['payment_op_alipay_failed_order_status_id'];
		} else {
			$data['payment_op_alipay_failed_order_status_id'] = $this->config->get('payment_op_alipay_failed_order_status_id');
		}
		if (isset($this->request->post['payment_op_alipay_pending_order_status_id'])) {
			$data['payment_op_alipay_pending_order_status_id'] = $this->request->post['payment_op_alipay_pending_order_status_id'];
		} else {
			$data['payment_op_alipay_pending_order_status_id'] = $this->config->get('payment_op_alipay_pending_order_status_id');
		}
		
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_op_alipay_geo_zone_id'])) {
			$data['payment_op_alipay_geo_zone_id'] = $this->request->post['payment_op_alipay_geo_zone_id'];
		} else {
			$data['payment_op_alipay_geo_zone_id'] = $this->config->get('payment_op_alipay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['payment_op_alipay_status'])) {
			$data['payment_op_alipay_status'] = $this->request->post['payment_op_alipay_status'];
		} else {
			$data['payment_op_alipay_status'] = $this->config->get('payment_op_alipay_status');
		}
		
		if (isset($this->request->post['payment_op_alipay_sort_order'])) {
			$data['payment_op_alipay_sort_order'] = $this->request->post['payment_op_alipay_sort_order'];
		} else {
			$data['payment_op_alipay_sort_order'] = $this->config->get('payment_op_alipay_sort_order');
		}
		
		if (isset($this->request->post['payment_op_alipay_location'])) {
          $data['payment_op_alipay_location'] = $this->request->post['payment_op_alipay_location'];
		  } else {
			  $data['payment_op_alipay_location'] = $this->config->get('payment_op_alipay_location');
		  }

		  if (isset($this->request->post['payment_op_alipay_locations'])) {
			  $data['payment_op_alipay_locations'] = $this->request->post['payment_op_alipay_locations'];
		  } else {
			  $data['payment_op_alipay_locations'] = $this->config->get('payment_op_alipay_locations');
		  }

		  if (isset($this->request->post['payment_op_alipay_entity'])) {
			  $data['payment_op_alipay_entity'] = $this->request->post['payment_op_alipay_entity'];
		  } else {
			  $data['payment_op_alipay_entity'] = $this->config->get('payment_op_alipay_entity');
		  }

		  if (isset($this->request->post['payment_op_alipay_entitys'])) {
			  $data['payment_op_alipay_entitys'] = $this->request->post['payment_op_alipay_entitys'];
		  } else {
			  $data['payment_op_alipay_entitys'] = $this->config->get('payment_op_alipay_entitys');
		  }

		

			
			
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('extension/payment/op_alipay', $data));
		}
		

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/op_alipay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_op_alipay_account']) {
			$this->error['account'] = $this->language->get('error_account');
		}

		if (!$this->request->post['payment_op_alipay_terminal']) {
			$this->error['terminal'] = $this->language->get('error_terminal');
		}

		if (!$this->request->post['payment_op_alipay_securecode']) {
			$this->error['securecode'] = $this->language->get('error_securecode');
		}
		
		return !$this->error;
	}
}
?>