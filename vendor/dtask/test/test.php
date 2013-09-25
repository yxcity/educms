<?php
//mb_internal_encoding("UTF-8");
$html=file_get_contents('page-0.html');
$result=array();
//$html = mb_convert_encoding($html,"UTF-8","auto");
//$html = preg_replace('/([\x{0000}-\x{0008}]|[\x{000b}-\x{000c}]|[\x{000E}-\x{001F}])/u','',$html);
$html = preg_replace_callback('/([\x{0000}-\x{0008}]|[\x{000b}-\x{000c}]|[\x{000E}-\x{001F}])/u', function($sub_match){return '\u00' . dechex(ord($sub_match[1]));},$html);
if (preg_match('/{"contacts":(\[[\s\S]+\])}\)\.contacts,/',$html, $match)) {
            //$match[1] = str_replace('\"','_oo_',$match[1]);
            //$match[1] = preg_replace_callback('/("(nick_name|remark_name)"):"([^"]*)"/', function($sub_match){return '"' . $sub_match[2] .'":"'.  urlencode($sub_match[3]).'"';}, $match[1]);
            //$match[1] = json_decode(json_encode($match[1]));
            //$str = htmlentities($match[1],ENT_QUOTES,'UTF-8');
            $result['data'] = json_decode($match[1],true);
            //echo $match[1] . "\n";
            
}
var_dump($result);
echo json_last_error();
?>