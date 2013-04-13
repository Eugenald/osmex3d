<?php

    //header('Content-Type: text/xml; utf-8');
	
    $login_db = 'root';
    $password_db = '';

    try {
        $db = new PDO('mysql:host=localhost;dbname=osmex3d', $login_db, $password_db);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

	//$zoom = 18;
	//$n = pow(2, $zoom);
	//$x = $_GET['x'];
	//$z = $_GET['z'];
    $position_lon = $_GET['minlon'];//$x / $n * 360.0 - 180.0;
    $position_lat = $_GET['minlat']; //rad2deg(atan(sinh(pi() * (1 - 2 * ($z+1) / $n))));
    $position_lonend = $_GET['maxlon']; //($x+1) / $n * 360.0 - 180.0;
    $position_latend = $_GET['maxlat']; //rad2deg(atan(sinh(pi() * (1 - 2 * $z / $n))));
	$tile_id = $_GET['tile_id']; 
	//echo "<b>$position_lon</b><br>";
	//echo "<b>$position_lat</b><br>";
	//echo "<b>$position_lonend</b><br>";
	//echo "<b>$position_latend</b><br>";
    $fullarr = array();

    $res = $db->query("SELECT * FROM instance WHERE positionLon >=  $position_lon AND positionLon <=  $position_lonend 
        AND positionLat >= $position_lat AND positionLat <= $position_latend;");

    $row = $res->fetchAll();
    foreach ($row as $rs){
        $t1 = $rs["id"];
        $t2 = $rs["scaleX"];
        $t3 = $rs["scaleY"];
        $t4 = $rs["scaleZ"];
        $t5 = $rs["rotationX"];
        $t7 = $rs["rotationY"];
        $t8 = $rs["rotationZ"];
        $t9 = $rs["positionLon"];
        $t10 = $rs["positionLat"];
        $t11 = $rs["ObjectID"];
        $var = array('build_id' => $t1, 'scaleX' => $t2, 'scaleY' => $t3, 'scaleZ' => $t4, 'rotationX' => $t5,
            'rotationY' => $t7, 'rotationZ' => $t8, 'positionLon' => $t9, 'positionLat' => $t10, 'typeObject' => $t11);
		array_push($fullarr,$var);	
    }

    $result_str = json_encode(array('tile_id' => $tile_id,'builds' => $fullarr));
    echo $result_str;
    $db = NULL;
?>
