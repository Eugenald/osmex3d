﻿<?php

    $login_db = 'root';
    $password_db = 'vertrigo';

    try {
        $db = new PDO('mysql:host=localhost;dbname=osmex3d', $login_db, $password_db);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $position_lon = $_GET['lon_start'];
    $position_lonend = $_GET['lon_end'];
    $position_lat = $_GET['lat_start'];
    $position_latend = $_GET['lat_end'];
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
        $fullarr[] = array("id" => $t1, "scaleX" => $t2, "scaleY" => $t3, "scaleZ" => $t4, "rotationX" => $t5,
            "rotationY" => $t7, "rotationZ" => $t8, "positionLon" => $t9, "positionLat" => $t10, "typeObject" => $t11);
    }

    $result_str = json_encode(array('buildings' => $fullarr));
    echo $result_str;
    $db = NULL;
?>
