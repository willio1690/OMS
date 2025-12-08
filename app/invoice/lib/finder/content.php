<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 +----------------------------------------------------------
 * 发票内容管理
 +----------------------------------------------------------
 * Time: 2016-05-31 $
 +----------------------------------------------------------
 */
class invoice_finder_content{
    var $column_edit  = '操作';
    var $column_edit_order = 5;
    var $column_edit_width = '50';
    function column_edit($row){
         if(intval($row['content_id']) != 1){
             return '<a href="index.php?app=invoice&ctl=admin_content&act=edit&content_id='.$row['content_id'].'&finder_id='.$_GET['_finder']['finder_id'].'" target="dialog::{width:400,height:100,title:\'编辑发票内容\'}">编辑</a>';
         }
    }
}