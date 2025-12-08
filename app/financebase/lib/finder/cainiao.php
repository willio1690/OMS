<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_finder_cainiao
{
    var $column_edit = "操作";
    var $column_edit_width = "110";
    var $column_edit_order = 4;
    
    public $addon_cols = 'error_data';//调用字段

    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $view = isset($_GET['view']) ? $_GET['view'] : 0;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        
        //文件类型
        $catType = 'cainiao';
        switch ($row['type'])
        {
            case 'jzt':
                $catType = 'jingzhuntong';
                break;
            case 'jdbill':
                $catType = 'jdbill';
                break;
            default:
        }
        
        $url = '<a href="index.php?app=financebase&ctl=admin_shop_settlement_'. $catType .'&act=detailed';
        $seeBtn = $url .'&id=' . $row['id'] . '&finder_id=' . $finder_id . '&view='.$view.'&page='.$page.'" >查看明细</a>';

        return $seeBtn;
    }
    
    var $column_download = "文件下载";
    var $column_download_width = "100";
    var $column_download_order = 5;

    function column_download($row)
    {
        $data = $row[$this->col_prefix . 'error_data'];
        if (!$data[10]) {
            return '';
        }
        
        //文件类型
        $catType = 'cainiao';
        switch ($row['type'])
        {
            case 'jzt':
                $catType = 'jingzhuntong';
                break;
            case 'jdbill':
                $catType = 'jdbill';
                break;
            default:
        }
        
        $text = '错误文件';
        $url = '<a target="_blank" href="index.php?app=financebase&ctl=admin_shop_settlement_'. $catType .'&act=downloaderr';
        
        return $url .'&id=' . $row['id'] . '" >'.$text.'</a>';
    }
}