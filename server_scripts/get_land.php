<?php
 header('Content-Type: text/xml; utf-8');
 
require_once("config.php");

$query;

$_GET["id"]=trim($_GET["id"]);

if(!get_magic_quotes_gpc())
{ 
$id=mysql_real_escape_string($_GET["id"]);
//$str=iconv("UTF-8", "CP1251", $_GET["name"]); 
}

//$json_data = array ('onlgeom'=>(int)$onlygeom,'id'=>$id,'lvl'=>$user['lvl'],'id_t_c1'=>$user['id_t_c1'],'id_t_c2'=>$user['id_t_c2'],'id_t_c3'=>$user['id_t_c3'],'id_t_c4'=>$user['id_t_c4'],'id_t_p'=>$user['id_t_p']);

$query= <<<EOD
SELECT ar_verts.verts FROM ar_verts,
tile WHERE tile.id='$id' and ar_verts.id=tile.id_av
EOD;


$usr=mysql_query($query);
if(!$usr)exit("Ошибка - ".mysql_error());	
$user=mysql_fetch_array($usr);

$verts_y=array();

if(isset($user['verts'])){
$verts=explode(" ",trim($user['verts']));

 for ($i = 1; $i < count($verts); $i+=3) 
  { 
    array_push($verts_y,$verts[$i]);
  } 
                          }
else{$id*=-1;}						  
$json_data = array ('id'=>$id,'verts'=> $verts_y);
echo json_encode($json_data);


/*foreach ($verts as $key => $value) {
echo "<b>$value</b><br>";
}*/	
?>


