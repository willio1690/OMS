<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 条码列表项扩展Lib
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class material_finder_barcode{

    var $column_edit = '操作';
    function column_edit($row){
        if($_GET['ctl'] == 'admin_barcode' && $_GET['act'] == 'index'){
            return "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=material&ctl=admin_barcode&act=edit&p[0]={$row['bm_id']}&finder_id={$_GET['_finder']['finder_id']}',{width:600,height:400,title:'编辑条码'});\">编辑</a>";
        }else{
            return '-';
        }
    }

}
