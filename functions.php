<?php
function getCountries(){
    global $db;
    $sql="SELECT * from countries";
    $countries = $db->query($sql);
    if (count($countries)>0){
	foreach ($countries as $k=>$v){
	  $option.='<option value="'.$v['id'].'">'.$v['name'].'</option>';  
	}
    }
    return $option;
}
function getCities(){
    global $db;
    $sql="SELECT * from cities";
    $countries = $db->query($sql);
    if (count($countries)>0){
	foreach ($countries as $k=>$v){
	  $option.='<option value="'.$v['id'].'">'.$v['name'].'</option>';  
	}
    }
    return $option;
}
function getContacttype(){
    global $db;
    $sql="SELECT * from contact_type";
    $countries = $db->query($sql);
    if (count($countries)>0){
	foreach ($countries as $k=>$v){
	  $option.='<option value="'.$v['id'].'">'.$v['name'].'</option>';  
	}
    }
    return $option;
}
