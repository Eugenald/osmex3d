<?php
 header('Content-type: text/html; utf-8');
 
$link = @mysql_connect("localhost","root","") or die("Could not connect: " . mysql_error());

$sql = 'CREATE DATABASE landscape';
if (mysql_query($sql, $link)) {
    echo "DB 'landscape' has been created\n";
	$dbname = 'landscape';
    mysql_select_db($dbname);
	
	$sql = "CREATE TABLE tile
           (
           ID INT NOT NULL, 
           PRIMARY KEY(ID),
           lvl TINYINT NOT NULL,
           id_av INT NOT NULL
           )";
	mysql_query($sql) or die('Error crt table tile');
	
	$sql = "CREATE TABLE ar_verts
           (
           ID INT NOT NULL, 
           PRIMARY KEY(ID),
           verts TEXT NOT NULL
           )";
	mysql_query($sql) or die('Error crt table tile');
	
} else {
    echo 'Error ,cant crtd db: ' . mysql_error() . "\n";
}
?>


