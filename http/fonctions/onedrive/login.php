<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

$bd = db_connect();
$tabConfig = db_tableau($bd, "SELECT champ, valeur FROM gestion_onedrive_config","champ");
db_close($bd);


define('REDIRECT', "http://".$_SERVER['SERVER_NAME']."/fonctions/onedrive/getToken.php");
define('CLIENT_ID',$tabConfig['client_id']['valeur']);
?>


<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Connexion</title>
<script src="//js.live.net/v5.0/wl.js"></script>
<script type="text/javascript">


    var client_id ="<?php echo CLIENT_ID ?>",
        scope = ["wl.skydrive wl.offline_access"],
        redirect_uri = "<?php echo REDIRECT ?>";

    WL.init({ client_id: client_id, redirect_uri: redirect_uri, response_type: "code", scope: scope });

    WL.ui({ name: "signin", element: "login" });

</script>
</head>
<body>
<h1>Connexion :</h1>
    <div id="login"></div>

</body>
</html>