<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_order_index_actionbar{
    
    /*
     * 获取按钮列表
     * @return array conf
     */
    public function getActionBar(){
        return  array(
	        		array(
	                    'label'=>'导出模板',
	                    'href'=>'index.php?app=ome&ctl=admin_order&act=exportTemplate',
	                    'target'=>'_blank'
	                ),
        );
    }
    
}