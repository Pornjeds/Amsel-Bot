<?php
date_default_timezone_set('Asia/Bangkok');
$access_token = '5xB8I03dwTqRr7bAVZxYaU4FE2C+f9yzpTen4z+B/Q28nL+5Mvio/fsOzJeVmIq0eAeRCsOuw/gxsJdcyMn5+/lPgkpd+VnPWz3YLHP4DSDZiLpaYR6GP9YU/K68+Cf/N5Hr/AfbFGGYpiJ6JOM1ewdB04t89/1O/w1cDnyilFU=';

echo date("Y-m-d H:i:s");
// Get POST body content
$content = file_get_contents('php://input');
// Parse JSON
$events = json_decode($content, true);
// Validate parsed JSON data
if (!is_null($events['events'])) {
	// Loop through each event
	foreach ($events['events'] as $event) {
		// Reply only when message sent is in 'text' format
		if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
			// Get text sent
			$text = $event['message']['text'];
			// Get replyToken
			$replyToken = $event['replyToken'];

			$id = '';
			$type = '';
			if($event['source']['type'] == 'user'){
				$id = $event['source']['userId'];
				$type = 'user';
			}else if($event['source']['type'] == 'group'){
				$id = $event['source']['groupId'];
				$type = 'user';
			}else if($event['source']['type'] == 'room'){
				$id = $event['source']['roomId'];
				$type = 'user';
			}

			//Save to Logger
			try{
				$url = 'https://linechatlogger.firebaseio.com/logger.json';
				$data = [
					'datetime' => date("Y-m-d H:i:s"),
					'event' => [$event],
				];
				$post = json_encode($data);
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$result = curl_exec($ch);
				curl_close($ch);
			}catch(Exception $e){
				echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
			// End Save to Logger

			//Request Image Response
			$text_ex = explode(' ', $text);
			if (strpos($text_ex[0], 'ขอรูป') !== false) {
					$counter = 1;

					if($text == 'ขอรูปเยอะๆ'){
						$counter = 5;
					}

					if(sizeof($text_ex) > 1 && is_numeric($text_ex[1])){
						$counter = $text_ex[1];
					}

					try{
						// Get request/response message from firebase
						$url = 'https://friendlychat-7162a.firebaseio.com/images/aGirl.json';
						$content = file_get_contents($url);
						$json = json_decode($content, true);
						$imageList = array();

						//Fill in image url into an Array
						foreach($json as $item){
							array_push($imageList,$item['downloadURLs']);
						}
						$arraySize = sizeof($imageList);
						//echo 'image size: '+ $arraySize;

						//Randomly pick 1 image from the list and reply back
						if($arraySize >= 0){
							for($i = 0; $i < $counter; $i++){
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
									'to' => $id,
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
							}

							//echo $result . "\r\n";
						}else{
							//no result from json.
						}
					}catch(Exception $ex){
							echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
			}

			//Command Function
			$text_ex = explode(' ', $text);

			//Wikipedia
			if($text_ex[0] == "อยากรู้"){ //ถ้าข้อความคือ "อยากรู้" ให้ทำการดึงข้อมูลจาก Wikipedia หาจากไทยก่อน
					$ch1 = curl_init();
					curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch1, CURLOPT_URL, 'https://th.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles='.$text_ex[1]);
					$result1 = curl_exec($ch1);
					curl_close($ch1);
					$obj = json_decode($result1, true);
				foreach($obj['query']['pages'] as $key => $val){
					$result_text = $val['extract'];
				}
				if(empty($result_text)){//ถ้าไม่พบให้หาจาก en
					$ch1 = curl_init();
					curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch1, CURLOPT_URL, 'https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles='.$text_ex[1]);
					$result1 = curl_exec($ch1);
					curl_close($ch1);
					$obj = json_decode($result1, true);

					foreach($obj['query']['pages'] as $key => $val){
					$result_text = $val['extract'];
					}
				}
				if(empty($result_text)){//หาจาก en ไม่พบก็บอกว่า ไม่พบข้อมูล ตอบกลับไป
					$result_text = 'สัส ไม่รู้โว้ย';
				}
				$response_format_text = ['contentType'=>1,"toType"=>1,"text"=>$result_text];

				$text = $result_text;
			}

			//Weather
			if($text_ex[0] == "อากาศ"){
				$ch1 = curl_init();
				curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch1, CURLOPT_URL, 'http://api.openweathermap.org/data/2.5/weather?q='.$text_ex[1].'&APPID=00583bfaf42c82b44a8f99896720ee8f');
				//curl_setopt($ch1, CURLOPT_URL, 'https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.forecast%20where%20woeid%20in%20(select%20woeid%20from%20geo.places(1)%20where%20text%3D%22'.$text_ex[1].'%22)&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys');
				$result1 = curl_exec($ch1);
				curl_close($ch1);
				$obj = json_decode($result1, true);

				$result_text = $obj['name'].' lat:'.$obj['coord']['lat'].' lon:'.$obj['coord']['lon'].' -'.$obj['weather']['main'].' -'.$obj['weather']['description'].' - temp:'.$obj['main']['temp'].' - wind speed:'.$obj['wind']['speed'].' - wind deg: '.$obj['wind']['deg'];

				if(empty($result_text)){//หาจาก en ไม่พบก็บอกว่า ไม่พบข้อมูล ตอบกลับไป
					$result_text = 'ไม่พบข้อมูล';
				}

				$text = $result_text;
			}

			//End Request Image Response
			if ($text == $event['message']['text']) {
					//ignore
			}else{
				$messages = [
					'type' => 'text',
					'text' => $text
				];

				// Make a POST Request to Messaging API to reply to sender
				$url = 'https://api.line.me/v2/bot/message/reply';
				$data = [
					'replyToken' => $replyToken,
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
			}
		}
	}
}
echo "OK";

