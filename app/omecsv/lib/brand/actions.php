<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omecsv_brand_actions{

   
    function action_modify(&$actions){
		kernel::log("action_modify = ".$actions);
		foreach($actions as $key=>$action){
			if($action['label']=="导入"){
				 $actions[$key] = array('label'=>app::get('desktop')->_('导入'),'icon'=>'upload.gif','href'=>'index.php?app=omecsv&ctl=admin_import&act=main&ctler=ome_mdl_brand&add=ome','target'=>'dialog::{width:550,height:250,title:\''.app::get('desktop')->_('导入').'\'}');
			}
		}
		 
    }

  

}
