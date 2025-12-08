<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_timer_vopickinventory
{
    /* 当前的执行时间 */
    public static $now;
    
    /* 执行的间隔时间 */
    const intervalTime = 120; //间隔两分钟
    
    protected $_inventoryOrderMdl = null;
    protected $_invOrderItemMdl = null;
    protected $_jitOrderLib = null;
    
    protected $_shopList = array();
    
    public function __construct()
    {
        self::$now = time();
        
        $this->_inventoryOrderMdl = app::get('console')->model('inventory_orders');
        $this->_invOrderItemMdl = app::get('console')->model('inventory_order_items');
        
        $this->_jitOrderLib = kernel::single('console_inventory_orders');
    }
    
    /**
     * 实时销售订单查询
     */
    public function process($params, &$error_msg = '')
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '128M');
        ignore_user_abort(1);
        
        base_kvstore::instance('console/vop/purchase')->fetch('pick-inventory-ordertime', $lastExecTime);
        
        //最后一次执行时间戳
        $lastExecTime = $lastExecTime ? $lastExecTime : strtotime(date('Y-m-d H:00:00'));
        
        //check time
        $checkExecTime = $lastExecTime + self::intervalTime;
        if($checkExecTime && $checkExecTime > self::$now) {
            return false;
        }
        
        //cache time
        base_kvstore::instance('console/vop/purchase')->store('pick-inventory-ordertime', self::$now);
        
        //开始时间 = 上次执行的时间 - 5分钟
        $start_time = $lastExecTime - 300;
        
        //shop
        $shopObj = app::get('ome')->model('shop');
        $sql = "SELECT shop_id,shop_bn,name,shop_type,node_id FROM sdb_ome_shop WHERE shop_type ='vop' and node_id is not null";
        $shopList = $shopObj->db->select($sql);
        if(empty($shopList)) {
            $error_msg = 'vop shop empty!';
            return false;
        }
        
        $this->_shopList = $shopList;
        
        //exec
        foreach ($shopList as $shopKey => $shopInfo)
        {
            $shop_id = $shopInfo['shop_id'];
            
            //params
            $params = array(
                'shop_id' => $shop_id,
                'start_time' => $start_time,
                'end_time' => time(),
                'page' => 1,
                'page_size'  => 100,
            );
            
            //while
            do {
                $has_next = false;
                
                //reuqest
                $result = $this->_jitOrderLib->getInventoryOccupiedOrders($params);
                if($result['rsp'] != 'succ'){
                    $error_msg = $result['error_msg'];
                    
                    break;
                }
                
                //check
                if(empty($result['data'])) {
                    break;
                }
                
                //是否继续拉取下一页
                if(isset($result['data']) && $result['data']){
                    if($result['data']['has_next']){
                        $params['page'] += 1;
                        
                        $has_next = true;
                    }
                }
            } while ($has_next);
        }
        
        //核销处理订单
        $this->disposeOrders($params, $error_msg);
        
        //处理唯品会平台成交后已取消订单列表
        $result = $this->disposeInventoryCancelledOrders($params, $error_msg);
        
        return true;
    }
    
    /**
     * 核销处理订单
     *
     * @param $params
     * @param $error_msg
     * @return bool
     */
    public function disposeOrders($params=array(), &$error_msg='')
    {
        //filter
        $filter = array('dispose_status'=>array('none'));
        
        //count
        $count = $this->_inventoryOrderMdl->count($filter);
        if($count <= 0){
            $error_msg = '没有可执行的数据';
            return false;
        }
        
        //page
        $page_size = 100;
        $pageNum = ceil($count / $page_size);
        
        //exec
        for($page=1; $page<=$pageNum; $page++)
        {
            $orderList = $this->_inventoryOrderMdl->getList('id,order_sn,dispose_status', $filter, 0, $page_size);
            if(empty($orderList)){
                continue;
            }
            
            //id
            $ids = array_column($orderList, 'id');
            
            //exec
            $paramsFilter = array('id'=>$ids);
            $result = $this->_jitOrderLib->disposeInventoryOrders($paramsFilter);
        }
        
        return true;
    }
    
    /**
     * 处理唯品会平台成交后已取消订单列表
     *
     * @param $params
     * @param $error_msg
     * @return bool
     */
    public function disposeInventoryCancelledOrders($params=array(), &$error_msg='')
    {
        //获取最后一次执行的时间戳
        base_kvstore::instance('console/vop/purchase')->fetch('inventory-canncelorder-ordertime', $lastExecTime);
        
        //check time
        $lastExecTime = $lastExecTime ? $lastExecTime : strtotime(date('Y-m-d H:00:00'));
        if($lastExecTime && $lastExecTime > self::$now) {
            return false;
        }
        
        //保存最后执行的时间戳
        base_kvstore::instance('console/vop/purchase')->store('inventory-canncelorder-ordertime', self::$now);
        
        //开始时间 = 上次执行的时间 - 10分钟
        $start_time = $lastExecTime - 600;
        
        //shop
        $shopList = $this->_shopList;
        if(empty($shopList)){
            //shop
            $shopObj = app::get('ome')->model('shop');
            $sql = "SELECT shop_id,shop_bn,name,shop_type,node_id FROM sdb_ome_shop WHERE shop_type ='vop' and node_id is not null";
            $shopList = $shopObj->db->select($sql);
            if(empty($shopList)) {
                $error_msg = 'vop shop empty;';
                return false;
            }
        }
        
        //exec
        foreach ($shopList as $shopKey => $shopInfo)
        {
            $shop_id = $shopInfo['shop_id'];
            
            //params
            $params = array(
                'shop_id' => $shop_id,
                'start_time' => $start_time,
                'end_time' => time(),
                'page' => 1,
                'page_size'  => 100,
            );
            
            //while
            do {
                $has_next = false;
                
                //reuqest
                $result = $this->_jitOrderLib->getInventoryCancelledOrders($params);
                if($result['rsp'] != 'succ'){
                    $error_msg = $result['error_msg'];
                    
                    break;
                }
                
                //check
                if(empty($result['data'])) {
                    break;
                }
                
                //是否继续拉取下一页
                if(isset($result['data']) && $result['data']){
                    if($result['data']['has_next']){
                        $params['page'] += 1;
                        
                        $has_next = true;
                    }
                }
            } while ($has_next);
        }
        
        return true;
    }
}
