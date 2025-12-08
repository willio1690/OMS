<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 特性列表项扩展Lib
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class flowctrl_finder_feature{

    var $addon_cols = "type,config";

    var $column_edit = '操作';
    function column_edit($row){
        if($_GET['ctl'] == 'admin_feature' && $_GET['act'] == 'index'){
            return "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=flowctrl&ctl=admin_feature&act=edit&p[0]={$row[ft_id]}&finder_id={$_GET[_finder][finder_id]}',{width:600,height:600,title:'编辑特性'});\">编辑</a>";
        }else{
            return '-';
        }
    }


    //处理的模式
    var $column_process_mode = '处理方式';
    var $column_process_mode_width = 200;
    var $column_process_mode_order = 300;
    function column_process_mode($row)
    {
        $node = $row[$this->col_prefix.'type'];
        $config = $row[$this->col_prefix.'config'];
        $flowConfLib = kernel::single('flowctrl_conf');
        $desc = $flowConfLib->getNodeCnfDescByNode($node, $config);
        return $desc ? $desc : '-';
    }
}
