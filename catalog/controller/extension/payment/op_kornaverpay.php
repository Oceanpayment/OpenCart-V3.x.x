<?php

class ControllerExtensionPaymentOPKornaverpay extends Controller {
	
	const PUSH 			= "[PUSH]";
	const BrowserReturn = "[Browser Return]";	
	
	public function index() {
		

		$this->load->model('checkout/order');
		
		
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['action'] = 'index.php?route=extension/payment/op_kornaverpay/op_kornaverpay_form';
		
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		return $this->load->view('extension/payment/op_kornaverpay', $data);
	}

	
	public function op_kornaverpay_form() {
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_op_kornaverpay_default_order_status_id'), '', false);

		
		//判断是否为空订单
		if (!empty($order_info)) {
			
			$this->load->model('extension/payment/op_kornaverpay');
			$product_info = $this->model_extension_payment_op_kornaverpay->getOrderProducts($this->session->data['order_id']);
			
			//获取订单详情
			$productDetails = $this->getProductItems($product_info);
			//获取消费者详情
			$customer_info = $this->model_extension_payment_op_kornaverpay->getCustomerDetails($order_info['customer_id']);
			
			
			if (!$this->request->server['HTTPS']) {
				$base_url = $this->config->get('config_url');
			} else {
				$base_url = $this->config->get('config_ssl');
			}
			
			//提交网关
			$action = $this->config->get('payment_op_kornaverpay_transaction');
			$data['action'] = $action;
			
			//订单号
			$order_number = $order_info['order_id'];
			$data['order_number'] = $order_number;
			
			//订单金额
			$order_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], '', FALSE);
			$data['order_amount'] = $order_amount;
			
			//币种
			$order_currency = $order_info['currency_code'];
			$data['order_currency'] = $order_currency;
			

			$validate_arr['terminal'] = $this->config->get('payment_op_kornaverpay_terminal');
			$validate_arr['securecode'] = $this->config->get('payment_op_kornaverpay_securecode');


			//商户号
			$account = $this->config->get('payment_op_kornaverpay_account');
			$data['account'] = $account;
				
			//终端号
			$terminal = $validate_arr['terminal'];
			$data['terminal'] = $terminal;
			
			//securecode
			$securecode = $validate_arr['securecode'];
			
			
			//返回地址
			$backUrl = $base_url.'index.php?route=extension/payment/op_kornaverpay/callback';
			$data['backUrl'] = $backUrl;
			
			//服务器响应地址
			$noticeUrl = $base_url.'index.php?route=extension/payment/op_kornaverpay/notice';
			$data['noticeUrl'] = $noticeUrl;
			
			//备注
			$order_notes = '';
			$data['order_notes'] = $order_notes;
			
			//支付方式
			$methods = "NaverPay";
			$data['methods'] = $methods;

			//账单人名
			$billing_firstName = $this->OceanHtmlSpecialChars($order_info['payment_firstname']);
			$data['billing_firstName'] = $billing_firstName;

			//账单人姓
			$billing_lastName = $this->OceanHtmlSpecialChars($order_info['payment_lastname']);
			$data['billing_lastName'] = $billing_lastName;
			 
			//账单人邮箱
			$billing_email = $this->OceanHtmlSpecialChars($order_info['email']);
			$data['billing_email'] = $billing_email;
			 
			//账单人手机
			$billing_phone = $order_info['telephone'];
			$data['billing_phone'] = $billing_phone;
			 
			//账单人国家
			$billing_country = $order_info['payment_iso_code_2'];
			$data['billing_country'] = $billing_country;
			
			//账单人州
			$billing_state = $order_info['payment_zone'];
			$data['billing_state'] = $billing_state;
			 
			//账单人城市
			$billing_city = $order_info['payment_city'];
			$data['billing_city'] = $billing_city;
			 
			//账单人地址
			if (!$order_info['payment_address_2']) {
				$billing_address = $order_info['payment_address_1'] ;
			} else {
				$billing_address = $order_info['payment_address_1'] . ',' . $order_info['payment_address_2'];
			}
			$data['billing_address'] = $billing_address;
			 
			//账单人邮编
			$billing_zip = $order_info['payment_postcode'];
			$data['billing_zip'] = $billing_zip;
			 
			//加密串
			$signValue = hash("sha256",$account.$terminal.$backUrl.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$securecode);
			$data['signValue'] = $signValue;

			//收货人名
			$ship_firstName = $order_info['shipping_firstname'];
			$data['ship_firstName'] = $ship_firstName;

