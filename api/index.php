<?php
include "STApi.php";

date_default_timezone_set("UTC");
$server = new \Saturn\SaturnServer();
$server->handle_request();

?>