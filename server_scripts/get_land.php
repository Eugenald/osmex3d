<?php
 header('Content-type: text/html; utf-8');
 
require_once("config.php");

$_GET["id"]=trim($_GET["id"]);

if(!get_magic_quotes_gpc())
{ 
$id=mysql_real_escape_string($_GET["id"]);
//$str=iconv("UTF-8", "CP1251", $_GET["name"]); 
}
$query= <<<EOD
SELECT ar_verts.verts,tile.lvl,tile.id_t_c1,tile.id_t_c2,tile.id_t_c3,tile.id_t_c4,
tile.id_t_p FROM ar_verts,tile WHERE tile.id='$id' and ar_verts.id=tile.id_av
EOD;
$usr=mysql_query($query);
if(!$usr)exit("Ошибка - ".mysql_error());	
$user=mysql_fetch_array($usr);

$verts=explode(" ",trim($user['verts']));

$json_data = array ('id'=>$id,'lvl'=>$user['lvl'],'id_t_c1'=>$user['id_t_c1'],'id_t_c2'=>$user['id_t_c2'],'id_t_c3'=>$user['id_t_c3'],'id_t_c4'=>$user['id_t_c4'],'id_t_p'=>$user['id_t_p'],'verts'=> $verts);
echo json_encode($json_data);
/*foreach ($verts as $key => $value) {
echo "<b>$value</b><br>";
}*/	
?>


