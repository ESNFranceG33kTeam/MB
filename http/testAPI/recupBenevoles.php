<?php


$url = 'http://127.0.0.1/fonctions/api/benevoles.php';
$method = 'GET';
$data = array();
$headers = array('Content-type: application/x-www-form-urlencoded;charset=UTF-8', 'Authorization: eyJhbGciOiAiSFMyNTYiLCJ0eXAiOiAiSldUIn0=.eyJub21CREQiOiAiMTI3LjAuMC4xIiwiaWQiOiAiMSIsInVzZXJuYW1lIjogIm1heGltZV9zY2hlciIsImRyb2l0cyI6ICJidXJlYXUiLCJleHBhdCI6ICIyMDE2LTExLTAyIDE2OjE1OjMzIiwiaWF0IjogIjIwMTYtMTEtMDEgMTY6MTU6MzMifQ==.5bX2UuMZkEv7NQPPhO/CktyhaAA5BJy9Qg5Pu+G9BcU=');

$context = stream_context_create(array
								 (
								 'http' => array(
									 'method' => $method,
									 'header' => $headers,
									 'content' => ($data)
								 )
								 ));

echo file_get_contents($url, false, $context);


function buildQueryString($array){
    $result = '';
    foreach ($array as $k => $v)
    {
        if ($result == '')
        {
            $prefix = '';
        }
        else
        {
            $prefix = '&';
        }
        $result .= $prefix . rawurlencode($k) . '=' . rawurlencode($v);
    }
    return $result;
}

?>