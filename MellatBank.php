<?php

/**
 * کلاس درگاه پرداخت بانک ملت
 *
 * با استفاده از این کلاس میتوانید به راحتی از درگاه پرداخت بانک ملت 
 * در نرم افزار های تحت وب خود استفاده کنید، همچنین میتوایند از این
 * کلاس در سی ام اس هایی مانند وردپرس ، جوملا و .. نیز استفاده کنید.
 *
 * @category  Gateway
 * @package   Mellatbank
 * @license   http://www.opensource.org/licenses/BSD-3-Clause
 * @example   ../index.php
 * @example <br />
 *  $mellat = new MellatBank();<br />
 *  $mellat->startPayment('1000', 'http://localhost');<br />
 *  $results = $mellat->checkPayment($_POST);<br />
 *  if($results['status']=='success') echo 'OK';<br />
 * @version   1
 * @since     2014-12-10
 * @author    Ahmad Rezaei <info@freescript.ir>
 */
class MellatBank {
	
	/**
	 * ترمینال درگاه بانک ملت.
	 * @var intiger
	 */
	private $terminal = '' ;
	
	/**
	 * نام کاربری درگاه بانک ملت.
	 * @var string
	 */
	private $username = '' ;
	
	/**
	 * رمز عبور درگاه بانک ملت.
	 * @var string
	 */
	private $password = '' ;
	
	
	/**
	 * __construct
	 *
	 * @terminal : bankmellat terminal (int)
	 * @username : bankmellat username (string)
	 * @password : bankmellat password (string)
	 */
	public function __construct($terminal = '', $username = '', $password = '')
	{
		if(!empty($terminal))
			$this->terminal = $terminal;
			
		if(!empty($username))
			$this->username = $username;
			
		if(!empty($password))
			$this->password = $password;
	}

	
	/**
	 * تابع پرداخت
	 * با استفاده از این متد میتوانید درخواست پرداخت را به بانک ملت ارسال کنید.
	 *
	 * @param intiger $amount : مبلغ پرداخت
	 * @param string $callBackUrl : آدرس برگشت بعد از پرداخت
	 *
	 * @since   2014-12-10
	 * @author  Ahmad Rezaei <info@freescript.ir>
	 */
	public function startPayment($amount, $callBackUrl)
	{			
		$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
		$terminalId = $this->terminal ;
		$userName = $this->username;
		$userPassword = $this->password;
		$orderId = rand(10000,99999);
		$localDate = date('ymj');
		$localTime = date('His');
		$additionalData = '';
		$payerId = 0;
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}
		$parameters = array(
			'terminalId' => $terminalId,
			'userName' => $userName,
			'userPassword' => $userPassword,
			'orderId' => $orderId,
			'amount' => $amount,
			'localDate' => $localDate,
			'localTime' => $localTime,
			'additionalData' => $additionalData,
			'callBackUrl' => $callBackUrl,
			'payerId' => $payerId);
		$result = $client->call('bpPayRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
		if ($client->fault) {
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo '</pre>';
			die();
		} 
		else {
			$resultStr  = $result;
			$err = $client->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				die();
			} 
			else {
				$res = explode (',',$resultStr['return']);
				echo '<div style="display:none;">Pay Response is : ' . $resultStr . '</div>';
				$ResCode = $res[0];	
				if ($ResCode == "0") {
					$this->postRefId($res[1]);
				} 
				else {
					$this->error($ResCode);
				}
			}
		}
			
	}
	
	
	/**
	 * تابع تایید پرداخت
	 * با استفاده از این تابع میتوانید درخواست تایید پرداخت را 
	 * به بانک ملت ارسال کنید و پاسخ آن را دریافت کنید.
	 *
	 * @param array $params : اطلاعات دریافتی از درگاه پرداخت
	 *
	 * @return  void
	 *
	 * @since   2014-12-10
	 * @author  Ahmad Rezaei <info@freescript.ir>
	 */
	protected function verifyPayment($params) 
	{
		$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
		$orderId = $params["SaleOrderId"];
		$verifySaleOrderId = $params["SaleOrderId"];
		$verifySaleReferenceId = $params['SaleReferenceId'];
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}	  
		$parameters = array(
			'terminalId'=> $this->terminal, 
			'userName'=> $this->username, 
			'userPassword'=> $this->password, 
			'orderId' => $orderId,
			'saleOrderId' => $verifySaleOrderId,
			'saleReferenceId' => $verifySaleReferenceId);
		$result = $client->call('bpVerifyRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
		if ($client->fault) {
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo '</pre>';
			die();
		} 
		else {
			$resultStr = $result['return'];
			$err = $client->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				die();
			} 
			else {
				if( $resultStr == '0' ) {
					return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * تابع درخواست تصفیه حساب
	 * با استفاده از این تابع میتوانید درخواست تصفیه حساب
	 * را به بانک ملت ارسال و نتیجه آن را دریافت کنید.
	 *
	 * @param array $params : اطلاعات دریافتی از درگاه پرداخت
	 *
	 * @return  void
	 *
	 * @since   2014-12-10
	 * @author  Ahmad Rezaei <info@freescript.ir>
	 */
	protected function settlePayment($params) 
	{
		$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
		$orderId = $params["SaleOrderId"];
		$settleSaleOrderId = $params["SaleOrderId"];
		$settleSaleReferenceId = $params['SaleReferenceId'];
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}		  
		$parameters = array(
			'terminalId'=> $this->terminal, 
			'userName'=> $this->username, 
			'userPassword'=> $this->password, 
			'orderId' => $orderId,
			'saleOrderId' => $settleSaleOrderId,
			'saleReferenceId' => $settleSaleReferenceId);
		$result = $client->call('bpSettleRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
		if ($client->fault) {
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo '</pre>';
			die();
		} 
		else {
			$resultStr = $result['return'];
			$err = $client->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				die();
			} 
			else {
				if( $resultStr == '0' ) {
					return true;
				}
				return $resultStr ;
			}
		}
		return false;
	}
	
	
	/**
	 * تابع بررسی ترانش
	 * با استفاده از این تابع میتوانید درخواست تایید و تصفیه حساب را 
	 * ارسال کنید و از نتیجه آن آگاه شوید.
	 *
	 * @param array $params : اطلاعات دریافتی از درگاه پرداخت
	 *
	 * @return bool | array
	 *
	 * @since   2014-12-10
	 * @author  Ahmad Rezaei <info@freescript.ir>
	 */
	public function checkPayment($params) 
	{
		if( $params["ResCode"] == 0 ) 
		{
			if( $this->verifyPayment($params) == true ) {
				if( $this->settlePayment($params) == true ) {
					return array(
						"status"=>"success", 
						"trans"=>$params["SaleReferenceId"]
					);
				}
			}
		}
		return false;
	}	
	
	
	protected function postRefId($refIdValue) 
	{
		echo '<script language="javascript" type="text/javascript"> 
				function postRefId (refIdValue) {
				var form = document.createElement("form");
				form.setAttribute("method", "POST");
				form.setAttribute("action", "https://bpm.shaparak.ir/pgwchannel/startpay.mellat");         
				form.setAttribute("target", "_self");
				var hiddenField = document.createElement("input");              
				hiddenField.setAttribute("name", "RefId");
				hiddenField.setAttribute("value", refIdValue);
				form.appendChild(hiddenField);
	
				document.body.appendChild(form);         
				form.submit();
				document.body.removeChild(form);
			}
			postRefId("' . $refIdValue . '");
			</script>';
	}
	
	
	protected function error($number) 
	{
		$err = $this->response($number);
		echo '<!doctype html><html><head><meta charset="utf-8"><title>خطا</title></head><body dir="rtl">';
		echo '<style>div.error{direction:rtl;background:#A80202;float:right;text-align:right;color:#fff;';
		echo 'font-family:tahoma;font-size:13px;padding:3px 10px}</style>';
		echo '<div class="error"><strong>خطا</strong> : ' . $err . '</div>';
		die ;
	}
	
	
	
	protected function response($number) 
	{
		switch($number) {
			case 'settle':
				$err ='عملیات Settel دستی با موفقیت انجام شد .';
			break;
			case '-2':
			case -2:
				$err ='شکست در ارتباط با بانک .';
			break;
			case '-1':
			case -1:
				$err ='شکست در ارتباط با بانک .';
			break;
			//case '0':
				//$err ='تراکنش با موفقیت انجام شد .';
			//break;
			case '11':
			case 11:
				$err ='شماره کارت معتبر نیست .';
			break;
			case '12':
			case 12:
				$err ='موجودی کافی نیست .';
			break;
			case '13':
			case 13:
				$err ='رمز دوم شما صحیح نیست .';
			break;
			case '14':
			case 14:
				$err ='دفعات مجاز ورود رمز بیش از حد است .';
			break;
			case '15':
			case 15:
				$err ='کارت معتبر نیست .';
			break;
			case '16':
			case 16:
				$err ='دفعات برداشت وجه بیش از حد مجاز است .';
			break;
			case '17':
			case 17:
				$err ='شما از انجام تراکنش منصرف شده اید .';
			break;
			case '18':
			case 18:
				$err ='تاریخ انقضای کارت گذشته است .';
			break;
			case '19':
			case 19:
				$err ='مبلغ برداشت وجه بیش از حد مجاز است .';
			break;
			case '111':
			case 111:
				$err ='صادر کننده کارت نامعتبر است .';
			break;
			case '112':
			case 112:
				$err ='خطای سوییچ صادر کننده کارت رخ داده است .';
			break;
			case '113':
			case 113:
				$err ='پاسخی از صادر کننده کارت دریافت نشد .';
			break;
			case '114':
			case 114:
				$err ='دارنده کارت مجاز به انجام این تراکنش نمی باشد .';
			break;
			case '21':
			case 21:
				$err ='پذیرنده معتبر نیست .';
			break;
			case '23':
			case 23:
				$err ='خطای امنیتی رخ داده است .';
			break;
			case '24':
			case 24:
				$err ='اطلاعات کاربری پذیرنده معتبر نیست .';
			break;
			case '25':
			case 25:
				$err ='مبلغ نامعتبر است .';
			break;
			case '31':
			case 31:
				$err ='پاسخ نامعتبر است .';
			break;
			case '32':
			case 32:
				$err ='فرمت اطلاعات وارد شده صحیح نیست .';
			break;
			case '33':
			case 33:
				$err ='حساب نامعتبر است .';
			break;
			case '34':
			case 34:
				$err ='خطای سیستمی رخ داده است .';
			break;
			case '35':
			case 35:
				$err ='تاریخ نامعتبر است .';
			break;
			case '41':
			case 41:
				$err ='شماره درخواست تکراری است .';
			break;
			case '42':
			case 42:
				$err ='همچین تراکنشی وجود ندارد .';
			break;
			case '43':
			case 43:
				$err ='قبلا درخواست Verify داده شده است';
			break;
			case '44':
			case 44:
				$err ='درخواست Verify یافت نشد .';
			break;
			case '45':
			case 45:
				$err ='تراکنش قبلا Settle شده است .';
			break;
			case '46':
			case 46:
				$err ='تراکنش Settle نشده است .';
			break;
			case '47':
			case 47:
				$err ='تراکنش Settle یافت نشد .';
			break;
			case '48':
			case 48:
				$err ='تراکنش قبلا Reverse شده است .';
			break;
			case '49':
			case 49:
				$err ='تراکنش Refund یافت نشد .';
			break;
			case '412':
			case 412:
				$err ='شناسه قبض نادرست است .';
			break;
			case '413':
			case 413:
				$err ='شناسه پرداخت نادرست است .';
			break;
			case '414':
			case 414:
				$err ='سازمان صادر کننده قبض معتبر نیست .';
			break;
			case '415':
			case 415:
				$err ='زمان جلسه کاری به پایان رسیده است .';
			break;
			case '416':
			case 416:
				$err ='خطا در ثبت اطلاعات رخ داده است .';
			break;
			case '417':
			case 417:
				$err ='شناسه پرداخت کننده نامعتبر است .';
			break;
			case '418':
			case 418:
				$err ='اشکال در تعریف اطلاعات مشتری رخ داده است .';
			break;
			case '419':
			case 419:
				$err ='تعداد دفعات ورود اطلاعات بیش از حد مجاز است .';
			break;
			case '421':
			case 421:
				$err ='IP معتبر نیست .';
			break;
			case '51':
			case 51:
				$err ='تراکنش تکراری است .';
			break;
			case '54':
			case 54:
				$err ='تراکنش مرجع موجود نیست .';
			break;
			case '55':
			case 55:
				$err ='تراکنش نامعتبر است .';
			break;
			case '61':
			case 61:
				$err ='خطا در واریز رخ داده است .';
			break;	
			
		}
		return $err ;
	}


}
