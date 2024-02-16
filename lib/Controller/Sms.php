<?php

class Controller_Sms extends AbstractController{
	function sendActivationCode($model,$code){

	}

	function sendMessage($no,$msg,$temp){
		if(!$this->app->getConfig("send_sms",true)) return $no.' '. $msg.'<br/>';
		$curl=$this->add('Controller_CURL');
		$msg=urlencode($msg);
		$password = urlencode($this->app->getConfig('password'));
/*SMSHUB*/
	//$url = "http://cloud.smsindiahub.in/api/mt/SendSMS?user=".$this->app->getConfig('SMSHUBUSER')."&password=".$this->app->getConfig('SMSHUBPASSWORD')."&senderid=".$this->app->getConfig('SMSHUBSENDERID')."&channel=Trans&DCS=0&flashsms=0&number=".$no."&text=".$msg."&DLTTemplateId=".$temp."&route=5&PEId=1001142556104192472";
	//$url = "http://cloud.smsindiahub.in/api/mt/SendSMS?user=".$this->app->getConfig('SMSHUBUSER')."&password=".$this->app->getConfig('SMSHUBPASSWORD')."&senderid=".$this->app->getConfig('SMSHUBSENDERID')."&channel=Trans&DCS=0&flashsms=0&number=".$no."&text=".$msg."&DLTTemplateId=".$temp."&route=5&PEId=1001142556104192472";

		//$url = "http://hellotext.stewindia.com/vb/apikey.php?apikey=".$this->app->getConfig('ApiKey')."&senderid=".$this->app->getConfig('senderId')."&templateid=".$temp."&number=".$no."&message=".$msg;

/*STEWINDIA*/
	$url =	"http://sms.stewindia.com/sms-panel/api/http/index.php?username=".$this->app->getConfig('user')."&apikey=".$this->app->getConfig('ApiKey')."&apirequest=Text&sender=".$this->app->getConfig('senderId')."&mobile=".$no."&message=".$msg."&route=TRANS&TemplateID=".$temp."&format=JSON";




//#echo $url;
	//var_dump($curl->get($url));
	return $curl->get($url);
	}
}

