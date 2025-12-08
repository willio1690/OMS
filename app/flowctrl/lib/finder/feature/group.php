<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 类目列表扩展Lib类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class flowctrl_finder_feature_group{

    var $column_edit = '操作';
    function column_edit($row){
        if($_GET['ctl'] == 'admin_feature_group' && $_GET['act'] == 'index'){
            return "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=flowctrl&ctl=admin_feature_group&act=edit&p[0]={$row[ftgp_id]}&finder_id={$_GET[_finder][finder_id]}',{width:600,height:400,title:'编辑特性'});\">编辑</a>";
        }else{
            return '-';
        }
    }

}
