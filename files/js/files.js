$(document).ready(function() {
	$('#file_action_panel').attr('activeAction', false);

	//drag/drop of files
	$('#fileList tr td.filename').draggable(dragOptions);
	$('#fileList tr[data-type="dir"] td.filename').droppable(folderDropOptions);
	$('div.crumb').droppable({
		drop: function( event, ui ) {
			var file=ui.draggable.text().trim();
			var target=$(this).attr('data-dir');
			var dir=$('#dir').val();
			while(dir.substr(0,1)=='/'){//remove extra leading /'s
				dir=dir.substr(1);
			}
			dir='/'+dir;
			if(dir.substr(-1,1)!='/'){
				dir=dir+'/';
			}
			if(target==dir){
				return;
			}
			$.ajax({
				url: 'ajax/move.php',
				data: "dir="+dir+"&file="+file+'&target='+target,
				complete: function(data){boolOperationFinished(data, function(){
					FileList.remove(file);
				});}
			});
		},
		tolerance: 'pointer'
	});
	
	// Sets the file-action buttons behaviour :
	$('td.fileaction a').live('click',function(event) {
		event.preventDefault();
		FileActions.display($(this).parent());
	});
	
	// Sets the file link behaviour :
	$('td.filename a').live('click',function(event) {
		event.preventDefault();
		var filename=$(this).parent().parent().attr('data-file');
		if(!FileList.isLoading(filename)){
			var mime=$(this).parent().parent().attr('data-mime');
			var type=$(this).parent().parent().attr('data-type');
			var action=FileActions.getDefault(mime,type);
			if(action){
				action(filename);
			}
		}
	});
	
	// Sets the select_all checkbox behaviour :
	$('#select_all').click(function() {
		if($(this).attr('checked')){
			// Check all
			$('td.selection input:checkbox').attr('checked', true);
			$('td.selection input:checkbox').parent().parent().addClass('selected');
		}else{
			// Uncheck all
			$('td.selection input:checkbox').attr('checked', false);
			$('td.selection input:checkbox').parent().parent().removeClass('selected');
		}
	});
	
	$('td.selection input:checkbox').live('click',function() {
		$(this).parent().parent().toggleClass('selected');
		if(!$(this).attr('checked')){
			$('#select_all').attr('checked',false);
		}else{
			if($('td.selection input:checkbox:checked').length==$('td.selection input:checkbox').length){
				$('#select_all').attr('checked',true);
			}
		}
	});
	
	$('#file_newfolder_form').submit(function(event) {
		event.preventDefault();
		$.ajax({
			url: 'ajax/newfolder.php',
			data: "dir="+$('#dir').val()+"&foldername="+$('#file_newfolder_name').val(),
			complete: function(data){boolOperationFinished(data, function(){
				var date=formatDate(new Date());
				FileList.addDir($('#file_newfolder_name').val(),'0 B',date)
			});}
		});
		$('#file_newfolder_submit').fadeOut(250).trigger('vanish');
	});
	
	$('#file_newfolder_name').click(function(){
		if($('#file_newfolder_name').val() == 'New Folder'){
			$('#file_newfolder_name').val('');
		}
	});
	
	$('#file_newfolder_name').bind('keyup', adjustNewFolderSize);
	
	$('#file_newfolder_submit').bind('vanish', function() {
		$('#file_newfolder_name').bind('keyup', adjustNewFolderSize);
		unsplitSize($('#file_newfolder_name'),$('#file_newfolder_submit'));
	});
	
	$('#file_newfolder_name').focusout(function(){
		if($('#file_newfolder_name').val() == '') {
			$('#file_newfolder_form')[0].reset();
			$('#file_newfolder_submit').fadeOut(250).trigger('vanish');
		}
	});
	
	$('.download').live('click',function(event) {
		var files=getSelectedFiles('name').join(';');
		
		//send the browser to the download location
		var dir=$('#dir').val()||'/';
// 		alert(files);
		window.location='ajax/download.php?files='+encodeURIComponent(files)+'&dir='+encodeURIComponent(dir);
		return false;
	});
	
	$('.delete').live('click',function(event) {
		var files=getSelectedFiles('name').join(';');
		
		$.ajax({
			url: 'ajax/delete.php',
			data: "dir="+$('#dir').val()+"&files="+encodeURIComponent(files),
			complete: function(data){
				boolOperationFinished(data, function(){
					$('td.selection input:checkbox:checked').parent().parent().each(function(i,element){
						FileList.remove($(element).attr('data-file'));
					});
				});
			}
		});
		
		return false;
	});

	$('.file_upload_start').live('change',function(){
		var form=$(this).parent().parent();
		var uploadId=form.attr('data-upload-id');
		var files=this.files;
		var target=form.children('iframe');
		target.load(function(){
			var response=jQuery.parseJSON(target.contents().find('body').text());
			//set mimetype and if needed filesize
			if(response){
				for(var i=0;i<response.length;i++){
					var file=response[i];
					$('tr[data-file="'+file.name+'"]').attr('data-mime',file.mime);
					if(size=='Pending'){
						$('tr[data-file='+file.name+'] td.filesize').text(file.size);
					}
					FileList.loadingDone(file.name);
				}
			}
		});
		form.submit();
		var date=new Date();
		var uploadTime=formatDate(date);
		for(var i=0;i<files.length;i++){
			if(files[i].size>0){
				var size=simpleFileSize(files[i].size);
			}else{
				var size='Pending';
			}
			FileList.addFile(files[i].name,size,uploadTime,true);
		}
		
		//clone the upload form and hide the new one to allow users to start a new upload while the old one is still uploading
		var clone=form.clone();
		uploadId++;
		clone.attr('data-upload-id',uploadId);
		clone.attr('target','file_upload_target_'+uploadId);
		clone.children('iframe').attr('name','file_upload_target_'+uploadId)
		clone.insertBefore(form);
		form.hide();
	});
	
	//add multiply file upload attribute to all browsers except konqueror (which crashes when it's used)
	if(navigator.userAgent.search(/konqueror/i)==-1){
		$('.file_upload_start').attr('multiple','multiple')
	}
});

