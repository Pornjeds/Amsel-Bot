<?php
date_default_timezone_set('Asia/Bangkok');
$access_token = '5xB8I03dwTqRr7bAVZxYaU4FE2C+f9yzpTen4z+B/Q28nL+5Mvio/fsOzJeVmIq0eAeRCsOuw/gxsJdcyMn5+/lPgkpd+VnPWz3YLHP4DSDZiLpaYR6GP9YU/K68+Cf/N5Hr/AfbFGGYpiJ6JOM1ewdB04t89/1O/w1cDnyilFU=';
$groupId = 'Ca272127b007bb6677319b550dccc2057';

try{
	// GET current Day of week
	$currentDateOfWeek = date('l');

	// SELECT the datasource based on day of the week
	$url = 'https://friendlychat-7162a.firebaseio.com/images/' . $currentDateOfWeek . '.json';
	
	// Get the image list of the data source and dump into a list
	$content = file_get_contents($url);
	$json = json_decode($content, true);
	$imageList = array();
	foreach($json as $item){
		array_push($imageList,$item['downloadURLs']);
	}

	$arraySize = sizeof($imageList);
	echo 'image list size: '. $arraySize;

	// Random pick one of the item in the list

	if($arraySize >= 0){
		$randomIndex = rand(0,$arraySize - 1);
		$imgUrl = $imageList[$randomIndex];
		
		//RESPONSE
		$messages = [
			'type' => 'image',
			'originalContentUrl' => $imgUrl,
			'previewImageUrl' => $imgUrl
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
	}else{
		//no result from json.
	}
}catch(Exception $e){
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}
echo date("Y-m-d H:i:s");
echo "OK";
