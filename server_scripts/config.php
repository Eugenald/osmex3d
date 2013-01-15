<?php
// Соединение, выбор базы данных
$link = @mysql_connect("localhost","root","1834625mala") or die("Could not connect: " . mysql_error());
$dbname = 'landscape';
mysql_select_db($dbname);
?>
