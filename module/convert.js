Zepto(function($){
	$('#decodeStr').on('keydown', function(e){
		if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
			decode_go();
		}
		fix_tabchar(this, e);
	});
});

function decode_go(){
	decodeStr = $('#decodeStr').val();
	send_post({decodeStr:decodeStr}, function(res, decode_fail){
		if(res!='error'){
			var result = $('#decodeResult');
            result.html('');
            result.html(res);
            if(decode_fail) {
                // result.find('input,textarea').css("background-color", "gray");
                result.find('input,textarea').attr("readonly", "readonly");
            }
		}
	});
}
