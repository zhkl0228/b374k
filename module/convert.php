<?php
$GLOBALS['module']['convert']['id'] = "convert";
$GLOBALS['module']['convert']['title'] = "Convert";
$GLOBALS['module']['convert']['js_ontabselected'] = "
if((!portableMode) && ($('#decodeResult').children().length==1)) $('#decodeStr').focus();";
$GLOBALS['module']['convert']['content'] = "
<table class='boxtbl'>
<thead>
	<tr><th colspan='2'><p class='boxtitle'>Convert</p></th></tr>
</thead>
<tbody>
	<tr><td colspan='2'><textarea style='height:140px;min-height:140px;' id='decodeStr'></textarea></td></tr>
	<tr><td colspan='2'><span class='button' onclick='decode_go();'>convert</span></td></tr>
</tbody>
<tfoot id='decodeResult'><tr><td colspan='2'>You can also press ctrl+enter to submit</td></tr></tfoot>
</table>";

if(!function_exists('decode')){
	function decode($str){
		$res = "";
		$length = (int) strlen($str);

		$res .= decode_line("md5", md5($str), "input");
        $res .= decode_line("md5(md5)", md5(md5($str)), "input");
		$res .= decode_line("sha1", sha1($str), "input");
        $res .= decode_line("sha1(sha1)", sha1(sha1($str)), "input");
        $res .= decode_line("mysql", mysql_old_password_hash($str), "input");
        $res .= decode_line("mysql5", '*'.strtoupper(sha1(sha1($str,TRUE))), "input");
        $res .= decode_line("md5(sha1)", md5(sha1($str)), "input");
        $res .= decode_line("sha1(md5)", sha1(md5($str)), "input");

		$res .= decode_line("base64 encode", base64_encode($str), "textarea");
		$base64_decoded = base64_decode($str);
		$res .= decode_line("base64 decode", $base64_decoded.' => '.bin2hex($base64_decoded), "textarea");

		$hex_string = @pack("H*" , $str);
		$res .= decode_line("hex to string", $hex_string.' => '.bin2hex($hex_string), "textarea");
		$res .= decode_line("string to hex", bin2hex($str), "textarea");

		$ascii = "";
		for($i=0; $i<$length; $i++){
			$ascii .= ord(substr($str,$i,1))." ";
		}
		$res .= decode_line("ascii char", trim($ascii), "textarea");

		$res .= decode_line("reversed", strrev($str), "textarea");
		$res .= decode_line("lowercase", strtolower($str), "textarea");
		$res .= decode_line("uppercase", strtoupper($str), "textarea");

		$res .= decode_line("urlencode", urlencode($str), "textarea");
		$res .= decode_line("urldecode", urldecode($str), "textarea");
		$res .= decode_line("rawurlencode", rawurlencode($str), "textarea");
		$res .= decode_line("rawurldecode", rawurldecode($str), "textarea");

		$res .= decode_line("htmlentities", html_safe($str), "textarea");

		if(function_exists('hash_algos')){
			$algos = hash_algos();
			foreach($algos as $algo){
				if(($algo=='md5')||($algo=='sha1')) continue;
				$res .= decode_line($algo, hash($algo, $str), "input");
			}
		}

		return $res;
	}
}

if(!function_exists('decode_line')){
	function decode_line($type, $result, $inputtype){
		$res = "<tr><td class='colFit'>".$type."</td><td>";
		if($inputtype=='input'){
			$res .= "<input type='text' value='".html_safe($result)."' ondblclick='this.select();'>";
		}
		else{
			$res .= "<textarea style='height:80px;min-height:80px;' ondblclick='this.select();'>".html_safe($result)."</textarea>";
		}
		return $res;
	}
}

/**
 * MySQL "OLD_PASSWORD()" AKA MySQL323 HASH FUNCTION
 * This is the password hashing function used in MySQL prior to version 4.1.1
 * By Rev. Dustin Fineout 10/9/2009 9:12:16 AM
 **/
if(!function_exists('mysql_old_password_hash')){
    function mysql_old_password_hash($input){
        $nr = 1345345333;
        $add = 7;
        $nr2 = 0x12345671;
        $tmp = null;
        $inlen = strlen($input);
        for ($i = 0; $i < $inlen; $i++) {
            $byte = substr($input, $i, 1);
            if ($byte == ' ' || $byte == "\t") continue;
            $tmp = ord($byte);
            $nr ^= ((($nr & 63) + $add) * $tmp) + (($nr << 8) & 0xFFFFFFFF);
            $nr2 += (($nr2 << 8) & 0xFFFFFFFF) ^ $nr;
            $add += $tmp;
        }
        $out_a = $nr & ((1 << 31) - 1);
        $out_b = $nr2 & ((1 << 31) - 1);
        return sprintf("%08x%08x", $out_a, $out_b);
    }
}

if(isset($p['decodeStr'])){
	$decodeStr = $p['decodeStr'];
	output(decode($decodeStr));
}
?>