<?php

class Controller_Sms extends AbstractController{
	function sendActivationCode($model,$code){

	}

	function sendMessage($no,$msg,$temp){
		if(!$this->app->getConfig("send_sms",true)) return $no.' '. $msg.'<br/>';
		$curl=$this->add('Controller_CURL');
		$msg=urlencode($msg);
		$password = urlencode($this->app->getConfig('password'));
		// $url="http://cloud.smsindiahub.in/vendorsms/pushsms.aspx?user=".$this->app->getConfig('user')."&password=".$password."&msisdn=$no&sid=".$this->app->getConfig('senderId')."&msg=$msg&fl=0&gwid=2";
		// $url="http://smsuser.dsadv.in/http-api.php?username=".$this->app->getConfig('user')."&password=".$password."&senderid=".$this->app->getConfig('senderId')."&route=2&number=".$no."&message=".$msg."&templateid=".$temp;
		// $url = "http://hellotext.stewindia.com/vb/apikey.php?apikey=".$this->app->getConfig('ApiKey')."&senderid=".$this->app->getConfig('senderId')."&templateid=".$temp."&number=".$no."&message=".$msg;



		$url = "http://sms.stewindia.com/sms-panel/api/http/index.php?username=".$this->app->getConfig('user')."&apikey=".$this->app->getConfig('ApiKey')."&apirequest=Unicode&sender=".$this->app->getConfig('senderId')."&mobile=".$no."&message=".$msg."&route=TRANS&TemplateID=".$temp."&format=JSON";

		//echo $url;
//		return $curl->get($url);

	}
}

