<?php
date_default_timezone_set('Asia/Bangkok');
$access_token = '5xB8I03dwTqRr7bAVZxYaU4FE2C+f9yzpTen4z+B/Q28nL+5Mvio/fsOzJeVmIq0eAeRCsOuw/gxsJdcyMn5+/lPgkpd+VnPWz3YLHP4DSDZiLpaYR6GP9YU/K68+Cf/N5Hr/AfbFGGYpiJ6JOM1ewdB04t89/1O/w1cDnyilFU=';
$groupId = 'U37d948cd0f83293486fc2b7bd339adc1';

try{
		$message = "jenkins has been pushed on " . date("Y-m-d H:i:s");
		//RESPONSE
		$messages = [
			'type' => 'text',
			'text' => $message
		];

		// Make a POST Request to Messaging API to reply to sender
		$url = 'https://api.line.me/v2/bot/message/push';
		$data = [
			'to' => $groupId,
			'messages' => [$messages],
		];
		$post = json_encode($data);
		$headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($ch);
		curl_close($ch);

		echo $result . "\r\n";
}catch(Exception $e){
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}
echo date("Y-m-d H:i:s");
echo "OK";
