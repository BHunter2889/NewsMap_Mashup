<?php

    require(__DIR__ . "/../includes/config.php");

    // numerically indexed array of places
    $places = [];
    /* From https://www.usps.com/send/official-abbreviations.htm */
    $states = array('AL'=>'ALABAMA','AK'=>'ALASKA','AS'=>'AMERICAN SAMOA','AZ'=>'ARIZONA','AR'=>'ARKANSAS','CA'=>'CALIFORNIA','CO'=>'COLORADO','CT'=>'CONNECTICUT','DE'=>'DELAWARE','DC'=>'DISTRICT OF COLUMBIA',
	                'FM'=>'FEDERATED STATES OF MICRONESIA','FL'=>'FLORIDA','GA'=>'GEORGIA','GU'=>'GUAM GU','HI'=>'HAWAII','ID'=>'IDAHO','IL'=>'ILLINOIS','IN'=>'INDIANA','IA'=>'IOWA','KS'=>'KANSAS','KY'=>'KENTUCKY',
	                'LA'=>'LOUISIANA','ME'=>'MAINE','MH'=>'MARSHALL ISLANDS','MD'=>'MARYLAND','MA'=>'MASSACHUSETTS','MI'=>'MICHIGAN','MN'=>'MINNESOTA','MS'=>'MISSISSIPPI','MO'=>'MISSOURI','MT'=>'MONTANA','NE'=>'NEBRASKA',
    	            'NV'=>'NEVADA','NH'=>'NEW HAMPSHIRE','NJ'=>'NEW JERSEY','NM'=>'NEW MEXICO','NY'=>'NEW YORK','NC'=>'NORTH CAROLINA','ND'=>'NORTH DAKOTA','MP'=>'NORTHERN MARIANA ISLANDS','OH'=>'OHIO','OK'=>'OKLAHOMA',
                    'OR'=>'OREGON','PW'=>'PALAU','PA'=>'PENNSYLVANIA','PR'=>'PUERTO RICO','RI'=>'RHODE ISLAND','SC'=>'SOUTH CAROLINA','SD'=>'SOUTH DAKOTA','TN'=>'TENNESSEE','TX'=>'TEXAS','UT'=>'UTAH','VT'=>'VERMONT',
                    'VI'=>'VIRGIN ISLANDS','VA'=>'VIRGINIA','WA'=>'WASHINGTON','WV'=>'WEST VIRGINIA','WI'=>'WISCONSIN','WY'=>'WYOMING','AE'=>'ARMED FORCES AFRICA \ CANADA \ EUROPE \ MIDDLE EAST','AA'=>'ARMED FORCES AMERICA (EXCEPT CANADA)','AP'=>'ARMED FORCES PACIFIC');
                    
    $query = strtoupper($_GET["geo"]);
    $keywords = preg_split("/[\s,]+/", $query);
    $postal = 0;
    $city = "";
    $state = "";
    $st_abbr = "";
    $county = "";
    $lat = 0.0;
    $lon = 0.0;
    $count = 0;
    foreach ($keywords as $keyword){
        if($keywords[$count] == NULL){
            $count++;   
        }
        /*if (strpos($keyword,'.') == (strlen($keyword) - 1)){
            if (strlen($city) < 2){
                $city = $keyword . $keywords[$count + 1];
                unset($keywords[$count], $keywords[$count + 1]);
            }
            else{
                $county = $keyword . $keywords[$count + 1];
                unset($keywords[$count], $keywords[$count + 1]);
            }
        }*/
        if(ctype_digit($keyword)){
            if (strlen($keyword) == 5){
                $postal = $keyword;
                unset($keywords[$count]);
            }
        }
        if (strpos($keyword, '-') == 5){
            $postal = $keyword;
            unset($keywords[$count]);
        }
        if (strpos($keyword, '-') === 0){
            $lon = $keyword;
            if($lat == 0.0 && strpos($keywords[$count - 1], '.') < strlen($keyword)){
                $lat = $keywords[$count - 1];
                unset($keywords[$count - 1]);
            }
            unset($keywords[$count]);
        }
        if(preg_match("/\b([A-Z]{2})\b/", $keyword)){
            foreach($states as $abbr=>$st){
                if (strcmp($abbr, $keyword) == 0){
                    $st_abbr = $keyword;
                    $state = $st;
                    unset($keywords[$count]);
                }
            }
        }
        $count++;
       
    }
    
    if($lat == 0.0 && $postal == 0){
        //set leftover, if any
        foreach($keywords as $keyword){
            if ($keyword != NULL){
                if(preg_match('~[0-9]~', $keyword) != 1){
                    if (strlen($state) < 2){
                        foreach($states as $abbr=>$st){
                            if (strcmp($abbr, $keyword) == 0){
                                $st_abbr = $keyword;
                                $state = $st;
                                unset($keywords[$count]);
                            }
                            else if(strcmp($st, $keyword) == 0){
                                $state = $keyword;
                                $st_abbr = $abbr;
                            }
                            
                        }
                        /*if (strlen($state) < 2){
                            if (strlen($city) < 2){
                                $city = $keyword;
                                unset($keywords[$count]);
                            }
                            else{
                                $county = $keyword;
                                unset($keywords[$count]);
                            }
                        }
                    }
                    else if (strlen($city) < 2){
                        $city = $keyword;
                        unset($keywords[$count]);
                    }
                    else{
                        $county = $keyword . $keywords[$count + 1];
                        unset($keywords[$count]);
                    }
                    */
                    }
                }
            }
            
        }
        //assign remaining leftovers from "geo" to a separate array
        $leftovers = array_values($keywords);
        $len = count($leftovers);
        $remaining_query = "";
    
        
        if($len != 0){
            for($i = 0; $i < count($leftovers); $i++){
                if(preg_match('~[0-9]~', $leftovers[$i]) != 1){
                    $remaining_query = $remaining_query . " " . $leftovers[$i];
                    array_splice($leftovers, $i, 1);
                    $i--;
                    
                }
                else if(ctype_digit($leftovers[$i])){
                    if (strlen($state) > 2){
                        $places = CS50::query("SELECT * FROM places WHERE MATCH(postal_code, admin_name1) AGAINST (?)", $leftovers[$i] . "%" . $state);
                    }
                    else{
                        $places = CS50::query("SELECT * FROM places WHERE postal_code LIKE ?", $leftovers[$i] . "%");
                    }
                    array_splice($leftovers, $i, 1);
                    $i--;
                }
                
                // $places[] = CS50::query("SELECT * FROM places WHERE MATCH(postal_code, place_name) AGAINST (?)", $leftovers[$i] . "%");
            }
        }
        /*if(strlen($city) > 1){
            if (strlen($state) < 2){
                if (strlen($county) < 2){
                    $places[] = CS50::query("SELECT * FROM places WHERE place_name LIKE ?", $city . "%");
                }
                else{
                    $places[] = CS50::query("SELECT * FROM places WHERE MATCH(place_name, admin_name2) AGAINST (?)", $city . "%" . $county . "%");
                }
            }
        }*/
        if(strlen($state) > 1 || strlen($st_abbr) > 1){
            $places = CS50::query("SELECT * FROM places WHERE MATCH(place_name, admin_name1) AGAINST (?)", $remaining_query . "%" . $state);
            $places = CS50::query("SELECT * FROM places WHERE MATCH(admin_name2, admin_name1) AGAINST (?)", $remaining_query . "%" . $state);
        }
        else{ ///\b([A-Z]{2})\b/
            if(preg_match("/\b([A-Za-z,\.]{4,})\b/", $remaining_query) == 1 || preg_match("/\b(?=[A-Za-z,\.]{2,})(?=\s|\.\s)(?=[A-Z]{1,})\b/", $remaining_query) == 1){
                $places = CS50::query("SELECT * FROM places WHERE place_name LIKE ?", $remaining_query . "%");
                $places = CS50::query("SELECT * FROM places WHERE MATCH(place_name, admin_name1) AGAINST (?)", $remaining_query . "%");
                if(count($places) < 5){
                    $places = CS50::query("SELECT * FROM places WHERE admin_name2 LIKE ?", $remaining_query . "%");
                    $places = CS50::query("SELECT * FROM places WHERE MATCH(admin_name2, admin_name1) AGAINST (?)", $remaining_query . "%");
                    $places = CS50::query("SELECT * FROM places WHERE MATCH(place_name, admin_name2) AGAINST (?)", $remaining_query . "%");
                }
            }
            
        }
    }
    else{
        if ($lat != 0.0){
            $places[] = CS50::query("SELECT * FROM places WHERE latitude LIKE ? AND longitude LIKE ?", $lat . "%", $lon . "%");
        }
        else{
            $places[] = CS50::query("SELECT * FROM places WHERE postal_code LIKE ?", $postal . "%");
        }
    }
    // TODO: search database for places matching $_GET["geo"], store in $places
    

    // output places as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($places, JSON_PRETTY_PRINT));

?>