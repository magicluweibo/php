<?php
$url = $_GET['url'] ?? '';
if (strpos($url, 'http') !== 0) {
        $url = base64_decode($url);
    }
$choose = $_GET['choose'] ?? '';
$jump = $_GET['jump'] ?? '';
if (isset($_GET['url']) && !empty($_GET['url'])) {
	
	
	
	
	if (isset($_GET['jump'])){   #判断是否需要重定向
		$redirect_url = get_redirect_url($url);//http://210.210.155.37/session/d9800a34-6288-11ee-b448-b7059c73a118/qwr9ew/s/s31/index.m3u8
		$playurl = $redirect_url;
		//echo $redirect_url;
	}
	else{
		$playurl = $url;
	}
	
	
	//$prefix = str_replace('index.m3u8', '', $playurl);   #取得前缀  老方法  不适用
	$prefix = substr($playurl,0,strrpos($playurl,'/'));   #取得前缀 但不包含斜杆 /  需要补齐
	$prefix = ''.$prefix.'/';
	
	
	
	if (isset($_GET['choose'])){    #判断是否需要选择分辨率
		$RESOLUTION = get_RESOLUTION($playurl);//返回"01.m3u8"
		if(strstr($RESOLUTION,'http') == false){
			$exactly_url = $prefix."".$RESOLUTION;//确切直播源   http://210.210.155.37/session/d9800a34-6288-11ee-b448-b7059c73a118/qwr9ew/s/s31/01.m3u8
			
		}
		else{
			$exactly_url = $RESOLUTION;
			
		}
		
	    
	}
	
	else{
		$exactly_url = $playurl;
		
	}
	
	
	
	
	
	
	
	$output = zxCurl($exactly_url);
	if (strstr($output, "EXTM3U")) {
        $m3u8s = explode("\n", $output);
        $output = '';
        foreach ($m3u8s as $v) {
            $v = str_replace("\r", '', $v);
			
            if (strstr($v, ".ts") || strstr($v, ".aac")) {   #切片行,以.ts结尾是标志
				if(strstr($v,'http') == false){
					$v = $prefix."".$v;   //切片没有前缀  需要手动补齐
				}
				
				
                $output .= scriptUrl() . "?ts=" . base64_encode($v) . "\n";
            } 
			elseif ($v !== '') {     #非切片行   如#EXT-X-PROGRAM-DATE-TIME:2023-10-11T06:21:56Z
                $output .= $v . "\n";
            }
        }
        echo $output;
    }
	
	}

	elseif (isset($_GET['ts']) && !empty($_GET['ts'])) {
    $ts = base64_decode($_GET['ts']);
	
    $output = zxCurl($ts);
    echo $output;
}
	
	



function scriptUrl()
{
    $httpType = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https://'
        : 'http://';
    return $httpType . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}


function get_redirect_url($url) {
  // 将 CURL 中的头部信息和主体一并返回
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
  $response = curl_exec($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($response, 0, $header_size);
  curl_close($ch);

  // 解析头部信息中的 Location 字段，找到重定向后的地址
  preg_match_all('/Location:(.*?)\n/', $header, $matches);
  $redirect_url = array_pop($matches[1]);
  return trim($redirect_url);
}

function get_php_url(){  //获取php实时准确url
	$php_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$parsedUrl = parse_url($php_url);
	$query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
	$urlWithoutQuery = $parsedUrl['scheme'].'://'.$parsedUrl['host'].$parsedUrl['path'];
	return $urlWithoutQuery;	
}




function get_RESOLUTION($url){
	
	if ($headers === null) {
        $headers = ['User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'];
    }
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);
	 // 将响应文本按行分割成数组
    $lines = explode("\n", $response);

    // 从后向前循环查找最后一个非空行
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $lastLine = trim($lines[$i]);
        if (!empty($lastLine)) {
            return $lastLine;
        }
    }

    // 如果没有非空行，则返回空字符串
    return "";

	
}



function zxCurl($url, $headers = null)
{
    if ($headers === null) {
        $headers = ['User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'];
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $data = curl_exec($ch);
    curl_close($ch);
	if (strstr($url, ".aac")){
		//echo '这是aac';
		//header('Content-type: audio/x-aac');
		header('Content-type: video/mp2t');
		return $data;
		
	}
	else{
		
		return $data;
		
	}
	
    
}
?>