Zepto(function($){
	$('#decodeStr,#salt').on('keydown', function(e){
		if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
			decode_go();
		}
		fix_tabchar(this, e);
	});
});

function decode_go(){
	var decodeStr = $('#decodeStr').val();
	var salt = $('#salt').val();
	send_post({decodeStr:decodeStr,salt:salt}, function(res, decode_fail){
		if(res!='error'){
			var result = $('#decodeResult');
            result.html('');
            result.html(res);
            result.find('input,textarea').each(function() {
            	var encoded = $(this).attr('url-encoded');
            	try {
            		encoded = decodeURIComponent(encoded);
				} catch(e) {}
				if($(this).is('input')) {
                    $(this).val(encoded);
				} else {
                    $(this).html(encoded);
				}
                if(decode_fail) {
                    $(this).attr("readonly", "readonly");
                }
			});
		}
	});
}
