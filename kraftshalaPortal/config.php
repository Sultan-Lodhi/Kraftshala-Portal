<?php
$db_host = 'localhost';
$db_user = 'sultan';
$db_pass = 'sultan246';
$db_base = 'kraftshala';

$sqlCon = new mysqli($db_host, $db_user, $db_pass, $db_base);
if (!$sqlCon) {
    echo "DB Conection Down";
}
