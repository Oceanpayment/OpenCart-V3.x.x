<?php

class ControllerExtensionPaymentOPCreditCardOnePage extends Controller {
	
	const PUSH 			= "[PUSH]";
	const BrowserReturn = "[Browser Return]";	
	
	public function index() {
		

		$this->load->model('checkout/order');
		
		
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['action'] = 'index.php?route=extension/payment/op_creditcardonepage/process';
		$data['transaction'] = $this->config->get('payment_op_creditcardonepage_transaction');
		$data['public_key'] = $this->config->get('payment_op_creditcardonepage_publickey');
		$data['language'] = $this->config->get('payment_op_creditcardonepage_language');
		$data['SSL'] = $this->config->get('payment_op_creditcardonepage_https').'://'.$_SERVER['HTTP_HOST'];
		
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		// return $this->load->view('extension/payment/op_creditcard', $data);
		return $this->load->view('extension/payment/op_creditcardonepage_form', $data);
	}

	public function process() {
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$this->load->model('extension/payment/op_creditcardonepage');
		$product_info = $this->model_extension_payment_op_creditcardonepage->getOrderProducts($this->session->data['order_id']);
		//获取订单详情
		$productDetails = $this->getProductItems($product_info);
		//产品名称
		$order_info['productName'] = $productDetails['productName'];
		//产品SKU
		$order_info['productSku'] = $productDetails['productSku'];
		//产品数量
		$order_info['productNum'] = $productDetails['productNum'];

		//请求网关支付结果
		$result = $this->op_payment($order_info,$_REQUEST['card_data']);
		//解析返回结果
		$xml = simplexml_load_string($result);
		$pay_url            = (string)$xml->pay_url;
		$account			= (string)$xml->account;
		$terminal			= (string)$xml->terminal;
		$payment_id 		= (string)$xml->payment_id;
		$order_number		= (string)$xml->order_number;
		$order_currency		= (string)$xml->order_currency;
		$order_amount		= (string)$xml->order_amount;
		$payment_status		= (string)$xml->payment_status;
		$payment_details	= (string)$xml->payment_details;
		$back_signValue 	= (string)$xml->signValue;
		$order_notes		= (string)$xml->order_notes;
		$card_number		= (string)$xml->card_number;
		$payment_authType	= (string)$xml->payment_authType;
		$payment_risk 		= (string)$xml->payment_risk;
	
		$local_signValue  = hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
				$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$this->config->get('payment_op_creditcardonepage_securecode'));

