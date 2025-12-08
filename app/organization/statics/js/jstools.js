/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

//已弃用 后续有调整的 之前开发时调试所用 
function selectOrganization(sel,path,depth,type){
    sel=$(sel);
    if(!sel)return;
    var sel_value=sel.value;
    var sel_panel=sel.getParent();
    var selNext=sel.getNext();
    var areaPanel= sel.getParent('*[package]');
    var hid=areaPanel.getElement('input[type=hidden]');
	var curOption=$(sel.options[sel.selectedIndex]);

    var setHidden=function(sel){
        var rst=[];
		var sel_break = true;		
		
		if (curOption && !curOption.get('has_c')){
			/** 删除多余的三级地区 **/
			var _currChliSpan = sel.getNext('.x-region-child');	
			if (_currChliSpan){
				_currChliSpan.destroy();
			}
			/** end **/
		}
		
		var sels=$ES('select',areaPanel);
		sels.each(function(s){
		  if(s.getValue()!= '_NULL_' && sel_break){
			  var opt = $(s.options[s.selectedIndex]), t = opt.textContent ? opt.textContent : opt.innerText;
			  rst.push(t);
			  if(parseInt(depth) == 2){
				  sel_break = false;
			  }
		  }else{
		    sel_break = false;
		  }
		});
        if(sel.value != '_NULL_'){
		    $E('input',areaPanel).value = areaPanel.get('package')+':'+rst.join('/')+':'+sel.value;
		}else{
		    $E('input',areaPanel).value =function(sel){
			  var s=sels.indexOf(sel)-1;
			  if(s>=0){
				 return areaPanel.get('package')+':'+rst.join('/')+':'+sels[s].value;
			  }
			  return '';
            }(sel);
		}
    };
	if(sel_value=='_NULL_'&&selNext&&(selNext.getTag()=='span' && selNext.hasClass('x-areaSelect'))){
		sel.nextSibling.empty();
        setHidden(sel);
	}else{
		/*nextDepth*/
		if(curOption.get('has_c')){
		  new Request({
				url:'index.php?app=organization&ctl=admin_management&act=selOrganization&path='+path+'&depth='+depth+'&type='+type,
				onSuccess:function(response){
					var e;
					if(selNext && (selNext.getTag()=='span'&& selNext.hasClass('x-region-child'))){
						e = selNext;
					}else{
						e = new Element('span',{'class':'x-region-child'}).inject(sel_panel);
					}
                    setHidden(sel);
					if(response){
						e.set('html',response);
	                    if(hid){
                           hid.retrieve('sel'+depth,function(){})();
                           hid.retrieve('onsuc',function(){})();
                        }
					}else{
						sel.getAllNext().remove();
						setHidden(sel);
						hid.retrieve('lastsel',function(){})(sel);
					}
				}
			}).get();
		}else{
		    sel.getAllNext().remove();
            setHidden(sel);
            if(!curOption.get('has_c')&&curOption.value!='_NULL_')
            hid.retrieve('lastsel',function(){})(sel);
		}
	}
}