<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_vopsetting{
    
    var $addon_cols    ='is_merge,is_auto_combine,dly_mode,carrier_code,branch_id';
    
    var $column_title = '应用店铺';
    var $column_title_width = 150;
    var $column_title_order = 5;
    function column_title($row)
    {
        $shopObj      = app::get('ome')->model('shop');
        $setShopObj   = app::get('purchase')->model('setting_shop');
        
        $sel_shop_ids = array();
        $shopList     = $setShopObj->getList('*', array('sid'=>$row['sid']));
        if($shopList)
        {
            foreach ($shopList as $key => $val)
            {
                $sel_shop_ids[]    = $val['shop_id'];
            }
        }
        
        $shop_names    = array();
        if($sel_shop_ids)
        {
            $shopList  = $shopObj->getList('name', array('shop_id'=>$sel_shop_ids));
            foreach ($shopList as $key => $val)
            {
                $shop_names[]    = $val['name'];
            }
        }
        
        return implode(',', $shop_names);
    }
    
    var $column_is_merge = '同入库仓合并';
    var $column_is_merge_width = 120;
    var $column_is_merge_order = 10;
    function column_is_merge($row)
    {
        if($row[$this->col_prefix .'is_merge'])
        {
            return '启用';
        }
        
        return '停用';
    }
    
    var $column_is_auto_combine = '是否自动审核';
    var $column_is_auto_combine_width = 130;
    var $column_is_auto_combine_order = 20;
    function column_is_auto_combine($row)
    {
        if($row[$this->col_prefix .'is_auto_combine'])
        {
            return '启用';
        }
        
        return '停用';
    }
    
    var $column_carrier_code = '承运商';
    var $column_carrier_code_width = 150;
    var $column_carrier_code_order = 30;
    function column_carrier_code($row)
    {
        if($row[$this->col_prefix .'carrier_code'])
        {
            $stockLib    = kernel::single('purchase_purchase_stockout');
            $carrier_code    = $stockLib->getCarrierCode('', $row[$this->col_prefix .'carrier_code']);
            
            return $carrier_code;
        }
        
        return '';
    }
    
    var $column_branch_id = '出库仓';
    var $column_branch_id_width = 150;
    var $column_branch_id_order = 30;
    function column_branch_id($row)
    {
        $purchaseLib    = kernel::single('purchase_purchase_order');
        $branch_list    = $purchaseLib->get_branch_list();
        
        if($row[$this->col_prefix .'branch_id'])
        {
            foreach ($branch_list as $key => $val)
            {
                if($val['branch_id'] == $row[$this->col_prefix .'branch_id'])
                {
                    return $val['name'];
                }
            }
        }
    
        return '';
    }
    
    var $column_dly_mode = '配送方式';
    var $column_dly_mode_width = 150;
    var $column_dly_mode_order = 30;
    function column_dly_mode($row)
    {
        $stockLib    = kernel::single('purchase_purchase_stockout');
        $dly_mode        = $stockLib->getDlyMode();//配送方式
        
        if($row[$this->col_prefix .'dly_mode'])
        {
            return $dly_mode[$row[$this->col_prefix .'dly_mode']];
        }
    
        return '';
    }
    
    var $column_edit  = '操作';
    var $column_edit_order = 2;
    var $column_edit_width = '100';
    function column_edit($row)
    {
        $finder_id   = $_GET['_finder']['finder_id'];
        $sid         = $row['sid'];
        
        $button = "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=console&ctl=admin_vopsetting&act=edit&p[0]={$sid}&finder_id={$_GET['_finder']['finder_id']}',{width:700,height:680,title:'编辑JIT审核配置'}); \">编辑</a>";
        
        return '<span class="c-gray">'. $button .'</span>';
    }
}