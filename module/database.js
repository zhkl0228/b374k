Zepto(function($){
	db_init();

});

var dbSupported = "";
var dbPageLimit = 50;

function db_init(){
	if((dbSupported = localStorage.getItem('db_supported'))){
		db_bind();
		output("db : "+dbSupported);
		db_add_supported();
	}
	else{
		send_post({dbGetSupported:""}, function(res){
			if(res!="error"){
				localStorage.setItem('dbSupported', res);
				dbSupported = res;
				db_bind();
				output("db : "+dbSupported);
				db_add_supported();
			}
		});
	}
}

function db_add_supported(){
	splits = dbSupported.split(",");
	$.each(splits, function(i, k){
		$('#dbType').append("<option>"+k+"</option>");
	});
}

function db_bind(){
	$('#dbType').on('change', function(e){
		type = $('#dbType').val();
		if((type=='odbc')||(type=='pdo')){
			$('.dbHostLbl').html('DSN / Connection String');
			$('.dbUserRow').show();
			$('.dbPassRow').show();
			$('.dbPortRow').hide();

		}
		else if((type=='sqlite')||(type=='sqlite3')){
			$('.dbHostLbl').html('DB File');
			$('.dbUserRow').hide();
			$('.dbPassRow').hide();
			$('.dbPortRow').hide();

		}
		else{
			$('.dbHostLbl').html('Host');
			$('.dbUserRow').show();
			$('.dbPassRow').show();
			$('.dbPortRow').show();
		}
	});

	$('#dbQuery').on('focus', function(e){
		if($('#dbQuery').val()=='You can also press ctrl+enter to submit'){
			$('#dbQuery').val('');
		}
	});
	$('#dbQuery').on('blur', function(e){
		if($('#dbQuery').val()==''){
			$('#dbQuery').val('You can also press ctrl+enter to submit');
		}
	});
	$('#dbQuery').on('keydown', function(e){
		if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
			db_run();
		}
	});
}

function db_nav_bind(){
	dbType = $('#dbType').val();
	$('.boxNav').off('click');
	$('.boxNav').on('click', function(){
		$(this).next().toggle();
	});

	$('.dbTable').off('click');
	$('.dbTable').on('click', function(){
		type = $('#dbType').val();
		table = $(this).html();
		db = $(this).parent().parent().parent().prev().html();
		db_query_tbl(type, db, table, 0, dbPageLimit);
	});
}

function db_connect(){
	dbType = $('#dbType').val();
	dbHost = $('#dbHost').val();
	dbUser = $('#dbUser').val();
	dbPass = $('#dbPass').val();
	dbPort = $('#dbPort').val();
	send_post({dbType:dbType, dbHost:dbHost, dbUser:dbUser, dbPass:dbPass, dbPort:dbPort}, function(res){
		if(res!='error'){
			$('#dbNav').html(res);
			$('.dbHostRow').hide();
			$('.dbUserRow').hide();
			$('.dbPassRow').hide();
			$('.dbPortRow').hide();
			$('.dbConnectRow').hide();
			$('.dbQueryRow').show();
			$('#dbBottom').show();
			db_nav_bind();
		}
		else $('.dbError').html('Unable to connect');
	});
}

function db_disconnect(){
	$('.dbHostRow').show();
	$('.dbUserRow').show();
	$('.dbPassRow').show();
	$('.dbPortRow').show();
	$('.dbConnectRow').show();
	$('.dbQueryRow').hide();
	$('#dbNav').html('');
	$('#dbResult').html('');
	$('#dbBottom').hide();
}

function db_run(){
	dbType = $('#dbType').val();
	dbHost = $('#dbHost').val();
	dbUser = $('#dbUser').val();
	dbPass = $('#dbPass').val();
	dbPort = $('#dbPort').val();
	dbQuery = $('#dbQuery').val();

	if((dbQuery!='')&&(dbQuery!='You can also press ctrl+enter to submit')){
		dbQuery = b64encode(dbQuery)
		send_post({dbType:dbType, dbHost:dbHost, dbUser:dbUser, dbPass:dbPass, dbPort:dbPort, dbQuery:dbQuery}, function(res){
			if(res!='error'){
				$('#dbResult').html(res);
				$('.tblResult').each(function(){
					sorttable.k(this);
				});
			}
		});
	}
}

function db_query_tbl(type, db, table, start, limit){
	dbType = $('#dbType').val();
	dbHost = $('#dbHost').val();
	dbUser = $('#dbUser').val();
	dbPass = $('#dbPass').val();
	dbPort = $('#dbPort').val();

	send_post({dbType:dbType, dbHost:dbHost, dbUser:dbUser, dbPass:dbPass, dbPort:dbPort, dbQuery:'', dbDB:db, dbTable:table, dbStart:start, dbLimit:limit}, function(res){
		if(res!='error'){
			$('#dbResult').html(res);
			$('.tblResult').each(function(){
				sorttable.k(this);
			});
		}
	});
}

function db_pagination(type){
	db = $('#dbDB').val();
	table = $('#dbTable').val();
	start = parseInt($('#dbStart').val());
	limit = parseInt($('#dbLimit').val());
	dbType = $('#dbType').val();

	if(type=='next'){
		start = start+limit;
	}
	else if(type=='prev'){
		start = start-limit;
		if(start<0) start = 0;
	}
	db_query_tbl(dbType, db, table, start, limit);
}

function b64encode(str) {
    var base64EncodeChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
    function utf16to8(str) { 
        var out, i, len, c; 
     
        out = ""; 
        len = str.length; 
        for(i = 0; i < len; i++) { 
            c = str.charCodeAt(i); 
            if ((c >= 0x0001) && (c <= 0x007F)) { 
                out += str.charAt(i); 
            } else if (c > 0x07FF) { 
                out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F)); 
                out += String.fromCharCode(0x80 | ((c >>  6) & 0x3F)); 
                out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F)); 
            } else { 
                out += String.fromCharCode(0xC0 | ((c >>  6) & 0x1F)); 
                out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F)); 
            } 
        } 
        return out; 
    }
    function base64encode(str) { 
        var out, i, len; 
        var c1, c2, c3; 
     
        len = str.length; 
        i = 0; 
        out = ""; 
        while(i < len) { 
            c1 = str.charCodeAt(i++) & 0xff; 
            if(i == len) 
            { 
                out += base64EncodeChars.charAt(c1 >> 2); 
                out += base64EncodeChars.charAt((c1 & 0x3) << 4); 
                out += "=="; 
                break; 
            } 
            c2 = str.charCodeAt(i++); 
            if(i == len) 
            { 
                out += base64EncodeChars.charAt(c1 >> 2); 
                out += base64EncodeChars.charAt(((c1 & 0x3)<< 4) | ((c2 & 0xF0) >> 4)); 
                out += base64EncodeChars.charAt((c2 & 0xF) << 2); 
                out += "="; 
                break; 
            } 
            c3 = str.charCodeAt(i++); 
            out += base64EncodeChars.charAt(c1 >> 2); 
            out += base64EncodeChars.charAt(((c1 & 0x3)<< 4) | ((c2 & 0xF0) >> 4)); 
            out += base64EncodeChars.charAt(((c2 & 0xF) << 2) | ((c3 & 0xC0) >>6)); 
            out += base64EncodeChars.charAt(c3 & 0x3F); 
        } 
        return out; 
    }
    return base64encode(utf16to8(str));
}