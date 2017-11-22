<?php
class mdocs_box_view {
	public function displayThumbnail($thumbnail) {
		if(function_exists('imagecreatefromjpeg')) {
			@$im = imagecreatefromstring($thumbnail);
			if ($im !== false) {
				ob_start();
				imagepng($im);
				$png = ob_get_clean();
				$uri = "data:image/png;base64," . base64_encode($png);
				imagedestroy($im);
				echo $uri;
			}
			else {
				echo 'Thumbnail data not available.';
			}
		} else echo 'php-gd is unsupported on this server';
	}
	public function getThumbnail($doc_id) {
		$url = 'https://view-api.box.com/1/documents/'.$doc_id.'/thumbnail?width=256&height=256';
		$header = array(
						'Authorization: Token '.get_option('mdocs-box-view-key'),
						);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		//$boxview = new mdocs_box_view();
		//$boxview->displayThumbnail($data);
		return $data;
	}
	public function downloadFile($key) {
		$url = 'https://view-api.box.com/1/sessions';
		$header = array(
						'Authorization: Token '.get_option('mdocs-box-view-key'),
						'Content-Type: application/json',
						);
		$json = array(
					  'document_id' => $key,
					  'duration' => 1,
					  );
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		return json_decode($data,true);
	}
	public function uploadFile($file, $filename=null, $thumbnail_size='256x256', $try=0) {
		$url = 'https://view-api.box.com/1/documents';
		$header = array(
						'Authorization: Token '.get_option('mdocs-box-view-key'),
						'Content-Type: application/json',
						);
		$json = array(
					  'url' => $file,
					  'name' => $filename,
					  'thumbnails' => $thumbnail_size,
					  'non_svg' => true,
					  );
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
		curl_setopt($ch, CURLOPT_URL, $url);

		$data = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($data,true);
		
		if($data['type'] == 'error' && isset($data['message'])  && $data['message'] == 'Request was throttled. Try again in 2 seconds' && $try < 5) {
			sleep(2);
			$boxview = new mdocs_box_view();
			$data = $boxview->uploadFile($file, null, '256x256', $try++);
		}
		return $data;
	}
}
?>