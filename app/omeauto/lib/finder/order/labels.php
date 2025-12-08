<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单标签
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class omeauto_finder_order_labels
{
    var $addon_cols = 'label_color';
    
    //操作
    var $column_confirm = '操作';
    var $column_confirm_width = '90';
    var $column_confirm_order = 1;
    function column_confirm($row)
    {
        $url = "index.php?app=omeauto&ctl=order_labels&act=edit&p[0]={$row['label_id']}&finder_id={$_GET['_finder']['finder_id']}";
        
        $str = "<a href='javascript:void(0);' target='download' onclick=\"new Dialog('%s', {width:500,height:300,title:'修改标签'}); \">修改</a>";
        
        return sprintf($str, $url);
    }
    
    //标记规则
    var $column_label_color = '标签颜色';
    var $column_label_color_width = '120';
    var $column_label_color_order = 15;
    function column_label_color($row)
    {
        return '<span style="color:'. $row[$this->col_prefix.'label_color'] .'">'. $row[$this->col_prefix.'label_color'] .'</span>';
    }
}