/**
* ownCloud - ajax frontend
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OC_FILES.browser=new  Object();

OC_FILES.browser.show=function(dir){
   dir=(dir)?dir:'';
   OC_FILES.dir=dir;
   OC_FILES.getdirectorycontent(dir,OC_FILES.browser.show_callback);
}

OC_FILES.browser.show_callback=function(content){
    var dir=OC_FILES.dir
    var dirs=dir.split('/');
    var tr=null;
    var td=null;
    var img=null;
    
    body=document.getElementsByTagName('body').item(0);
    body.addEvent('onclick',OC_FILES.browser.hideallactions);
    
    //remove current content;
    var contentNode=document.getElementById('content');
    if(contentNode.hasChildNodes()){
       while(contentNode.childNodes.length >=1){
          contentNode.removeChild(contentNode.firstChild);
       }
    }
    
    var browser=document.createElement('div');
    browser.className='center';
    var table=document.createElement('table');
    browser.appendChild(table);
    
    // breadcrumb
    if(dirs.length>0) {
       table.setAttribute('cellpadding',2);
       table.setAttribute('cellspacing',0);
       var tbody=document.createElement('tbody');//some IE versions need this
       table.appendChild(tbody);
       tr=document.createElement('tr');
       tbody.appendChild(tr);
       td=document.createElement('td');
       tr.appendChild(td);
       td.setAttribute('colspan','2');
       td=document.createElement('td');
       tr.appendChild(td);
       td.setAttribute('colspan','4');
       td.className='breadcrumb';
       var a=document.createElement('a');
       td.appendChild(a);
       a.setAttribute('href','#');
       a.addEvent('onclick',OC_FILES.browser.show);
       a.appendChild(document.createTextNode('Home'));
       var currentdir='';
       for(index in dirs) {
          d=dirs[index];
          currentdir=currentdir+'/'+d;
          if(d!=''){
             a=document.createElement('a');
             td.appendChild(a);
             a.setAttribute('href','#'+currentdir);
             a.setAttribute('onclick','OC_FILES.browser.show("'+currentdir+'")');
             img=document.createElement('img');
             a.appendChild(img);
             img.src=WEBROOT+'/img/arrow.png';
             a.appendChild(document.createTextNode(' ' +d));
          }
      }
    }

    // files and directories
    table.setAttribute('cellpadding',6);
    table.setAttribute('cellspacing',0);
    table.className='browser';
    var tbody=document.createElement('tbody');//some IE versions need this
    table.appendChild(tbody);
    var filesfound=false;
    var sizeTd=null;
    if(content){
         tr=document.createElement('tr');
         tbody.appendChild(tr);
         tr.className='browserline';
         td=document.createElement('td');
         tr.appendChild(td);
         td.setAttribute('colspan','2');
         input=document.createElement('input');
         input.setAttribute('type','checkbox');
         input.setAttribute('name','fileSelector');
         input.setAttribute('value','select_all');
         input.setAttribute('id','select_all');
         input.addEvent('onclick',OC_FILES.selectAll);
         td.appendChild(input);
         td=document.createElement('td');
         tr.appendChild(td);
         td.setAttribute('colspan','4');
         dropdown=document.createElement('select');
         td.appendChild(dropdown);
         dropdown.setAttribute('id','selected_action');
         for(index in this.actions_selected){
            if(this.actions_selected[index].call){
                option=document.createElement('option');
                dropdown.appendChild(option);
                option.setAttribute('value',index);
                option.appendChild(document.createTextNode(index));
            }
         }
         td.appendChild(document.createTextNode(' selected. '));
         button=document.createElement('button');
         td.appendChild(button);
         button.appendChild(document.createTextNode('Go'));
         button.addEvent('onclick',OC_FILES.action_selected);
         for(index in content){
          var file=content[index];
          if(file.name){
             file.name=file.name.replace('\'','');
             OC_FILES.files[file['name']]=new OC_FILES.file(dir,file['name'],file['type']);
             tr=document.createElement('tr');
             tbody.appendChild(tr);
             tr.className='browserline';
             td=document.createElement('td');
             tr.appendChild(td);
             input=document.createElement('input');
             input.setAttribute('type','checkbox');
             input.setAttribute('name','fileSelector');
             input.setAttribute('value',file['name']);
             td.appendChild(input);
             tr.appendChild(OC_FILES.browser.showicon(file['type']));
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='nametext';
             td.setAttribute('name',file['name']);
             td.setAttribute('id',file['name']);
             a=document.createElement('a');
             td.appendChild(a);
             a.appendChild(document.createTextNode(file['name']))
             if(file['type']=='dir'){
                a.addEvent('onclick',OC_FILES.browser.show,[dir+'/'+file['name']]);
                td.setAttribute('colspan',2);
                a.setAttribute('href','#'+dir+'/'+file['name']);
             }else{
                a.setAttribute('href',WEBROOT+'/?dir=/'+dir+'&file='+file['name']);
                sizeTd=document.createElement('td');
                tr.appendChild(sizeTd);
                sizeTd.className='sizetext';
                sizeTd.appendChild(document.createTextNode(sizeFormat(file['size'])));
             }
             a=document.createElement('a');
             img=document.createElement('img');
             td.appendChild(img);
             img.className='file_actions';
             img.alt=''
             img.title='actions';
             img.src=WEBROOT+'/img/arrow_down.png';
             img.setAttribute('onclick','OC_FILES.browser.showactions(\''+file['name']+'\')')
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='sizetext';
             td.appendChild(document.createTextNode(file['date']));
          }
       }
    }
    td=document.createElement('td');
    tr.appendChild(td);
    tr=document.createElement('tr');
    tbody.appendChild(tr);
    tr.className='utilrow';
    td=document.createElement('td');
    tr.appendChild(td);
    td.className='upload';
    td.setAttribute('colspan','6');
    OC_FILES.browser.showuploader(dir,td,content['max_upload']);
    contentNode.appendChild(browser);
}

OC_FILES.browser.showicon=function(filetype){
   var td=document.createElement('td');
   var img=document.createElement('img');
   td.appendChild(img);
   img.setAttribute('width',16);
   img.setAttribute('height',16);
   if(filetype=='dir'){
      img.src=WEBROOT+'/img/icons/folder.png';
   }else{
      img.src=WEBROOT+'/img/icons/other.png';
   }
   return td;
}

OC_FILES.browser.showuploader=function(dir,parent,max_upload){
   OC_FILES.uploadForm=document.createElement('form');
   OC_FILES.uploadForm.setAttribute('target','uploadIFrame');
   OC_FILES.uploadForm.setAttribute('action','files/upload.php?dir='+dir);
   OC_FILES.uploadForm.method='post';
   OC_FILES.uploadForm.setAttribute('enctype','multipart/form-data');
   OC_FILES.uploadIFrame=document.createElement('iframe');
   OC_FILES.uploadIFrame.className='hidden';
   OC_FILES.uploadIFrame.name='uploadIFrame';
   parent.appendChild(OC_FILES.uploadIFrame);
   var input=document.createElement('input');
   input.setAttribute('type','hidden');
   input.setAttribute('name','MAX_FILE_SIZE');
   input.setAttribute('value',max_upload);
   input.setAttribute('id','max_upload');
   OC_FILES.uploadForm.appendChild(input);
   var file=document.createElement('input');
   file.name='file';
   file.setAttribute('id','fileSelector');
   file.setAttribute('type','file');
   file.addEvent('onchange',OC_FILES.upload,[dir]);
   OC_FILES.uploadForm.appendChild(document.createTextNode('Upload file: '));
   OC_FILES.uploadForm.appendChild(file);
   parent.appendChild(OC_FILES.uploadForm);
}

OC_FILES.browser.show_rename=function(dir,file){
   var item=document.getElementById(file);
   item.oldContent=Array();
   if(item.hasChildNodes()){
      while(item.childNodes.length >=1){
         item.oldContent[item.oldContent.length]=item.firstChild;
         item.removeChild(item.firstChild);
      }
   }
   var form=document.createElement('form');
   form.addEvent('onsubmit',OC_FILES.rename,[dir,file]);
   var input=document.createElement('input');
   input.setAttribute('type','text');
   input.setAttribute('name','newname');
   input.setAttribute('value',file);
   input.setAttribute('id',file+'_newname')
   input.addEvent('onblur',OC_FILES.browser.rename_cancel,[file]);
   form.appendChild(input);
   item.appendChild(form);
   input.focus();
}

OC_FILES.browser.rename_cancel=function(file){
   var item=document.getElementsByName(file).item(0);
   if(item.hasChildNodes()){
      while(item.childNodes.length >=1){
         item.removeChild(item.firstChild);
      }
   }
   for(index in item.oldContent){
      if(item.oldContent[index].nodeType){
         item.appendChild(item.oldContent[index]);
      }
   }
}

OC_FILES.browser.showactions=function(file,hide){
    node=document.getElementById(file);
    if(node &&(node.actionsshown || hide)){
        if(node.actionsdiv){
            node.removeChild(node.actionsdiv);
        }
        node.actionsdiv=null;
        node.actionsshown=false
    }else if(node){
        node.actionsshown=true
        div=document.createElement('div');
        node.actionsdiv=div;
        div.className='fileactionlist';
        table=document.createElement('table');
        div.appendChild(table);
        tbody=document.createElement('tbody');
        table.appendChild(tbody);
        actions=OC_FILES.files[file].actions;
        for(name in actions){
            if(actions[name].call){
                tr=document.createElement('tr');
                tbody.appendChild(tr);
                td=document.createElement('td');
                tr.appendChild(td);
                a=document.createElement('a');
                td.appendChild(a);
                a.appendChild(document.createTextNode(name));
                td.addEvent('onclick',new callBack(OC_FILES.files[file].actions[name],OC_FILES.files[file]));
            }
        }
        node.appendChild(div);
        OC_FILES.hideallenabled=false;
        setTimeout('OC_FILES.hideallenabled=true',50);
    }
}

OC_FILES.browser.hideallactions=function(){
    if(OC_FILES.hideallenabled){
        for(name in OC_FILES.files){
            if(OC_FILES.files[name].hideactions){
                OC_FILES.files[name].hideactions.call(OC_FILES.files[name]);
            }
        }
    }
}

OC_FILES.hideallenabled=true; //used to prevent browsers from hiding actionslists right after they are displayed;

sizeFormat=function(size){
    var orig=size;
    var steps=Array('B','KiB','MiB','GiB','TiB');
    var step=0;
    while(size>(1024*2)){
        step++;
        size=size/1024;
    }
    if(size.toFixed){
        size=size.toFixed(2);
    }
    return ''+size+' '+steps[step];
}