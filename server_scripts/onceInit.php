<?php
 header('Content-type: text/html; utf-8');
 
require_once("config.php");


$id=0;

$query= <<<EOD
SELECT (SELECT MAX(id) FROM tile) as maxid,ar_verts.verts,tile.id_t_p,tile.OSMtex FROM ar_verts,
tile WHERE tile.id='$id' and ar_verts.id=tile.id_av
EOD;


$usr=mysql_query($query);
if(!$usr)exit("Ошибка - ".mysql_error());	
$user=mysql_fetch_array($usr);

$verts=explode(" ",trim($user['verts']));

$start_xz[0]=$verts[0];
$start_xz[1]=$verts[2];


$maxid=$user['maxid'];

$verts_y=array();

 for ($i = 1; $i < count($verts); $i+=3) 
  { 
    array_push($verts_y,$verts[$i]);
  } 

$json_data = array ('maxid'=>$maxid,'id'=>$id,'start_xz'=> $start_xz,'verts'=> $verts_y);
echo json_encode($json_data);


/*foreach ($verts as $key => $value) {
echo "<b>$value</b><br>";
}*/	
?>


