<?php
require(__DIR__ . "/../includes/config.php");

$txt = '.txt';
if ($argc != 2 || strpos($argv[1], $txt) === false) {

    echo "Usage: provide a path to a .txt file in second argument to import. No additional arguments accepted";
    exit(1);
}

else{
    
    if(!is_readable($argv[1])){
        echo "Invalid path to file.";
        exit(1);
    }
    
    $fpUS = fopen($argv[1], "r");
    
    if($fpUS === false){
        echo "Invalid filepointer.";
        exit(1);
    }
    $row = array('country_code', 'postal_code', 'place_name', 'admin_name1', 'admin_code1', 'admin_name2', 'admin_code2', 'admin_name3', 'admin_code3', 'latitude', 'longitude', 'accuracy');
    $places = array();
    
    $rowCount = 0;
    while(($data = fgetcsv($fpUS, 0, "\t")) !== false){
        $row = [
            'country_code' => $data[0],  
            'postal_code' => $data[1], 
            'place_name' => htmlspecialchars($data[2], ENT_QUOTES), 
            'admin_name1' => htmlspecialchars($data[3], ENT_QUOTES), 
            'admin_code1' => htmlspecialchars($data[4], ENT_QUOTES), 
            'admin_name2' => htmlspecialchars($data[5], ENT_QUOTES), 
            'admin_code2' => htmlspecialchars($data[6], ENT_QUOTES), 
            'admin_name3' => htmlspecialchars($data[7], ENT_QUOTES), 
            'admin_code3' => htmlspecialchars($data[8], ENT_QUOTES), 
            'latitude' => $data[9], 
            'longitude' => $data[10], 
            'accuracy' => $data[11]
            ];
        $places[$rowCount] = $row;
        //$v[] = "('".implode("','", $row) . "')";
        $vals = implode("','", $row);
        $values = "('" . $vals . "')";
        $query = "INSERT INTO places (country_code, postal_code, place_name, admin_name1, admin_code1, admin_name2, admin_code2, admin_name3, admin_code3, latitude, longitude, accuracy) VALUES $values";
        $sql = CS50::query($query);
        
        if($sql === false){
            echo "SQL insert failed at: " . $row;
            exit(1);
        }
        
        $rowCount++;
    }

    echo "Success!";
    
}
?>
