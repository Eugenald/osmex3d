<?php
 header('Content-type: text/html; utf-8');
 
require_once("config.php");

$onlygeom=NULL;
$query;

$_GET["id"]=trim($_GET["id"]);

if(!get_magic_quotes_gpc())
{ 
$id=mysql_real_escape_string($_GET["id"]);
//$str=iconv("UTF-8", "CP1251", $_GET["name"]); 
}

//$json_data = array ('onlgeom'=>(int)$onlygeom,'id'=>$id,'lvl'=>$user['lvl'],'id_t_c1'=>$user['id_t_c1'],'id_t_c2'=>$user['id_t_c2'],'id_t_c3'=>$user['id_t_c3'],'id_t_c4'=>$user['id_t_c4'],'id_t_p'=>$user['id_t_p']);

$query= <<<EOD
SELECT ar_verts.verts,tile.id_t_p,tile.OSMtex FROM ar_verts,
tile WHERE tile.id='$id' and ar_verts.id=tile.id_av
EOD;


$usr=mysql_query($query);
if(!$usr)exit("Ошибка - ".mysql_error());	
$user=mysql_fetch_array($usr);

$verts=explode(" ",trim($user['verts']));

$json_data = array ('id'=>$id,'prnt'=>$user['id_t_p'],'tex'=>$user['OSMtex'],'verts'=> $verts);
echo json_encode($json_data);


/*foreach ($verts as $key => $value) {
echo "<b>$value</b><br>";
}*/	
?>

