<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omevirtualwms_finder_callback{

    
    var $column_control = '回传';
    var $column_control_width = 100;
    var $column_control_order = 1;
    var $addon_cols = "method";
    function column_control($row){
        $finder_id = $_GET['_finder']['finder_id'];
        $method = $row['method'];
        $api = array('store.wms.item.add','store.wms.item.update','store.wms.item.query');
        if (in_array($method,$api)){
            return '<a   href="index.php?app=omevirtualwms&ctl=admin_wms&act=callback_goods_html&p[0]='.$row['msg_id'].'&p[1]='.$finder_id.'">回传</a>';
        }else{
            return '<a   href="index.php?app=omevirtualwms&ctl=admin_wms&act=callback_html&p[0]='.$row['msg_id'].'&p[1]='.$finder_id.'" target="dialog::{width:600,height:300,title:\'callback\'}">回传</a>';
        }
        
    }

}
