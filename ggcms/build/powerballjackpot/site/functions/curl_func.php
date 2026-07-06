<?php

$ua='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36';
$ext_headers = array(
//    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9',
//    'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
);

$curl_options = array(
    CURLOPT_RETURNTRANSFER  => 1, // возвращать значение как результат функции, а не выводить в stdout
    CURLOPT_BINARYTRANSFER  => 1, // передавать в binary-safe
    CURLOPT_CONNECTTIMEOUT  => 100, // таймаут соединения ( lookup + connect )
    CURLOPT_TIMEOUT         => 100, // таймаут на получение данных
    CURLOPT_USERAGENT       => $ua,
    CURLOPT_HEADER          => 1, // заголовок не получается
    CURLOPT_FOLLOWLOCATION  => 1, // следовать редиректам
    CURLOPT_AUTOREFERER     => 1, // при редиректе подставлять в "Referer:" значение из "Location:"
    CURLOPT_SSL_VERIFYPEER  => FALSE,
    CURLOPT_SSL_VERIFYHOST  => FALSE,
    CURLOPT_HTTPHEADER      => $ext_headers,
    CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1
);

function http_parse_headers($raw_headers) {
  $headers = array();
  $key = '';
  foreach(explode("\n", $raw_headers) as $i => $h) {
    $h = explode(':', $h, 2);
    if (isset($h[1])) {
      if (!isset($headers[$h[0]]))       $headers[$h[0]] = trim($h[1]);
      elseif (is_array($headers[$h[0]])) $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
      else                               $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
      $key = $h[0];
    } else {
      if (substr($h[0], 0, 1) == "\t")   $headers[$key] .= "\r\n\t".trim($h[0]);
      elseif (!$key)                     $headers[0] = trim($h[0]);trim($h[0]);
    }
  }
  return $headers;
}

function postpage($url,$post,&$headers=array(),$cookie=false,$proxylist=false) {
    global $curl_options,$ext_headers;
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt_array($ch,$curl_options ); // задаю заголовки скопом
    if(count($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER,array_merge($ext_headers,$headers));

    if(is_array($proxylist)) {
      $proxy=$proxylist[rand(0,count($proxylist)-1)];
      list($proxyip,$proxyport,$proxyuser,$proxypass)=explode(':',$proxy);
      curl_setopt($ch,CURLOPT_PROXY,"$proxyip:$proxyport");
      curl_setopt($ch,CURLOPT_PROXYUSERPWD,"$proxyuser:$proxypass");
    }

    if($cookie) {
      curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
      curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    $f=curl_exec($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    $header_size = $curl_info['header_size'];
    $headers = http_parse_headers(substr($f, 0, $header_size));
    $body = substr($f, $header_size);
    return $body;
}

function getpage($url,&$headers=array(),$cookie=false,$proxylist=false) {
    global $curl_options,$ext_headers;
    $ch  = curl_init();
//    curl_setopt($ch, CURLOPT_POST, 1);
//    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt_array($ch,$curl_options ); // задаю заголовки скопом
    if(count($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER,array_merge($ext_headers,$headers));

    if(is_array($proxylist)) {
      $proxy=$proxylist[rand(0,count($proxylist)-1)];
      list($proxyip,$proxyport,$proxyuser,$proxypass)=explode(':',$proxy);
      curl_setopt($ch,CURLOPT_PROXY,"$proxyip:$proxyport");
      curl_setopt($ch,CURLOPT_PROXYUSERPWD,"$proxyuser:$proxypass");
    }

    if($cookie) {
      curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
      curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    $f=curl_exec($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    $header_size = $curl_info['header_size'];
    $headers = http_parse_headers(substr($f, 0, $header_size));
    $body = substr($f, $header_size);
    return $body;
}
