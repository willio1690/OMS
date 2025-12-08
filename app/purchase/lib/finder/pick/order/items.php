<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_finder_pick_order_items
{
    private $_appName = 'purchase';
    
    public $addon_cols = 'item_id,stat';
    
    var $detail_basic = '基本信息';
    /**
     * detail_basic
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_basic($id)
    {
        $render = app::get($this->_appName)->render();
        $orderMdl = app::get($this->_appName)->model('pick_order_items');
        
        $statusList = array(
            'none' => '未处理',
            'running' => '处理中',
            'succ' => '已完成',
            'fail' => '处理失败',
        );
        
        //order
        $itemInfo = $orderMdl->dump(array('item_id'=>$id), '*');
        
        $itemInfo['status_value'] = $statusList[$itemInfo['status']];
        
        $render->pagedata['itemInfo'] = $itemInfo;
        
        return $render->fetch('admin/pick/order_item_detail.html');
    }
    
    var $column_stat = '订单状态';
    public $column_stat_width = 90;
    public $column_stat_order = 32;
    /**
     * column_stat
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_stat($row)
    {
        $orderMdl = app::get($this->_appName)->model('pick_order_items');
        
        //list
        $statList = $orderMdl::$order_stat;
        
        //stat
        $stat = $row[$this->col_prefix.'stat'];
        
        return $statList[$stat]['name'];
    }
}
?>