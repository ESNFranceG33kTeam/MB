<?php
if(!defined('NEED_CONNECT')){
	define('NEED_CONNECT',false);
}
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');


$bd = db_connect();
$tabConfig = db_tableau($bd, "SELECT champ, valeur FROM gestion_onedrive_config","champ");
db_close($bd);


define('REDIRECT', "http://".$_SERVER['SERVER_NAME']."/fonctions/onedrive/getToken.php");
define('CLIENT_ID', $tabConfig['client_id']['valeur']);
define('CLIENT_SECRET', $tabConfig['client_secret']['valeur']);


$fileTok = fopen(($GLOBALS['SITE']->getFolderData()).'/tokenOneDrive.txt', 'r');
fgets($fileTok);fgets($fileTok);$refreshTok=fgets($fileTok);
fclose($fileTok);

if (!empty($_GET['code'])){
	echo ('<html><head><script src="//js.live.net/v5.0/wl.js" type="text/javascript"></script></head><body></body></html>');
	refreshTokens('code', $_GET['code']);
}elseif(!empty($refreshTok)){
	refreshTokens('refreshTok', $refreshTok);
}

function refreshTokens ($methode, $code){

	$token = requestAccessToken($methode,$code);

	if ($token !== false){
		$fileToken = fopen(($GLOBALS['SITE']->getFolderData()).'/tokenOneDrive.txt', 'r+');
		fseek($fileToken, 0);
		ftruncate($fileToken, 0);
		fputs($fileToken, $token->{'access_token'}."\n");
		fputs($fileToken, $token->{'authentication_token'}."\n");
		fputs($fileToken, $token->{'refresh_token'}."\n");
		fclose($fileToken);
    }
    
	return;
}


function requestAccessToken($methode,$code){

	if($methode=='code'){

		$donnees = array(
					   'client_id' => CLIENT_ID,
					   'redirect_uri' => REDIRECT,
					   'client_secret' => CLIENT_SECRET,
					   'code' => $code,
					   'grant_type' => 'authorization_code'
					  );

	}else{

		$donnees = array(
					   'client_id' => CLIENT_ID,
					   'redirect_uri' => REDIRECT,
					   'client_secret' => CLIENT_SECRET,
					   'refresh_token' => $code,
					   'grant_type' => 'refresh_token'
					  );
}

    $response = sendRequest('https://login.live.com/oauth20_token.srf','POST',$donnees);

    if ($response !== false)
    {
        $authToken = json_decode($response);
        if (!empty($authToken))
        {
            return $authToken;
        }
    }
    return false;
}

function sendRequest(
    $url,
    $method = 'GET',
    $data = array(),
    $headers = array('Content-type: application/x-www-form-urlencoded;charset=UTF-8'))
{
    $context = stream_context_create(array
                                     (
                                     'http' => array(
                                         'method' => $method,
                                         'header' => $headers,
                                         'content' => buildQueryString($data)
                                     )
                                     ));

    return file_get_contents($url, false, $context);
}

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
