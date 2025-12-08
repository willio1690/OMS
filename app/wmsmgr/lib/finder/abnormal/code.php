<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * WMS仓储异常错误码
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class wmsmgr_finder_abnormal_code
{
    var $addon_cols = '';
    
    //操作
    var $column_confirm = '操作';
    var $column_confirm_width = '120';
    var $column_confirm_order = 1;
    function column_confirm($row)
    {
        $abnormal_id = $row['abnormal_id'];
        $url = "index.php?app=wmsmgr&ctl=admin_abnormal_code&act=edit&p[0]={$abnormal_id}&finder_id={$_GET['_finder']['finder_id']}";
        
        $str = "<a href='javascript:void(0);' target='download' onclick=\"new Dialog('%s', {width:500,height:300,title:'修改错误码'}); \">修改</a>";
        
        return sprintf($str, $url);
    }
}
