<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * OMS发货单推送给翱象系统finder
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 2023.03.04
 */
class dchain_finder_aoxiang_delivery
{
    public $addon_cols = '';
    
    var $_url = 'index.php?app=dchain&ctl=admin_aoxiang_delivery';
    
    public $column_edit = '操作';
    public $column_edit_width = 130;
    public $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $shop_id = trim($_GET['shop_id']);
        $view = intval($_GET['view']);
        $id = $row['did'];
        $button = '';
        
        //edit
        if($shop_id && !in_array($row['sync_status'], array('succ'))){
            $button = '<a href="'. $this->_url .'&act=single_sync&id='. $id .'&view='. $view .'&shop_id='. $shop_id .'&finder_id='. $finder_id .'">同步</a>';
        }
        
        return $button;
    }
}
