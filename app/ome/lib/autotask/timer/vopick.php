<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_timer_vopick
{
    /* 当前的执行时间 */
    public static $now;
    
    /* 执行的间隔时间 */
    const intervalTime = 1800;
    
    function __construct()
    {
        self::$now = time();
    }
    
    /**
     * 创建拣货单
     */
    public function process($params, &$error_msg='')
    {
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        ignore_user_abort(1);
        
        //拣货单任务
        //@todo：只是把代码搬至新function方法中执行，未修改代码逻辑；
        $result = $this->disposePickTask($params, $error_msg);
        
        //通过拣货单号获取JIT订单明细任务
        $result = $this->jitOrderDetailTask($params, $error_msg);
        
        //处理拣货单号获取JIT订单明细任务
        $result = $this->disposeJitOrderDetailTask($params, $error_msg);
        
        return true;
    }
    
    /**
     * 拣货单任务
     * @todo：只是把代码搬至此方法中执行，未修改代码逻辑；
     *
     * @param $params
     * @param $error_msg
     * @return bool
     */
    public function disposePickTask($params, &$error_msg='')
    {
        base_kvstore::instance('console/vop/pick')->fetch('apply-nextexectime',$lastExecTime);
        
        $lastExecTime = $lastExecTime ? $lastExecTime : strtotime(date('Y-m-d H:00'));
        
        if($lastExecTime && $lastExecTime>self::$now) {
            return false;
        }
        
        base_kvstore::instance('console/vop/pick')->store('apply-nextexectime', $lastExecTime+self::intervalTime);
        
        $shopObj       = app::get('ome')->model('shop');
        $purchaseObj   = app::get('purchase')->model('order');
        $pickObj       = app::get('purchase')->model('pick_bills');
        $pickLib       = kernel::single('purchase_purchase_pick');
        $setObj        = app::get('purchase')->model('setting');
        
        //执行点
        $hour = date('H', $lastExecTime);
        $min  = date('i', $lastExecTime);
        
        //有效的配置
        $tempData    = $setObj->getList('*', array());
        if(empty($tempData)){
            return false;
        }
        
        $sids    = array();
        foreach ($tempData as $key => $val)
        {
            //已开启自动审核并且设置了出库仓
            if($val['is_auto_combine'] && $val['branch_id']){
                $exec_hour    = explode(',', $val['exec_hour']);
                
                if(in_array($hour, $exec_hour) && $min=='00'){
                    $sids[]    = $val['sid'];
                }
            }
        }
        
        if(empty($sids)){
            return false;//没有配置匹配到当前的审核时间段
        }
    
        //获取配置的店铺列表
        $sql       = "SELECT * FROM sdb_purchase_setting_shop WHERE sid IN(". implode(',', $sids) .") GROUP BY shop_id";
        $tempData  = $shopObj->db->select($sql);
        
        $shop_ids    = array();
        if($tempData){
            foreach ($tempData as $key => $val)
            {
                $shop_ids[]    = $val['shop_id'];
            }
        }
        
        if(empty($shop_ids)){
            return false;
        }
        
        //按店铺执行
        foreach ($shop_ids as $shop_id)
        {
            $shop = app::get('ome')->model('shop')->db_dump($shop_id, 'config');
            $config = @unserialize($shop['config']);
            if (!$config || $config['download_jit_auto'] != 'yes') {
                continue;
            }
            
            $sql = "SELECT po_id, po_bn, shop_id FROM sdb_purchase_order WHERE shop_id='". $shop_id ."' AND unpick_num>0 AND sell_et_time>". time();
            $poList = $purchaseObj->db->select($sql);
            if($poList)
            {
                foreach ($poList as $key => $val)
                {
                    kernel::single('vop_purchase_pick')->createPick($val['po_bn'], $val['shop_id']);
                }
            }
            
            //自动生成出库单
            $result    = $pickLib->auto_batch_create_stockout($shop_id);
        }
        
        //自动审核出库单
        $stockoutObj    = app::get('purchase')->model('pick_stockout_bills');
        $autoStockLib   = kernel::single('purchase_purchase_auto_stockout');
        
        $stockList      = $stockoutObj->getList('stockout_id', array('status'=>1, 'confirm_status'=>1, 'o_status'=>1), 0, -1);
        foreach ($stockList as $key => $val)
        {
            //初始化信息
            $error_msg    = '';
            $result       = $autoStockLib->_initStockoutIfo($val['stockout_id'], $error_msg);
            if(!$result){
                continue;
            }
            
            //审核
            $result       = $autoStockLib->combine($error_msg);
        }
        
        return true;
    }
    
    /**
     * 通过拣货单号获取JIT订单明细任务
     *
     * @param $params
     * @param $error_msg
     * @return bool
     */
    public function jitOrderDetailTask($params, &$error_msg='')
    {
        $pickObj = app::get('purchase')->model('pick_bills');
        $jitOrderLib = kernel::single('console_inventory_orders');
        
        //filter
        $filter = array('pull_status'=>'none');
        
        //count
        $count = $pickObj->count($filter);
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
            $pickList = $pickObj->getList('bill_id,pick_no,po_bn,shop_id', $filter, 0, $page_size);
            if(empty($pickList)){
                continue;
            }
            
            //拉取JIT订单明细
            foreach ($pickList as $pickKey => $pickInfo)
            {
                $jitOrderLib->getJitorderdetail($pickInfo);
            }
        }
        
        return true;
    }
    
    /**
     * 处理拣货订单明细任务
     *
     * @param $params
     * @param $error_msg
     * @return bool
     */
    public function disposeJitOrderDetailTask($params, &$error_msg='')
    {
        $pickOrderItemMdl = app::get('purchase')->model('pick_order_items');
        
        $jitOrderLib = kernel::single('console_inventory_orders');
        
        //filter
        $filter = array('status'=>array('none'));
        
        //count
        $count = $pickOrderItemMdl->count($filter);
        if($count <= 0){
            $error_msg = '没有可执行的拣货订单数据';
            return false;
        }
        
        //page
        $page_size = 100;
        $pageNum = ceil($count / $page_size);
        
        //exec
        for($page=1; $page<=$pageNum; $page++)
        {
            $itemList = $pickOrderItemMdl->getList('item_id,order_sn,status', $filter, 0, $page_size);
            if(empty($itemList)){
                continue;
            }
            
            //id
            $ids = array_column($itemList, 'item_id');
            
            //exec
            $paramsFilter = array('item_id'=>$ids);
            $result = $jitOrderLib->disposePickOrderItems($paramsFilter);
        }
        
        return true;
    }
}
