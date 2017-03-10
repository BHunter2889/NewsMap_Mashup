<?php
foreach($places as $place){
        $values[] = "('".implode("','", $place) . "')";
        $places_array = implode(",", $values);
    }

    $query = "INSERT INTO places (country_code, postal_code, place_name, admin_name1, admin_code1, admin_name2, admin_code2, admin_name3, admin_code3, latitude, longitude, accuracy) VALUES $places_array";

    $sql = CS50::query($query);
    if($sql === false){
        echo "SQL insert failed";
    }
    else{



?>

"INSERT INTO places (country_code, postal_code, place_name, admin_name1, admin_code1, admin_name2, admin_code2, admin_name3, admin_code3, latitude, longitude, accuracy) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