	   if (strtolower($local_signValue) == strtolower($back_signValue)) {
			//3D直接重定向
			if($pay_url !== ''){
					Header("Location: ".$pay_url);
			}
			$this->language->load('extension/payment/op_creditcardonepage');
			$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
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
			
			$data['text_order_number'] ='<font color="green">'.$order_number.'</font>';
			$data['text_result'] ='<font color="green">'.$payment_details.'</font>';
			$data['payment_details'] ='<font color="green">'.$payment_details.'</font>';

			$message = '';
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
			
			if($payment_status == 1){ //支付成功

				//清除coupon
				unset($this->session->data['coupon']);
				
				$this->model_checkout_order->addOrderHistory($order_number, $this->config->get('payment_op_creditcardonepage_success_order_status_id'), $message, true);
				
				$data['continue'] = HTTPS_SERVER . 'index.php?route=checkout/success';
				$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_success', $data));
			
			}elseif($payment_status == -1){  //预授权
					if($payment_authType == 1){						
						$message .= '(Pre-auth)';
					}
					$this->model_checkout_order->addOrderHistory($order_number, $this->config->get('payment_op_creditcardonepage_pending_order_status_id'), $message, false);
						
					$data['continue'] = $this->url->link('checkout/cart');
					$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_success', $data));
			
			}else{   //支付失败
				//交易失败
				$this->model_checkout_order->addOrderHistory($order_number, $this->config->get('payment_op_creditcardonepage_failed_order_status_id'), $message, false);
									
				$data['continue'] = $this->url->link('checkout/cart');
				$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_failure', $data));
			}
   		}
		
	}

	 //请求到支付网关
	 public function op_payment($order,$card_data)
	 {
		 $isMobile = $this->isMobile() ? 'Mobile' : 'PC';
			 //Oceanpayment账户
		 $account			= $this->config->get('payment_op_creditcardonepage_account');
		 //账户号下的终端号
		 $terminal			= $this->config->get('payment_op_creditcardonepage_terminal');
		 //securecode 获取本地存储的securecode，不需要用form表单提交
		 $secureCode			= $this->config->get('payment_op_creditcardonepage_securecode');
		 //订单号的交易币种，采用国际标准ISO 4217，请参考附录H.1
		 $order_currency		= $order["currency_code"];
		 //订单号的交易金额；最大支持小数点后2位数，如：1.00、5.01；如果交易金额为0，不需要发送至钱海支付系统
		 $order_amount		= number_format($order["total"], 2, '.', '');
		 //返回支付信息的网站URL地址；用于浏览器跳转
		 if (!$this->request->server['HTTPS']) {
				$base_url = $this->config->get('config_url');
			} else {
				$base_url = $this->config->get('config_ssl');
			}
		 $backUrl			= $base_url.'index.php?route=extension/payment/op_creditcardonepage/callback';
		 $noticeUrl			= $base_url.'index.php?route=extension/payment/op_creditcardonepage/notice';
		 //网站订单号
		 $order_number		= $order["order_id"];
 
		 //消费者的名，如果没有该值可默认传：消费者id或N/A
		 $billing_firstName	= empty($order["payment_firstname"]) ? 'N/A' : $this->utf8_substr($order["payment_firstname"], 0, 64);
		 //消费者的姓，如果没有该值可默认传：消费者id或N/A
		 $billing_lastName	= empty($order["payment_lastname"]) ? 'N/A' : $this->utf8_substr($order["payment_lastname"], 0, 64);
		 //消费者的邮箱，如果没有该值可默认传：消费者id@域名或简称.com
		 $billing_email		= $order["email"];
 
		 
		 /*==================*
		 *        参数      *
		 *==================*/
		 $data = array(
			 'account'=>$account,
			 'terminal'=>$terminal,
			 'signValue'=>hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$secureCode),
			 'backUrl'=>$backUrl,
			 'noticeUrl'=>$noticeUrl,
			 'methods'=>'Credit Card',
			 'card_data'=>$card_data,
			 'order_number'=>$order_number,
			 'order_currency'=>$order_currency,
			 'order_amount'=>$order_amount,
			 'order_notes'=>'',
			 'billing_firstName'=>$billing_firstName,
			 'billing_lastName'=>$billing_lastName,
			 'billing_email'=>$billing_email,
			 'billing_phone'=>empty($order["telephone"]) ? '' : str_replace( array( '(', '-', ' ', ')', '.' ), '', $order["telephone"]),
			 'billing_country'=>empty($order["payment_iso_code_2"]) ? $order["payment_iso_code_3"] : $order["payment_iso_code_2"],
			 'billing_state'=>empty($order["payment_zone_code"]) ? $order["payment_zone"] : $order["payment_zone_code"],
			 'billing_city'=>is_numeric($order['payment_city']) ? 'NULL' : $order['payment_city'],
			 'billing_address'=>$order['payment_address_1'].$order['payment_address_2'],
			 'billing_zip'=>$order['payment_postcode'],
			 'billing_ip'=>$order['ip'],
			 'ship_firstName'=>empty($order['shipping_firstname']) ? '' : $this->utf8_substr($order['shipping_firstname'], 0, 64),
			 'ship_lastName'=>empty($order['shipping_lastname']) ? '' : $this->utf8_substr($order['shipping_lastname'], 0, 64),
			 'ship_phone'=>empty($order['telephone']) ? '' : $this->utf8_substr($order['telephone'], 0, 32),
			 'ship_country'=>empty($order['shipping_iso_code_2']) ? $order['shipping_iso_code_3'] : $this->utf8_substr($order['shipping_iso_code_2'], 0, 32),
			 'ship_state'=>empty($order['shipping_zone_code']) ? '' : $this->utf8_substr($order['shipping_zone_code'], 0, 32),
			 'ship_zip'=>$order['shipping_postcode'],
			 'productSku'=>$order['productSku'],
			 'productName'=>$order['productName'],
			 'productNum'=>$order['productNum'],
			 'cart_info'=>'Opencart3|V1.0.0|'.$isMobile,
			 'cart_api'=>'V1.0',
		 );
 
		//记录发送到oceanpayment的post log
		$post_log = '';	
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
				"billing_phone = "     .$order["telephone"] . "\r\n".
				"billing_country = "   .$order["payment_iso_code_2"].$order["payment_iso_code_3"] . "\r\n".
				"billing_state = "     .$order["payment_zone_code"] . "\r\n".
				"billing_city = "      .$order['payment_city'] . "\r\n".
				"billing_address = "   .$order['payment_address_1'].$order['payment_address_2'] . "\r\n".
				"billing_zip = "       .$order['payment_postcode'] . "\r\n".
				"ship_firstName = "    .$order['shipping_firstname'] . "\r\n".
				"ship_lastName = "     .$order['shipping_lastname'] . "\r\n".
				"ship_phone = "        .$order['telephone'] . "\r\n".
				"ship_country = "  	   .$order['shipping_iso_code_2'] . "\r\n".
				"ship_state = "        .$order['shipping_zone_code'] . "\r\n".
				"ship_zip = "          .$order['shipping_postcode'] . "\r\n".
				"methods = "           .'Credit Card' . "\r\n".
				"signValue = "         .hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$secureCode) . "\r\n".
				"productName = "       .$order['productName'] . "\r\n".
				"productSku = "        .$order['productSku'] . "\r\n".
				"productNum = "        .$order['productNum'] . "\r\n".
				"cart_info = "         .'Opencart3|V1.0.0|'.$isMobile . "\r\n".
				"cart_api = "          .'V1.0' . "\r\n".
				"order_notes = "       .'' . "\r\n".
		$post_log = $post_log . "*************************************\r\n";
		$post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
		$filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
		fwrite($filename,$post_log);
		fclose($filename);
		fclose($newfile);

		 //提交地址
		 $url_pay = $this->config->get('payment_op_creditcardonepage_transaction');
		 $result_data = $this->curl_send($url_pay,$data); 
		 
		 return $result_data;
 
	 }
 
	 function curl_send($url, $data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch);

		if (curl_errno($ch)) {
			echo 'Curl Error:'.curl_error($ch);
			exit;
		}

		curl_close($ch);
		return $response;
	 }
 
	 public function utf8_substr($str,$start=0) {
		 if(empty($str)){
			 return false;
		 }
		 if (function_exists('mb_substr')){
			 if(func_num_args() >= 3) {
				 $end = func_get_arg(2);
				 return mb_substr($str,$start,$end,'utf-8');
			 }
			 else {
				 mb_internal_encoding("UTF-8");
				 return mb_substr($str,$start);
			 }
 
		 }
		 else {
			 $null = "";
			 preg_match_all("/./u", $str, $ar);
			 if(func_num_args() >= 3) {
				 $end = func_get_arg(2);
				 return join($null, array_slice($ar[0],$start,$end));
			 }
			 else {
				 return join($null, array_slice($ar[0],$start));
			 }
		 }
	 }

	 /**
     * 检验是否移动端
     */
    function isMobile(){
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])){
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 判断手机发送的客户端标志
        if (isset ($_SERVER['HTTP_USER_AGENT'])){
            $clientkeywords = array (
                'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel',
                'lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm',
                'operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
                return true;
            }
        }
        // 判断协议
        if (isset ($_SERVER['HTTP_ACCEPT'])){
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
                return true;
            }
        }
        return false;
    }
 
	
	
	public function callback() {
		if (isset($this->request->post['order_number']) && !(empty($this->request->post['order_number']))) {
			$this->language->load('extension/payment/op_creditcardonepage');
		
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
			$account = $this->config->get('payment_op_creditcardonepage_account');
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
			
			
			//匹配终端号   记录是否3D交易
			if($terminal == $this->config->get('payment_op_creditcardonepage_terminal')){
				//普通终端号
				$securecode = $this->config->get('payment_op_creditcardonepage_securecode');
				$text_is_3d = '';
			}elseif($terminal == $this->config->get('payment_op_creditcardonepage_3d_terminal')){
				//3D终端号
				$securecode = $this->config->get('payment_op_creditcardonepage_3d_securecode');
				$text_is_3d = '[3D] ';
			}else{				
				$securecode = '';	
				$text_is_3d = '';
			}
			
			

			//签名数据		
			$local_signValue = hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
					$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$securecode);
			
			if($this->config->get('payment_op_creditcardonepage_logs') == 'True') {
				//记录浏览器返回日志
				$this->returnLog(self::BrowserReturn);
			}

	

			$message = self::BrowserReturn . $text_is_3d;
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
			header("Set-Cookie:".$order_notes."path=/");
			$this->load->model('checkout/order');
			if (strtoupper($local_signValue) == strtoupper($back_signValue)) {     //数据签名对比

				if($response_type == 0){	
					//正常浏览器跳转
					if($ErrorCode == 20061){	 
						//排除订单号重复(20061)的交易
						$data['continue'] = $this->url->link('checkout/cart');
						$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_failure', $data));

					}else{
						if ($payment_status == 1 ){  
							//交易成功
							//清除coupon
							unset($this->session->data['coupon']);
							
							$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('payment_op_creditcardonepage_success_order_status_id'), $message, true);
							
							$data['continue'] = HTTPS_SERVER . 'index.php?route=checkout/success';
							$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_success', $data));

						}elseif ($payment_status == -1 ){   
							//交易待处理 
							//是否预授权交易
							if($payment_authType == 1){						
								$message .= '(Pre-auth)';
							}
							$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('payment_op_creditcardonepage_pending_order_status_id'), $message, false);
								
							$data['continue'] = $this->url->link('checkout/cart');
							$this->response->setOutput($this->load->view('extension/paymentonepage_success', $data));
	
						}else{     
							//交易失败
							$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('payment_op_creditcardonepage_failed_order_status_id'), $message, false);
							
							$data['continue'] = $this->url->link('checkout/cart');
							$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_failure', $data));

						}
 					}								
				}					
			
			}else {     
				//数据签名对比失败
				$this->model_checkout_order->addOrderHistory($this->request->post['order_number'], $this->config->get('op_creditcardonepage_failed_order_status_id'), $message, false);
							
				$data['continue'] = $this->url->link('checkout/cart');
				$this->response->setOutput($this->load->view('extension/payment/op_creditcardonepage_failure', $data));
					
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
				
					
			//匹配终端号   记录是否3D交易
			if($_REQUEST['terminal'] == $this->config->get('payment_op_creditcardonepage_terminal')){
				//普通终端号
				$securecode = $this->config->get('payment_op_creditcardonepage_securecode');
				$text_is_3d = '';
			}elseif($_REQUEST['terminal'] == $this->config->get('payment_op_creditcardonepage_3d_terminal')){
				//3D终端号
				$securecode = $this->config->get('payment_op_creditcardonepage_3d_securecode');
				$text_is_3d = '[3D] ';
			}else{
				$securecode = '';
				$text_is_3d = '';
			}
			

			
		}
		
		
		if($_REQUEST['response_type'] == 1){
			
			if($this->config->get('payment_op_creditcardonepage_logs') == 'True') {
				//记录交易推送日志
				$this->returnLog(self::PUSH);
			}
			
			//签名数据
			$local_signValue = hash("sha256",$_REQUEST['account'].$_REQUEST['terminal'].$_REQUEST['order_number'].$_REQUEST['order_currency'].$_REQUEST['order_amount'].$_REQUEST['order_notes'].$_REQUEST['card_number'].
					$_REQUEST['payment_id'].$_REQUEST['payment_authType'].$_REQUEST['payment_status'].$_REQUEST['payment_details'].$_REQUEST['payment_risk'].$securecode);
			
			//响应代码
			$getErrorCode	= explode(':', $_REQUEST['payment_details']);
			$errorCode      = $getErrorCode[0];
					
			//数据签名对比
 			if (strtoupper($local_signValue) == strtoupper($_REQUEST['signValue'])) { 
				
			
				$this->load->model('checkout/order');
				

				$message = self::PUSH . $text_is_3d;
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
						$this->model_checkout_order->addOrderHistory($_REQUEST['order_number'], $this->config->get('payment_op_creditcardonepage_success_order_status_id'), $message, false);
					}elseif ($_REQUEST['payment_status'] == -1){
						//交易待处理
						//是否预授权交易
						if($_REQUEST['payment_authType'] == 1){
							$message .= '(Pre-auth)';
						}
						$this->model_checkout_order->addOrderHistory($_REQUEST['order_number'], $this->config->get('payment_op_creditcardonepage_pending_order_status_id'), $message, false);
					}else{
						//交易失败
						$this->model_checkout_order->addOrderHistory($_REQUEST['order_number'], $this->config->get('payment_op_creditcardonepage_failed_order_status_id'), $message, false);
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
