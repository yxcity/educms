<?php

namespace module\Application\src\Model\Alipay;

use module\Application\src\Model\Tool;
class Alipay {
	private $alipay_config;
	private $payment_type;
	private $notify_url;
	private $return_url;
	private $seller_email;
	private $out_trade_no;
	private $subject;
	private $price;
	private $quantity;
	private $logistics_fee;
	private $logistics_type;
	private $logistics_payment;
	private $body;
	private $show_url;
	private $receive_name;
	private $receive_address;
	private $receive_zip;
	private $receive_phone;
	private $receive_mobile;
	function __construct($payData=null){
		if ($payData) {
			$this->alipay_config = Tool::alipayConfig ( trim ( $payData ['PID'] ), trim ( $payData ['KEY'] ) );
			$this->payment_type = 1;
			$this->notify_url = $payData ['notify_url'];
			$this->return_url = $payData ['return_url'];
			$this->seller_email = trim ( $payData ['alipayEmail'] );
			$this->out_trade_no = trim ( $payData ['serialnumber'] );
			$this->subject = trim ( $payData ['title'] );
			$this->price = trim ( $payData ['sum'] );
			$this->quantity = isset($payData ['quantity']) ? $payData ['quantity'] : '';
			$this->logistics_fee = isset($payData ['logistics_fee']) ? $payData ['logistics_fee'] : '';
			$this->logistics_type = isset($payData ['logistics_type']) ? $payData ['logistics_type'] : '';
			$this->logistics_payment = isset($payData ['logistics_payment']) ? $payData ['logistics_payment'] : '';
			$this->body = trim ( $payData ['body'] );
			$this->show_url = $payData ['show_url'];
			$this->receive_name = isset($payData ['receive_name']) ? $payData ['receive_name'] : '';
			$this->receive_address = isset($payData ['receive_address']) ? $payData ['receive_name'] : '';
			$this->receive_zip = isset($payData ['receive_zip']) ? $payData ['receive_zip'] : '';
			$this->receive_phone = isset($payData ['receive_phone']) ? $payData ['receive_phone'] : '';
			$this->receive_mobile = isset($payData ['receive_mobile']) ? $payData ['receive_mobile'] : '';
		}
		
		
	}
	function guarantee() {
		// 构造要请求的参数数组，无需改动
		$parameter = array (
				"service" => "create_partner_trade_by_buyer",
				"partner" => trim ( $this->alipay_config ['partner'] ),
				"payment_type" => $this->payment_type,
				"notify_url" => $this->notify_url,
				"return_url" => $this->return_url,
				"seller_email" => $this->seller_email,
				"out_trade_no" => $this->out_trade_no,
				"subject" => $this->subject,
				"price" => $this->price,
				"quantity" => $this->quantity,
				"logistics_fee" => $this->logistics_fee,
				"logistics_type" => $this->logistics_type,
				"logistics_payment" => $this->logistics_payment,
				"body" => $this->body,
				"show_url" => $this->show_url,
				"receive_name" => $this->receive_name,
				"receive_address" => $this->receive_address,
				"receive_zip" => $this->receive_zip,
				"receive_phone" => $this->receive_phone,
				"receive_mobile" => $this->receive_mobile,
				"_input_charset" => trim ( strtolower ( $this->alipay_config ['input_charset'] ) ) 
		);
		// 建立请求
		$alipaySubmit = new AlipaySubmit ( $this->alipay_config );
		$html_text = $alipaySubmit->buildRequestForm ( $parameter, "get", "确认" );
		echo $html_text;
	}
	function immediately() {
		// 防钓鱼时间戳
		$anti_phishing_key = "";
		// 若要使用请调用类文件submit中的query_timestamp函数
		
		// 客户端的IP地址
		$exter_invoke_ip = "";
		// 非局域网的外网IP地址，如：221.0.0.1
		
		// 构造要请求的参数数组，无需改动
		$parameter = array (
				"service" => "create_direct_pay_by_user",
				"partner" => trim ( $this->alipay_config ['partner'] ),
				"payment_type" => $this->payment_type,
				"notify_url" => $this->notify_url,
				"return_url" => $this->return_url,
				"seller_email" => $this->seller_email,
				"out_trade_no" => $this->out_trade_no,
				"subject" => $this->subject,
				"total_fee" => $this->price,
				"body" => $this->body,
				"show_url" => $this->show_url,
				"anti_phishing_key" => $anti_phishing_key,
				"exter_invoke_ip" => $exter_invoke_ip,
				"_input_charset" => trim ( strtolower ( $this->alipay_config ['input_charset'] ) ) 
		);
		// 建立请求
		$alipaySubmit = new AlipaySubmit ( $this->alipay_config );
		$html_text = $alipaySubmit->buildRequestForm ( $parameter, "get", "确认" );
		echo $html_text;
	}
	
	function standard() {
		
		
		// 构造要请求的参数数组，无需改动
		$parameter = array (
				"service" => "trade_create_by_buyer",
				"partner" => trim ( $this->alipay_config ['partner'] ),
				"payment_type" => $this->payment_type,
				"notify_url" => $this->notify_url,
				"return_url" => $this->return_url,
				"seller_email" => $this->seller_email,
				"out_trade_no" => $this->out_trade_no,
				"subject" => $this->subject,
				"price" => $this->price,
				"quantity" => $this->quantity,
				"logistics_fee" => $this->logistics_fee,
				"logistics_type" => $this->logistics_type,
				"logistics_payment" => $this->logistics_payment,
				"body" => $this->body,
				"show_url" => $this->show_url,
				"receive_name" => $this->receive_name,
				"receive_address" => $this->receive_address,
				"receive_zip" => $this->receive_zip,
				"receive_phone" => $this->receive_phone,
				"receive_mobile" => $this->receive_mobile,
				"_input_charset" => trim ( strtolower ( $this->alipay_config ['input_charset'] ) ) 
		);
		
		// 建立请求
		
		$alipaySubmit = new AlipaySubmit ( $this->alipay_config );
		$html_text = $alipaySubmit->buildRequestForm ( $parameter, "get", "确认" );
		echo $html_text;
	}
	
	function sendGoods($data)
	{
		$alipay_config = Tool::alipayConfig ( trim ( $data ['PID'] ), trim ( $data ['KEY'] ) );
		//支付宝交易号
		$trade_no = $data['trade_no'];
		//必填
		//物流公司名称
		$logistics_name = $data['logistics_name'];
		//物流发货单号
		$invoice_no = $data['invoice_no'];
		//物流运输类型
		$transport_type = $data['transport_type'];
		//三个值可选：POST（平邮）、EXPRESS（快递）、EMS（EMS）
		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "send_goods_confirm_by_platform",
				"partner" => trim($alipay_config['partner']),
				"trade_no"	=> $trade_no,
				"logistics_name"	=> $logistics_name,
				"invoice_no"	=> $invoice_no,
				"transport_type"	=> $transport_type,
				"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
		);

		$alipaySubmit = new AlipaySubmit($alipay_config);
		$alipaySubmit->buildRequestHttp($parameter);
	}
	
}