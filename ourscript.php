<?php
include('simple_html_dom.php');

echo $URL = $argv[1];

$totalSize = 0;

$totalNumResources = 0;

/*
check to see if the URL points to an HTML page,
if it doesn't then we are dealing with a single
file resource:
*/

if (!check_if_html($URL))
{
	$totalSize = get_remote_file_size($URL);
	$totalNumResources += 1;  //a single resource is still an HTTP request
	
	echo "\r\nFinal Total Download Size: $totalSize Bytes ";
	echo "\r\nFinal total HTTP requests: $totalNumResources" ;
	return;
}


/* at this point we know we are dealing with an HTML document
   which also counts as a resource, so increment the $totalNumResources
   variable by 1
*/

$totalNumResources += 1; 

$html = file_get_html($URL);

// find all images:
foreach($html->find('img') as $element){
   $size = get_remote_file_size($element->src);
   $totalSize = $totalSize + $size;     
   $totalNumResources += 1;
}

// Find all CSS:
foreach($html->find('link') as $element)
{
    if (strpos($element->href,'.css') !== false) {
      $size = get_remote_file_size($element->href);
      $totalSize = $totalSize + $size; 
      $totalNumResources += 1;
    }
}


//find all javascript:
foreach($html->find('script') as $element)
{
	//check to see if it is javascript file:
	if (strpos($element->src,'.js') !== false) {
	    $size = get_remote_file_size($element->src);
	    $totalSize = $totalSize + $size;               
	    $totalNumResources += 1;
	}
}

echo "\r\nFinal total download size: $totalSize Bytes" ;

echo "\r\nFinal total HTTP requests: $totalNumResources";

function get_remote_file_size($url) {
    $headers = get_headers($url, 1);

    if (isset($headers['Content-Length'])) return $headers['Content-Length'];
    
    //this one checks for lower case "L" IN CONTENT-length:
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

/*checks content type header to see if it is
   an HTML page...
*/
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

?>