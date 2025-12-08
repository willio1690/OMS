<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 京东钱包流水Finder类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_finder_jdbill
{
    //调用扩展字段
    public $addon_cols = 'error_data';
    
    var $column_edit = '操作';
    var $column_edit_width = 120;
    var $column_edit_order = 2;
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $view = isset($_GET['view']) ? $_GET['view'] : 0;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        
        //查看明细
        $url = 'index.php?app=financebase&ctl=admin_shop_settlement_jdbill&act=detailed';
        $seeBtn = '<a href="'. $url .'&id='. $row['id'] .'&finder_id='. $finder_id .'&view='.$view.'&page='.$page.'" >查看明细</a>';
        
        return $seeBtn;
    }
    
    var $column_download = '文件下载';
    var $column_download_width = "100";
    var $column_download_order = 5;
    function column_download($row)
    {
        $data = $row[$this->col_prefix .'error_data'];
        if (!$data[10]) {
            return '';
        }
        
        $text = '错误文件';
        return '<a target="_blank" href="index.php?app=financebase&ctl=admin_shop_settlement_jdbill&act=downloaderr&id='. $row['id'] .'" >'.$text.'</a>';
    }
}