			//收货人姓
			$ship_lastName = $order_info['shipping_lastname'];
			$data['ship_lastName'] = $ship_lastName;
			
			//收货人手机
			$ship_phone = $order_info['telephone'];
			$data['ship_phone'] = $ship_phone;
				
			//收货人国家
			$ship_country = $order_info['shipping_iso_code_2'];
			$data['ship_country'] = $ship_country;
				
			//收货人州
			$ship_state = $order_info['shipping_zone'];
			$data['ship_state'] = $ship_state;
				
			//收货人城市
			$ship_city = $order_info['shipping_city'];
			$data['ship_city'] = $ship_city;
				
			//收货人地址
			if (!$order_info['shipping_address_2']) {
				$ship_addr = $order_info['shipping_address_1'] ;
			} else {
				$ship_addr = $order_info['shipping_address_1'] . ',' . $order_info['shipping_address_2'];
			}
			$data['ship_addr'] = $ship_addr;
				
			//收货人邮编
			$ship_zip = $order_info['shipping_postcode'];
			$data['ship_zip'] = $ship_zip;
			
			//产品名称
			$productName = $productDetails['productName'];
			$data['productName'] = $productName;
			
			//产品SKU
			$productSku	= $productDetails['productSku'];
			$data['productSku'] = $productSku;
			
			//产品数量
			$productNum = $productDetails['productNum'];
			$data['productNum'] = $productNum;
			
			//购物车信息
			$cart_info = 'opencart2.0 above';
			$data['cart_info'] = $cart_info;
			
			//API版本
			$cart_api = 'V1.7.1';
			$data['cart_api'] = $cart_api;
			
			//支付页面样式
			$pages = 0;
			$data['pages'] = $pages;
			
			
			//附加参数-用户名注册时间
			$ET_REGISTERDATE = empty($customer_info['date_added']) ? 'N/A' : $customer_info['date_added'];
			$data['ET_REGISTERDATE'] = $ET_REGISTERDATE;
			
			//附加参数-是否使用优惠券
			$ET_COUPONS = isset($this->session->data['coupon']) ? 'Yes' : 'No';
			$data['ET_COUPONS'] = $ET_COUPONS;
	
			
			//记录发送到oceanpayment的post log
			$filedate = date('Y-m-d');
			$postdate = date('Y-m-d H:i:s');
			$newfile  = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
			$post_log = $postdate."[POST to Oceanpayment]\r\n" .
					"account = "           .$account . "\r\n".
					"terminal = "          .$terminal . "\r\n".
					"backUrl = "           .$backUrl . "\r\n".
					"noticeUrl = "         .$noticeUrl . "\r\n".
					"order_number = "      .$order_number . "\r\n".
					"order_currency = "    .$order_currency . "\r\n".
					"order_amount = "      .$order_amount . "\r\n".
					"billing_firstName = " .$billing_firstName . "\r\n".
					"billing_lastName = "  .$billing_lastName . "\r\n".
					"billing_email = "     .$billing_email . "\r\n".
					"billing_phone = "     .$billing_phone . "\r\n".
					"billing_country = "   .$billing_country . "\r\n".
					"billing_state = "     .$billing_state . "\r\n".
					"billing_city = "      .$billing_city . "\r\n".
					"billing_address = "   .$billing_address . "\r\n".
					"billing_zip = "       .$billing_zip . "\r\n".
					"ship_firstName = "    .$ship_firstName . "\r\n".
					"ship_lastName = "     .$ship_lastName . "\r\n".
					"ship_phone = "        .$ship_phone . "\r\n".
					"ship_country = "  	   .$ship_country . "\r\n".
					"ship_state = "        .$ship_state . "\r\n".
					"ship_city = "         .$ship_city . "\r\n".
					"ship_addr = "  	   .$ship_addr . "\r\n".
					"ship_zip = "          .$ship_zip . "\r\n".
					"methods = "           .$methods . "\r\n".
					"signValue = "         .$signValue . "\r\n".
					"productName = "       .$productName . "\r\n".
					"productSku = "        .$productSku . "\r\n".
					"productNum = "        .$productNum . "\r\n".
					"cart_info = "         .$cart_info . "\r\n".
					"cart_api = "          .$cart_api . "\r\n".
					"order_notes = "       .$order_notes . "\r\n".
					"ET_REGISTERDATE = "   .$ET_REGISTERDATE . "\r\n".
					"ET_COUPONS = "        .$ET_COUPONS . "\r\n";
			$post_log = $post_log . "*************************************\r\n";
			$post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
			$filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
			fwrite($filename,$post_log);
			fclose($filename);
			fclose($newfile);
			
			 
			if ($this->request->get['route'] != 'checkout/guest_step_3') {
				$data['back'] = HTTPS_SERVER . 'index.php?route=checkout/payment';
			} else {
				$data['back'] = HTTPS_SERVER . 'index.php?route=checkout/guest_step_2';
			}
			
