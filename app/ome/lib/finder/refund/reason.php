<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2021/7/9 17:04:29
 * @describe: finder
 * ============================
 */
class ome_finder_refund_reason {

    var $column_edit = "操作";
    var $column_edit_width = "200";
    function column_edit($row){
        $edit ='  <a target="dialog::{width:350,height:200,title:\'编辑退款原因\'}" href="index.php?app=ome&ctl=admin_refund_reason&act=edit&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'">编辑</a>  ';
        return $edit;
    }
}