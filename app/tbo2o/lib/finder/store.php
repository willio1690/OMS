<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_finder_store{

    var $addon_cols = "store_id,outer_store_id,sync";
    var $column_edit = "操作";
    var $column_edit_width = 120;
    var $column_edit_order = 1;
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        $store_id = $row[$this->col_prefix.'store_id'];
        $outer_store_id = $row[$this->col_prefix.'outer_store_id'];
        $sync = $row[$this->col_prefix.'sync'];

        $button = '<a href="index.php?app=tbo2o&ctl=admin_store&act=edit&p[0]='.$store_id.'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
        if(!$outer_store_id && ($sync ==1 || $sync ==2)){
            $button .= "&nbsp;|&nbsp;".sprintf('<a href="javascript:if (confirm(\'你确定同步该门店信息吗？\')){W.page(\'index.php?app=tbo2o&ctl=admin_store&act=addStore&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">同步</a>', $store_id,$finder_id);
        }elseif($outer_store_id && ($sync ==1 || $sync ==2)){
            $button .= "&nbsp;|&nbsp;".sprintf('<a href="javascript:if (confirm(\'你确定更新该门店信息吗？\')){W.page(\'index.php?app=tbo2o&ctl=admin_store&act=updateStore&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">更新</a>', $store_id,$finder_id);
        }elseif($outer_store_id && $sync == 3){
            $button .= "&nbsp;|&nbsp;".sprintf('<a href="javascript:if (confirm(\'你确定删除该门店信息吗？\')){W.page(\'index.php?app=tbo2o&ctl=admin_store&act=delStore&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">删除</a>', $store_id,$finder_id);
        }

        return $button;
    }

}