<?php

include('simple_html_dom.php');

$URL = $argv[1];

$totalSize = 0;

$totalNumResources = 0;

list($totalSize, $totalNumResources)  = start($URL, $totalSize, $totalNumResources);

echo "\r\nFinal total download size: $totalSize Bytes  " ;

echo "\r\nFinal total HTTP requests: $totalNumResources";

function start($URL, $totalSize, $totalNumResources)
{
  if (!check_if_html($URL))
  {
    $totalSize = get_remote_file_size($URL);
    $totalNumResources += 1; //a single resource is an HTTP request
    
    echo "\r\nFinal Total Download Size: $totalSize Bytes ";
    echo "\r\nFinal total HTTP requests: $totalNumResources" ;

    return;
  }

  $html = file_get_html($URL);

  // find all images!!
  foreach($html->find('img') as $element) {
    $size = get_remote_file_size($element->src);
    $totalSize = $totalSize + $size;     
    $totalNumResources += 1;
  }

  // Find all css
  foreach($html->find('link') as $element)
  {
    if (strpos($element->href,'.css') !== false) {
      $size = get_remote_file_size($element->href);
      $totalSize = $totalSize + $size; 
      $totalNumResources += 1;
    }
  }

  foreach($html->find('script') as $element)
  {
    if (strpos($element->src,'.js') !== false) {
      $size = get_remote_file_size($element->src);
      $totalSize = $totalSize + $size;             
      $totalNumResources += 1;
    }
  }

  foreach($html->find('iframe') as $element)
  {
      list($totalSize, $totalNumResources)  = start($element->src, $totalSize, $totalNumResources);
  }
  
  return array($totalSize, $totalNumResources) ;
}


function get_remote_file_size($url) {
    $headers = get_headers($url, 1);
    
    if (isset($headers['Content-Length'])) return $headers['Content-Length'];
    
    //THIS ONE CHECKS FOR LOWER CASE L IN CONTENT-length (DIFFERENT FROM ABOVE!!)
    if (isset($headers['Content-length'])) return $headers['Content-length'];

    $c = curl_init();
    curl_setopt_array($c, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3'),
        ));
    curl_exec($c);
    
    $size = curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD);
    curl_close($c);

    return $size;
}

function check_if_html($url){
  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, TRUE);
  curl_setopt($ch, CURLOPT_NOBODY, TRUE);

  $data = curl_exec($ch);
  $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE );

  curl_close($ch);

  if (strpos($contentType,'text/html') !== false)
    return TRUE;    // this is HTML, yes!
  else
    return FALSE;
}