<?php

if(!function_exists('getCountry')){
    function getCountry($code){
        switch($code){
            case "MYS": return "Malaysia";
        }
    }
}

if(!function_exists('getState')){
    function getState($code){
        switch($code){
            case "MY-01": return "Johor";
            case "MY-02": return "Kedah";
            case "MY-03": return "Kelantan";
            case "MY-04": return "Melaka";
            case "MY-05": return "Negeri Sembilan";
            case "MY-06": return "Pahang";
            case "MY-07": return "Pulau Pinang";
            case "MY-08": return "Perak";
            case "MY-09": return "Perlis";
            case "MY-10": return "Selangor";
            case "MY-11": return "Terengganu";
            case "MY-12": return "Sabah";
            case "MY-13": return "Sarawak";
            case "MY-14": return "Kuala Lumpur";
            case "MY-15": return "Labuan";
            case "MY-16": return "Putrajaya";
        }
    }
}

if(!function_exists('formatDate')){
    function formatDate($date){
        $newDate = new DateTime($date);
        return $newDate->format('M Y');
    }
}

if(!function_exists('formatDateTimeZone')){
    function formatDateTimeZone($datetime, $needConvert = 0){
        if($datetime == null){
            return null;
        }
        if($needConvert == 1){            
            $date = new DateTime($datetime, new DateTimeZone('UTC'));
            $datetime = $date->setTimeZone(new DateTimeZone('Asia/Kuala_Lumpur'));
        }
        return $datetime->format('Y-m-d H:i:s');
    }
}

if(!function_exists('roundtofive')){
    function roundtofive($number){
        $process = round($number,1)*10;
        $reminder = $process%10;
        if($reminder < 3){
            $final = $process - $reminder;
        }else if($reminder > 7){
            $final = $process - $reminder + 10;
        }else{
            $final = $process - $reminder + 5;
        }
        return $final/10;
    }
}


if(!function_exists('maskNumber')) {
    function maskNumber($number) {
        $middle_string ="";
        $length = strlen($number);

        if ($length == 1) {
            return $number;
        }
        if ($length < 4) {
            return str_pad(substr($number, -1), strlen($number), '*', STR_PAD_LEFT);
        }
        else if ($length < 8) {
            return str_pad(substr($number, -2), strlen($number), '*', STR_PAD_LEFT);
        }
        else {
            return str_pad(substr($number, -4), strlen($number), '*', STR_PAD_LEFT);
        }
    }
}
