<?php

if(!function_exists('getLanguages')){
    function getLanguages(){
        return ['en', 'cn', 'ms'];
    }
}

if(!function_exists('getCurrentLanguage')){
    function getCurrentLanguage($url){
        $languages = ['en', 'cn'];

        $url_path = parse_url($url, PHP_URL_PATH);
        $lang = substr($url_path, 1, 2);

        if($lang == '') {
            return 'en';
        }
        else {
            if(in_array($lang, $languages)) {
                return $lang;
            }
            else {
                return 'en';
            }
        }
    }
}