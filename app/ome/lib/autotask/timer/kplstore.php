<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_timer_kplstore
{
    public function process($params, &$error_msg='')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        
        //库存置0
        $result = $this->_processStore($error_msg);
        if(!$result){
            return false;
        }
        
        //撤销发货单
        $result = $this->_rebackDelivery($error_msg);
        if(!$result){
            return false;
        }
        
        return true;
    }

    /**
     * 检查开普勒商品库存
     * 
     * @return boolean
     */
    private function _processStore(&$error_msg='')
    {
        // 查询一件代发区域仓
        $sql = 'SELECT a.*, b.branch_id, b.wms_id, b.branch_bn, w.warn_num, c.node_id 
                FROM sdb_logisticsmanager_warehouse_address a 
                LEFT JOIN sdb_logisticsmanager_warehouse w ON(w.id=a.warehouse_id) 
                LEFT JOIN sdb_ome_branch b ON(w.branch_id = b.branch_id) 
                LEFT JOIN sdb_channel_channel c ON(c.channel_id = b.wms_id) 
                WHERE c.node_type = "yjdf" AND c.node_id IS NOT NULL AND c.node_id != ""';
        $list = kernel::database()->select($sql);
        if (!$list) {
            $error_msg = '没有一件代发区域仓';
            return false;
        }
        
        // 区域仓地址
        $areaWarehouses = [];
        foreach($list as $l)
        {
            $object = kernel::single('erpapi_router_request')->set('wms', $l['wms_id']);
            
            $platform_area = $object->branch_getAreaId([
                'ship_province' => $l['province'],
                'ship_city'     => $l['city'],
                'ship_district' => $l['street'],
                'ship_town'     => $l['town'],
                'ship_addr'     => $l['address'],
            ]);

            $l['provinceId'] = $platform_area['data']['provinceid'];
            $l['cityId']     = $platform_area['data']['cityid'];
            $l['townId']     = $platform_area['data']['streetid'];
            $l['countyId']   = $platform_area['data']['townid'];

            $areaWarehouses[$l['branch_id']][] = $l;
        }
        
        // 查询渠道所有在架及有库存商品
        $wms_ids = array_column($list, 'wms_id');
        $branch_ids = array_column($list, 'branch_id');
        
        $sql = 'SELECT fs.wms_id,fs.outer_sku,fs.inner_sku,bp.branch_id,bp.store,bp.product_id AS bm_id 
                FROM sdb_console_foreign_sku fs 
                LEFT JOIN sdb_ome_branch_product bp 
                ON(fs.inner_product_id = bp.product_id) 
                WHERE fs.wms_id IN(' . implode(',', $wms_ids) . ') AND bp.branch_id IN(' . implode(',', $branch_ids) . ')';
        
        $sql .= ' AND bp.store > 0';
        
        $deliveryList = app::get('ome')->model('delivery')->getList('delivery_id,branch_id,delivery_bn', [
            'status'               => ['ready', 'progress'],
            'parent_id'            => '0',
            'branch_id'            => $branch_ids,
            'sync_status'          => '2',
            'original_delivery_bn' => '0',
            'filter_sql'           => 'sync_msg regexp "商品无货"',
        ]);
        
        if (!$deliveryList) {
            $error_msg = '没有可操作的发货单';
            return false;
        }
        
        $delivery_ids = array_column($deliveryList, 'delivery_id');
        
        //发货单明细
        $itemList = app::get('ome')->model('delivery_items')->getList('product_id', ['delivery_id'=>$delivery_ids]);
        $sql .= ' AND bp.product_id IN(' . implode(',', array_column($itemList, 'product_id')) . ')';
        
        //list
        $list = kernel::database()->select($sql);
        if(!$list) {
            $error_msg = '没有基础物料分配商品';
            return false;
        }
        
        //京东云交易渠道只能绑定一个
        $kplChannel = app::get('wmsmgr')->model('channel')->getList('*', [
            'node_type' => 'yjdf',
        ]);
        if (count($kplChannel) != 1) {
            $error_msg = '京东云交易渠道只能绑定一个,现在是'. count($kplChannel) .'个';
            return false;
        }
        
        // 按渠道分类
        $skus_list = [];
        foreach ($list as $l)
        {
            $l['material_bn'] = $l['outer_sku'] ?: $l['inner_sku'];
            
            $skus_list[$l['branch_id']][$l['material_bn']] = $l;
        }
        
        $rt = false;
        $channel_id = $kplChannel[0]['channel_id'];
        foreach ($skus_list as $branch_id => $skus)
        {
            // 区域仓列表
            $addrs = $areaWarehouses[$branch_id];
            
            $invItems = [];
            foreach (array_chunk($skus, 10) as $v)
            {
                $params = [
                    'channel_id' => $channel_id,
                    'skus'       => $v,
                    'addrs'      => $addrs,
                ];
                
                //查询京东云交易商品的库存(指定SKU)
                $result = kernel::single('erpapi_router_request')->set('wms', $v[0]['wms_id'])->goods_selectStore($params);
                if ($result['data']) {
                    if ($result['data'][0]) {
                        // 库存追零
                        foreach ($result['data'][0] as $outer_sku)
                        {
                            if (in_array($outer_sku, $result['data'][1])) {
                                continue;
                            }
                            
                            $invItems[] = [
                                'product_bn' => $skus[$outer_sku]['inner_sku'],
                                'item_id'    => $outer_sku,
                                'mode'       => '1',
                                'normal_num' => -$skus[$outer_sku]['store'],
                            ];
                        }

                    }
                }
            }
            
            //同步OMS库存
            if($invItems){
                $inventory = array(
                    'inventory_bn' => uniqid('KEPLER'),
                    'warehouse'    => $addrs[0]['branch_bn'],
                    'memo'         => '同步开普勒库存',
                    'autoconfirm'  => 'Y',
                    'item'         => json_encode($invItems),
                );
                
                kernel::single('erpapi_router_response')->set_node_id($addrs[0]['node_id'])->set_api_name('wms.inventory.add')->dispatch($inventory);
                
                //标识
                $rt = true;
            }
        }
        
        return $rt;
    }
    
    /**
     * 撤销发货单，重新路由审核订单
     * 
     * @param string $error_msg
     * @return bool
     */
    private function _rebackDelivery(&$error_msg='')
    {
        // 开普勒仓储
        $channel = app::get('channel')->model('channel')->dump([
            'node_type'  => 'yjdf',
            'filter_sql' => 'node_id IS NOT NULL AND node_id != ""',
        ], 'channel_id');
        
        //check
        if (!$channel) {
            $error_msg = '没有京东云交易渠道';
            return false;
        }

        $branchList = app::get('ome')->model('branch')->getList('branch_id', [
            'wms_id' => $channel['channel_id'],
            'b_type' => '1',
        ]);

        if (!$branchList) {
            $error_msg = '没有京东云交易关联仓库';
            return false;
        }
        
        $deliveryList = app::get('ome')->model('delivery')->getList('delivery_id,branch_id,delivery_bn', [
            'status'               => ['ready', 'progress'],
            'parent_id'            => '0',
            'branch_id'            => array_column($branchList, 'branch_id'),
            'sync_status'          => '2',
            'original_delivery_bn' => '0',
            'filter_sql'           => 'sync_msg regexp "商品无货"',
        ]);

        if (!$deliveryList) {
            $error_msg = '没有无货的发货单';
            return false;
        }
        
        //list
        foreach ($deliveryList as $delivery)
        {
            //请求京东云交易取消发货单
            $res = ome_delivery_notice::cancel($delivery, true);
            if ($res['rsp'] == 'succ') {
                //OMS取消发货单
                $msg = '京东云交易商品无货,系统自动取消发货单';
                $data = [
                    'status'      => 'cancel',
                    'memo'        => $msg,
                    'delivery_bn' => $delivery['delivery_bn'],
                ];
                $res = kernel::single('ome_event_receive_delivery')->update($data);
                if ($res['rsp'] == 'succ') {
                    $deliveryOrderList = app::get('ome')->model('delivery_order')->getList('order_id', [
                        'delivery_id' => $delivery['delivery_id'],
                    ]);
                    
                    // 重新路由 TODO
                    $orderIds = array_column($deliveryOrderList, 'order_id');
                    if ($orderIds) {
                        // 订单恢复
                        foreach ($orderIds as $order_id)
                        {
                            app::get('ome')->model('orders')->renewOrder($order_id);
                        }
                        
                        //是否要把sdb_ome_objects表中,指定仓库编码store_code字段更新为空
                        
                        //放入队列,重新审核订单
                        kernel::single('ome_batch_log')->split($orderIds);
                    }
                }
            }
        }
        
        return true;
    }
}
