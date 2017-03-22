<?php
date_default_timezone_set('Asia/Bangkok');
$access_token = 'NnQYm6dX5PzkPe33FElXvfItdspk+G9C2WerZAvLIC2VclvZtKsmOF5mpLx7riJjOpk9hg0w5Cd8Gs0HteaKLU+jD/La6hGcCtbt33VYlO+KVRd9e73D8wXye05dPWF/6hKKwD/uMFN/uEIekpz4fAdB04t89/1O/w1cDnyilFU=';

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

			//Save to Logger
			try{
				$url = 'https://amsel-inventory-1c20e.firebaseio.com/Logger.json';
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
