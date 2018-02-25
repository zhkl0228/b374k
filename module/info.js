Zepto(function($){
	info_init();

});

function info_init(){
    var ir = localStorage.getItem('infoResult');
	if(ir){
		$('.infoResult').html(ir);
	}else{
		info_refresh();
	}
}

function info_toggle(id){
	$('#'+id).toggle();
}

function info_refresh(){
	send_post({infoRefresh:'infoRefresh'}, function(res){
        localStorage.setItem('infoResult', res);
		$('.infoResult').html(res);
	});
}