// // Get request/response message from firebase
// $url = 'https://linechatbotdb.firebaseio.com/keywords.json';
// $content = file_get_contents($url);
// $json = json_decode($content, true);

// $wordList = array();
// foreach($json as $item){
// 	//array_push($wordList, $item);

// 	if($item['compare'] == 1) //EQUAL
// 	{
// 		//EQUAL LOGIC
// 		if($text == $item['key'])
// 		{
// 			$text = $item['response'];
// 		}
// 	}else if($item['compare'] == 2) //CONTAINS
// 	{
// 		if(strpos($text, $item['key']) !== false)
// 		{
// 			$text = $item['response'];
// 		}
// 	}else
// 	{
// 		//EQUAL LOGIC BY DEFAULT
// 		if($text == $item['key'])
// 		{
// 			$text = $item['response'];
// 		}
// 	}
// }

// if($text == '!key') {
// 	$text = "แหกตาดูเองไป๊ \r\nhttps://pornjeds.github.io/ChatBotDashboard/";
// }

//LAUGH
// if (strpos($text, '555') !== false) {
// 	$text = '555555555555555555+';
// }

/*
//DATE TIME
$text = ($text == '!day') ? date("l",time()) : $text;
$text = ($text == '!date') ? date("Y-m-d",time()) : $text;
$text = ($text == '!time') ? date("H:i:s",time()) : $text;
$text = ($text == 'วันนี้วันอะไร') ? date("l",time()) : $text;
$text = ($text == 'วันนี้วันที่เท่าไหร่') ? date("Y-m-d",time()) : $text;
$text = ($text == 'กี่โมงแล้ว') ? date("H:i:s",time()) : $text;

//HELP
//$text = ($text == '!help') ? '' : $text;



//Google
if($text_ex[0] == "google"){
	$text = 'https://www.google.co.th/webhp?hl=en&sa=X&ved=0ahUKEwi9_eLi95PRAhXJN48KHQpIA9EQPAgD#hl=en&q='. $text_ex[1];
}

//Weather
if($text_ex[0] == "อากาศ"){
	$ch1 = curl_init();
	curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch1, CURLOPT_URL, 'http://api.openweathermap.org/data/2.5/weather?q='.$text_ex[1].'&APPID=00583bfaf42c82b44a8f99896720ee8f');
	//curl_setopt($ch1, CURLOPT_URL, 'https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.forecast%20where%20woeid%20in%20(select%20woeid%20from%20geo.places(1)%20where%20text%3D%22'.$text_ex[1].'%22)&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys');
	$result1 = curl_exec($ch1);
	curl_close($ch1);
	$obj = json_decode($result1, true);

	$result_text = $obj['name'].' lat:'.$obj['coord']['lat'].' lon:'.$obj['coord']['lon'].' -'.$obj['weather']['main'].' -'.$obj['weather']['description'].' - temp:'.$obj['main']['temp'].' - wind speed:'.$obj['wind']['speed'].' - wind deg: '.$obj['wind']['deg'];

	if(empty($result_text)){//หาจาก en ไม่พบก็บอกว่า ไม่พบข้อมูล ตอบกลับไป
		$result_text = 'ไม่พบข้อมูล';
	}

	$text = $result_text;
}
*/

//Command Function
// $text_ex = explode(' ', $text);

// //Wikipedia
// if($text_ex[0] == "อยากรู้"){ //ถ้าข้อความคือ "อยากรู้" ให้ทำการดึงข้อมูลจาก Wikipedia หาจากไทยก่อน
// 		$ch1 = curl_init();
// 		curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
// 		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
// 		curl_setopt($ch1, CURLOPT_URL, 'https://th.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles='.$text_ex[1]);
// 		$result1 = curl_exec($ch1);
// 		curl_close($ch1);
// 		$obj = json_decode($result1, true);
// 	foreach($obj['query']['pages'] as $key => $val){
// 		$result_text = $val['extract'];
// 	}
// 	if(empty($result_text)){//ถ้าไม่พบให้หาจาก en
// 		$ch1 = curl_init();
// 		curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
// 		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
// 		curl_setopt($ch1, CURLOPT_URL, 'https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles='.$text_ex[1]);
// 		$result1 = curl_exec($ch1);
// 		curl_close($ch1);
// 		$obj = json_decode($result1, true);

// 		foreach($obj['query']['pages'] as $key => $val){
// 		$result_text = $val['extract'];
// 		}
// 	}
// 	if(empty($result_text)){//หาจาก en ไม่พบก็บอกว่า ไม่พบข้อมูล ตอบกลับไป
// 		$result_text = 'สัส ไม่รู้โว้ย';
// 	}
// 	$response_format_text = ['contentType'=>1,"toType"=>1,"text"=>$result_text];

// 	$text = $result_text;
// }