var adjustNewFolderSize = function() {
	if($('#file_newfolder_name').val() != '') {
		splitSize($('#file_newfolder_name'),$('#file_newfolder_submit'));
		$('#file_newfolder_name').unbind('keyup', adjustNewFolderSize);
	};
}

function splitSize(existingEl, appearingEl) {
	nw = parseInt($(existingEl).css('width')) - parseInt($(appearingEl).css('width'));
	$(existingEl).css('width', nw + 'px');
	$(appearingEl).fadeIn(250);
}

function unsplitSize(stayingEl, vanishingEl) {
	nw = parseInt($(stayingEl).css('width')) + parseInt($(vanishingEl).css('width'));
	$(stayingEl).css('width', nw + 'px');
	$(vanishingEl).fadeOut(250);
}

function resetFileActionPanel() {
	$('#file_action_panel form').css({"display":"none"});
	$('#file_action_panel').attr('activeAction', false);
}

function boolOperationFinished(data, callback) {
	result = jQuery.parseJSON(data.responseText);
	if(result.status == 'success'){
		callback.call();
	} else {
		alert(result.data.message);
	}
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

function humanFileSize(bytes){
	if( bytes < 1024 ){
		return bytes+' B';
	}
	bytes = Math.round(bytes / 1024, 1 );
	if( bytes < 1024 ){
		return bytes+' kB';
	}
	bytes = Math.round( bytes / 1024, 1 );
	if( bytes < 1024 ){
		return bytes+' MB';
	}
	
	// Wow, heavy duty for owncloud
	bytes = Math.round( bytes / 1024, 1 );
	return bytes+' GB';
}

function simpleFileSize(bytes) {
	mbytes = Math.round(bytes/(1024*1024),1);
	if(bytes == 0) { return '0'; }
	else if(mbytes < 0.1) { return '< 0.1'; }
	else if(mbytes > 1000) { return '> 1000'; }
	else { return mbytes.toFixed(1); }
}

function formatDate(date){
	var monthNames = [ "January", "February", "March", "April", "May", "June",
	"July", "August", "September", "October", "November", "December" ];
	return monthNames[date.getMonth()]+' '+date.getDate()+', '+date.getFullYear()+', '+((date.getHours()<10)?'0':'')+date.getHours()+':'+date.getMinutes();
}


//options for file drag/dropp
var dragOptions={
	distance: 20, revert: 'invalid', opacity: 0.7,
	stop: function(event, ui) {
		$('#fileList tr td.filename').addClass('ui-draggable');
	}
};
var folderDropOptions={
	drop: function( event, ui ) {
		var file=ui.draggable.text().trim();
		var target=$(this).text().trim();
		var dir=$('#dir').val();
		$.ajax({
			url: 'ajax/move.php',
		data: "dir="+dir+"&file="+file+'&target='+dir+'/'+target,
		complete: function(data){boolOperationFinished(data, function(){
			var el=$('#fileList tr[data-file="'+file+'"] td.filename');
			el.draggable('destroy');
			FileList.remove(file);
		});}
		});
	}
}

/**
 * @brief get a list of selected files
 * @param string property (option) the property of the file requested
 * @return array
 *
 * possible values for property: name, mime
 * if property is set, an array with that property for each file is returnd
 * if it's ommited an array of objects with all properties is returned
 */
function getSelectedFiles(property){
	var elements=$('td.selection input:checkbox:checked').parent().parent();
	var files=[];
	elements.each(function(i,element){
		var file={
			name:$(element).attr('data-file'),
				  mime:$(element).attr('data-mime')
		};
		if(property){
			files.push(file[property]);
		}else{
			files.push();
		}
	});
	return files;
}