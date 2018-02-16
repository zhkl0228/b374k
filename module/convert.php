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
	<tr><td colspan='2'><input id='salt' placeholder='Salt (Optional)' /></td></tr>
	<tr><td colspan='2'><span class='button' onclick='decode_go();'>convert</span></td></tr>
</tbody>
<tfoot id='decodeResult'><tr><td colspan='2'>You can also press ctrl+enter to submit</td></tr></tfoot>
</table>";

if(!function_exists('decode')){
	function decode($str, $salt){
		$res = "";
		$length = (int) strlen($str);

		$res .= decode_line("md5", md5($str), "input");
        $res .= decode_line("md5(md5)", md5(md5($str)), "input");
		$res .= decode_line("sha1", sha1($str), "input");
        $res .= decode_line("sha1(sha1)", sha1(sha1($str)), "input");
        $res .= decode_line("md5(sha1)", md5(sha1($str)), "input");
        $res .= decode_line("sha1(md5)", sha1(md5($str)), "input");
        $res .= decode_line("mysql", mysql_old_password_hash($str), "input");
        $res .= decode_line("mysql5", '*'.strtoupper(sha1(sha1($str,TRUE))), "input");
        $res .= decode_line("ntlm", NTLMHash($str), "input");

        if(strlen($salt) < 1) {
            $salt = null;
        }
        $res .= decode_line("Crypt (all Unix servers)", enctype_crypt($str, $salt), "input");
        $res .= decode_line("MD5 (Apache servers only)", cryptApr1Md5($str, $salt), "input");

        $res .= decode_line("SHA-1 (Netscape-LDIF / Apache servers)", enctype_sha1($str), "input");

		$res .= decode_line("base64 encode", base64_encode($str), "textarea");
		$base64_decoded = base64_decode($str);
		$res .= decode_line("base64 decode", $base64_decoded, "textarea");

		$hex_string = @pack("H*" , $str);
		$res .= decode_line("hex to string", $hex_string, "textarea");
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
			$res .= "<input type='text' url-encoded='".rawurlencode($result)."' ondblclick='this.select();'>";
		}else{
			$res .= "<textarea style='height:80px;min-height:80px;' url-encoded='".rawurlencode($result)."' ondblclick='this.select();'></textarea>";
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
        $nr1 = 1345345333;
        $add = 7;
        $nr2 = 0x12345671;
        $inlen = strlen($input);
        for ($i = 0; $i < $inlen; $i++) {
            $byte = substr($input, $i, 1);
            if ($byte == ' ' || $byte == "\t") continue;
            $tmp = ord($byte);
            $nr1 ^= ((($nr1 & 63) + $add) * $tmp) + (($nr1 << 8) & 0xFFFFFFFF);
            $nr2 += (($nr2 << 8) & 0xFFFFFFFF) ^ $nr1;
            $add += $tmp;
        }
        $out1 = $nr1 & ((1 << 31) - 1);
        $out2 = $nr2 & ((1 << 31) - 1);
        return sprintf("%08x%08x", $out1, $out2);
    }
}

if(!function_exists('enctype_crypt')){
    function enctype_crypt($input, $extra_salt=null){
        if (strlen($input) > 8) {
            return 'Only the first 8 characters are taken into account when \'crypt\' algorithm is used.';
        }
        $chars     = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $len       = strlen($chars) - 1;
        $salt      = $extra_salt == null ? ($chars[mt_rand(0, $len)] . $chars[mt_rand(0, $len)]) : $extra_salt;
        return crypt($input, $salt);
    }
}

if(!function_exists('cryptApr1Md5')) {
    function cryptApr1Md5($input, $extra_salt=null){
        $salt = $extra_salt == null ? substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8) : $extra_salt;
        $len = strlen($input);
        $text = $input . '$apr1$' . $salt;
        $bin = pack("H32", md5($input . $salt . $input));
        for ($i = $len; $i > 0; $i -= 16) {
            $text .= substr($bin, 0, min(16, $i));
        }
        for ($i = $len; $i > 0; $i >>= 1) {
            $text .= ($i & 1) ? chr(0) : $input{0};
        }
        $bin = pack("H32", md5($text));
        for ($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $input : $bin;
            if ($i % 3) $new .= $salt;
            if ($i % 7) $new .= $input;
            $new .= ($i & 1) ? $bin : $input;
            $bin = pack("H32", md5($new));
        }
        $tmp = '';
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) $j = 5;
            $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
        }
        $tmp = chr(0) . chr(0) . $bin[11] . $tmp;
        $tmp = base64_encode($tmp);
        $tmp = substr($tmp, 2);
        $tmp = strrev($tmp);
        $tmp = strtr($tmp,
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
            "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
        return "$" . "apr1" . "$" . $salt . "$" . $tmp;
    }
}

if(!function_exists('enctype_sha1')){
    function enctype_sha1($input){
        $hash = base64_encode(sha1($input, true));
        return '{SHA}' . $hash;
    }
}

if(!function_exists('NTLMHash')) {
    function NTLMHash($Input){
        // Convert the password from UTF8 to UTF16 (little endian)
        $Input = convert_encode('UTF-8', 'UTF-16LE', $Input);

        // Encrypt it with the MD4 hash
        $MD4Hash = function_exists('mhash') ? bin2hex(mhash(MHASH_MD4, $Input)) : hash('md4', $Input);

        // Return the result
        return ($MD4Hash);
    }
}

if(isset($p['decodeStr'])){
	$decodeStr = $p['decodeStr'];
	$salt = $p['salt'];
	output(decode($decodeStr, $salt));
}
?>