			$this->id = 'payment';
			
			
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			
			//支付模式Pay Mode
			if($this->config->get('payment_op_kornaverpay_pay_mode') == 1){
				//内嵌Iframe
				$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_iframe', $data));
			}else{
				//跳转Redirect
				$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_form', $data));
			}

		}else{		
			$this->response->redirect($this->url->link('checkout/cart'));
		}
		
		
	}
	
	
	public function callback() {
		if (isset($this->request->post['order_number']) && !(empty($this->request->post['order_number']))) {
			$this->language->load('extension/payment/op_kornaverpay');
		
			$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			if (!$this->request->server['HTTPS']) {
				$data['base'] = $this->config->get('config_url');
			} else {
				$data['base'] = $this->config->get('config_ssl');
			}
			
	
			$data['charset'] = $this->language->get('charset');
			$data['language'] = $this->language->get('code');
			$data['direction'] = $this->language->get('direction');
			$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));		
			
			$data['text_response'] = $this->language->get('text_response');
			$data['text_success'] = $this->language->get('text_success');
			$data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
            $data['text_success_url'] = $this->url->link('checkout/success');
			$data['text_failure_url'] = $this->url->link('checkout/checkout');
			$data['text_failure'] = $this->language->get('text_failure');			
			$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/checkout'));
			
			$data['text_order_number'] ='<font color="green">'.$this->request->post['order_number'].'</font>';
			$data['text_result'] ='<font color="green">'.$this->request->post['payment_status'].'</font>';						
			
	
			//返回信息
			$account = $this->config->get('payment_op_kornaverpay_account');
			$terminal = $this->request->post['terminal'];
			$response_type = $this->request->post['response_type'];
			$payment_id = $this->request->post['payment_id'];
			$order_number = $this->request->post['order_number'];
			$order_currency =$this->request->post['order_currency'];
			$order_amount =$this->request->post['order_amount'];
			$payment_status =$this->request->post['payment_status'];
			$back_signValue = $this->request->post['signValue'];
			$payment_details = $this->request->post['payment_details'];
			$methods = $this->request->post['methods'];
			$payment_country = $this->request->post['payment_country'];
			$order_notes = $this->request->post['order_notes'];
			$card_number = $this->request->post['card_number'];
			$payment_authType = $this->request->post['payment_authType'];
			$payment_risk = $this->request->post['payment_risk'];
			$payment_solutions = $this->request->post['payment_solutions'];

			
			//用于支付结果页面显示响应代码
			$getErrorCode = explode(':', $payment_details);
			$ErrorCode = $getErrorCode[0];
			$data['op_errorCode'] = $ErrorCode;
			$data['payment_details'] = $payment_details;
			$data['payment_solutions'] = $payment_solutions;
			
			
			//匹配终端号
			if($terminal == $this->config->get('payment_op_kornaverpay_terminal')){
				//普通终端号
				$securecode = $this->config->get('payment_op_kornaverpay_securecode');
			}else{
				$securecode = '';
			}
			
			

			//签名数据		
			$local_signValue = hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
					$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$securecode);
			

			//记录浏览器返回日志
			$this->returnLog(self::BrowserReturn);


	

			$message = self::BrowserReturn;
			if($this->config->get('payment_op_kornaverpay_transaction') == 'https://test-secure.oceanpayment.com/gateway/service/pay'){
				$message .= 'TEST ORDER-';
				$data['payment_details'] = 'TEST ORDER-'.$data['payment_details'];
			}
			if ($payment_status == 1){           //交易状态
				$message .= 'PAY:Success.';
			}elseif ($payment_status == 0){
				$message .= 'PAY:Failure.';
			}elseif ($payment_status == -1){
				if($payment_authType == 1){
					$message .= 'PAY:Success.';
				}else{
					$message .= 'PAY:Pending.';
				}
			}
			$message .= ' | ' . $payment_id . ' | ' . $order_currency . ':' . $order_amount . ' | ' . $payment_details . "\n";
		
			$this->load->model('checkout/order');
			if (strtoupper($local_signValue) == strtoupper($back_signValue)) {     //数据签名对比

				if($response_type == 0){		
					//正常浏览器跳转
					if($ErrorCode == 20061){	 
						//排除订单号重复(20061)的交易
						$data['continue'] = $this->url->link('checkout/cart');
						$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_failure', $data));

					}else{
						if ($payment_status == 1 ){  
							//交易成功
							//清除coupon
							unset($this->session->data['coupon']);
							
							$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('payment_op_kornaverpay_success_order_status_id'), $message, true);
							
							$data['continue'] = HTTPS_SERVER . 'index.php?route=checkout/success';
							$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_success', $data));

						}elseif ($payment_status == -1 ){   
							//交易待处理 
							//是否预授权交易
							if($payment_authType == 1){						
								$message .= '(Pre-auth)';
							}
							$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('payment_op_kornaverpay_pending_order_status_id'), $message, false);
								
							$data['continue'] = $this->url->link('checkout/cart');
							$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_success', $data));
	
						}else{     
							//交易失败
							$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('payment_op_kornaverpay_failed_order_status_id'), $message, false);
							
							$data['continue'] = $this->url->link('checkout/cart');
							$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_failure', $data));

						}
 					}								
				}					
			
			}else {     
				//数据签名对比失败
				$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('op_kornaverpay_failed_order_status_id'), $message, false);
							
				$data['continue'] = $this->url->link('checkout/cart');
				$this->response->setOutput($this->load->view('extension/payment/op_kornaverpay_failure', $data));
					
			}
		}


	}
	
	
	
	public function notice() {
		
		//获取推送输入流XML
		$xml_str = file_get_contents("php://input");
		
		//判断返回的输入流是否为xml
		if($this->xml_parser($xml_str)){
			$xml = simplexml_load_string($xml_str);
		
			error_reporting(0);
			
			//把推送参数赋值到$_REQUEST
			$_REQUEST['response_type']	  = (string)$xml->response_type;
			$_REQUEST['account']		  = (string)$xml->account;
			$_REQUEST['terminal'] 	      = (string)$xml->terminal;
			$_REQUEST['payment_id'] 	  = (string)$xml->payment_id;
			$_REQUEST['order_number']     = (string)$xml->order_number;
			$_REQUEST['order_currency']   = (string)$xml->order_currency;
			$_REQUEST['order_amount']     = (string)$xml->order_amount;
			$_REQUEST['payment_status']   = (string)$xml->payment_status;
			$_REQUEST['payment_details']  = (string)$xml->payment_details;
			$_REQUEST['signValue'] 	      = (string)$xml->signValue;
			$_REQUEST['order_notes']	  = (string)$xml->order_notes;
			$_REQUEST['card_number']	  = (string)$xml->card_number;
			$_REQUEST['payment_authType'] = (string)$xml->payment_authType;
			$_REQUEST['payment_risk'] 	  = (string)$xml->payment_risk;
			$_REQUEST['methods'] 	  	  = (string)$xml->methods;
			$_REQUEST['payment_country']  = (string)$xml->payment_country;
			$_REQUEST['payment_solutions']= (string)$xml->payment_solutions;
				
					
			//匹配终端号
			if($_REQUEST['terminal'] == $this->config->get('payment_op_kornaverpay_terminal')){
				//普通终端号
				$securecode = $this->config->get('payment_op_kornaverpay_securecode');
			}else{
				$securecode = '';
			}
			

			
		}
		
		
		if($_REQUEST['response_type'] == 1){
			
			//记录交易推送日志
			$this->returnLog(self::PUSH);
			
			//签名数据
			$local_signValue = hash("sha256",$_REQUEST['account'].$_REQUEST['terminal'].$_REQUEST['order_number'].$_REQUEST['order_currency'].$_REQUEST['order_amount'].$_REQUEST['order_notes'].$_REQUEST['card_number'].
					$_REQUEST['payment_id'].$_REQUEST['payment_authType'].$_REQUEST['payment_status'].$_REQUEST['payment_details'].$_REQUEST['payment_risk'].$securecode);
			
			//响应代码
			$getErrorCode	= explode(':', $_REQUEST['payment_details']);
			$errorCode      = $getErrorCode[0];
					
			//数据签名对比
 			if (strtoupper($local_signValue) == strtoupper($_REQUEST['signValue'])) { 
				
			
				$this->load->model('checkout/order');
				

				$message = self::PUSH;
				if($this->config->get('payment_op_kornaverpay_transaction') == 'https://test-secure.oceanpayment.com/gateway/service/pay'){
					$message .= 'TEST ORDER-';
				}
				if ($_REQUEST['payment_status'] == 1){           //交易状态
					$message .= 'PAY:Success.';
				}elseif ($_REQUEST['payment_status'] == 0){
					$message .= 'PAY:Failure.';
				}elseif ($_REQUEST['payment_status'] == -1){
					if($_REQUEST['payment_authType'] == 1){
						$message .= 'PAY:Success.';
					}else{
						$message .= 'PAY:Pending.';
					}
				}			
				$message .= ' | ' . $_REQUEST['payment_id'] . ' | ' . $_REQUEST['order_currency'] . ':' . $_REQUEST['order_amount'] . ' | ' . $_REQUEST['payment_details'] . "\n";
				
				
				if($errorCode == 20061){	 
					//排除订单号重复(20061)的交易	
				}else{
					if ($_REQUEST['payment_status'] == 1 ){
						//交易成功
						$this->model_checkout_order->addOrderHistory($_REQUEST['order_number'], $this->config->get('payment_op_kornaverpay_success_order_status_id'), $message, false);
					}elseif ($_REQUEST['payment_status'] == -1){
						//交易待处理
						//是否预授权交易
						if($_REQUEST['payment_authType'] == 1){
							$message .= '(Pre-auth)';
						}
						$this->model_checkout_order->addOrderHistory($_REQUEST['order_number'], $this->config->get('payment_op_kornaverpay_pending_order_status_id'), $message, false);
					}else{
						//交易失败
						$this->model_checkout_order->addOrderHistory($_REQUEST['order_number'], $this->config->get('payment_op_kornaverpay_failed_order_status_id'), $message, false);
					}
				}
				
			}
			
			echo "receive-ok";
			
		}
		
	
			
	}
	
	

	
	/**
	 * return log
	 */
	public function returnLog($logType){
	
		$filedate   = date('Y-m-d');
		$returndate = date('Y-m-d H:i:s');			
		$newfile    = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );			
		$return_log = $returndate . $logType . "\r\n".
				"response_type = "       . $_REQUEST['response_type'] . "\r\n".
				"account = "             . $_REQUEST['account'] . "\r\n".
				"terminal = "            . $_REQUEST['terminal'] . "\r\n".
				"payment_id = "          . $_REQUEST['payment_id'] . "\r\n".
				"order_number = "        . $_REQUEST['order_number'] . "\r\n".
				"order_currency = "      . $_REQUEST['order_currency'] . "\r\n".
				"order_amount = "        . $_REQUEST['order_amount'] . "\r\n".
				"payment_status = "      . $_REQUEST['payment_status'] . "\r\n".
				"payment_details = "     . $_REQUEST['payment_details'] . "\r\n".
				"signValue = "           . $_REQUEST['signValue'] . "\r\n".
				"order_notes = "         . $_REQUEST['order_notes'] . "\r\n".
				"card_number = "         . $_REQUEST['card_number'] . "\r\n".
				"methods = "    		 . $_REQUEST['methods'] . "\r\n".
				"payment_country = "     . $_REQUEST['payment_country'] . "\r\n".
				"payment_authType = "    . $_REQUEST['payment_authType'] . "\r\n".
				"payment_risk = "        . $_REQUEST['payment_risk'] . "\r\n".
				"payment_solutions = "   . $_REQUEST['payment_solutions'] . "\r\n";
	
		$return_log = $return_log . "*************************************\r\n";			
		$return_log = $return_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");			
		$filename   = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );			
		fwrite($filename,$return_log);	
		fclose($filename);	
		fclose($newfile);
	
	}
	
	
	
	
	
	
	
	/**
	 *  判断是否为xml
	 */
	function xml_parser($str){
		$xml_parser = xml_parser_create();
		if(!xml_parse($xml_parser,$str,true)){
			xml_parser_free($xml_parser);
			return false;
		}else {
			return true;
		}
	}
	
	

	
	/**
	 * 获取订单详情
	 */
	function getProductItems($AllItems){
	
		$productDetails = array();
		$productName = array();
		$productSku = array();
		$productNum = array();
			
		foreach ($AllItems as $item) {
			$productName[] = $item['name'];
			$productSku[] = $item['product_id'];
			$productNum[] = $item['quantity'];
		}
	
		$productDetails['productName'] = implode(';', $productName);
		$productDetails['productSku'] = implode(';', $productSku);
		$productDetails['productNum'] = implode(';', $productNum);
	
		return $productDetails;
	
	}
	
	
	
	/**
	 * 钱海支付Html特殊字符转义
	 */
	function OceanHtmlSpecialChars($parameter){
	
		//去除前后空格
		$parameter = trim($parameter);
	
		//转义"双引号,<小于号,>大于号,'单引号
		$parameter = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$parameter);
	
		return $parameter;
	
	}
	

}
?>
