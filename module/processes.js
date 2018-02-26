Zepto(function(){
	var ps = localStorage.getItem('process');
	if(ps) {
		ps_init(ps);
	} else {
        show_processes();
	}
});

function show_processes(){
	send_post({showProcesses:''}, function(res){
		if(res!=='error'){
			localStorage.setItem('process', res);
            ps_init(res);
		}
	});
}

function ps_init(process) {
    $('#processes').html(process);
    sorttable.k($('#psTable').get(0));
    ps_bind();
}

function ps_bind(){
	var k = $('.kill');
	k.off('click');
	k.on('click', function(){
		kill_pid(ps_get_pid($(this)));
	});

	cbox_bind('psTable','ps_update_status');
}

function ps_get_pid(el){
	return el.parent().parent().attr('data-pid');
}

function ps_update_status(){
	var ts = $('#psTable').find('.cBoxSelected').not('.cBoxAll').length;
	var ps = $('.psSelected');
	if(ts===0) ps.html('');
	else ps.html(' ( '+ts+' item(s) selected )');
}

function kill_selected(){
	var buffer = get_all_cbox_selected('psTable', 'ps_get_pid');

	var allPid = '';
	$.each(buffer,function(i,v){
		allPid += v + ' ';
	});
	allPid = $.trim(allPid);
	kill_pid(allPid);
}

function kill_pid(allPid){
	var title = 'Kill';
	var content = "<table class='boxtbl'><tr><td colspan='2'><textarea class='allPid' style='height:120px;min-height:120px;' disabled>"+allPid+"</textarea></td></tr><tr><td colspan='2'><span class='button' onclick=\"kill_pid_go();\">kill</span></td></tr></table>";
	show_box(title, content);
}

function kill_pid_go(){
	var ap = $.trim($('.allPid').val());
	if(ap!==''){
		send_post({allPid:ap}, function(res){
			var br = $('.boxresult');
			if(res!=='error'){
				br.html(res + ' process(es) killed');
			}else br.html('Unable to kill process(es)');
			show_processes();
		});
	}
}