<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_gtype{

    var $column_control = '类型操作';
    function column_control($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href=\'index.php?app=ome&ctl=admin_goods_type&act=edit&p[0]='.$row['type_id'].'&finder_id='.$finder_id.'\'" target="dialog::{width:600,height:300,title:\'编辑物料类型\'}">编辑</a>';
    }


}
