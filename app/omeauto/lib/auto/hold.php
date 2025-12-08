<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/10/30 17:47:14
 * @describe: 类
 * ============================
 */
class omeauto_auto_hold {
    private $maxHoldTime = 4294967295;

    /**
     * 获取MaxHoldTime
     * @return mixed 返回结果
     */

    public function getMaxHoldTime() {
        return $this->maxHoldTime;
    }

    /**
     * 处理
     * @param mixed $orderId ID
     * @return mixed 返回值
     */
    public function process($orderId)
    {
        $group = $this->_instanceItemObject($orderId);
        if(!$group) {
            return;
        }
        $orderFilters = $this->initFilters();
        if(!$orderFilters) {
            return;
        }
        foreach ($orderFilters as $filter) {
            if ($filter->vaild($group)) {
                $config = $filter->getConfig();
                $holdTime = $config['hours'] > 0 ? (time()+$config['hours']*3600) : $this->maxHoldTime;
                $upFilter = array('order_id' => $orderId);
                if($config['hold'] == 'part') {
                    $roleConfig = unserialize($config['config']);
                    $skuRoleConfig = array();
                    foreach ($roleConfig as $v) {
                        $role = json_decode($v, 1);
                        if($role['role'] == 'sku') {
                            foreach (explode(',', $role['content']['sku']) as $bn) {
                                $skuRoleConfig[trim($bn)] = trim($bn);
                            }
                        }
                    }
                    if(empty($skuRoleConfig)) {
                        continue;
                    }
                    $upFilter['bn'] = $skuRoleConfig;
                }
                app::get('ome')->model('order_objects')->update(array('estimate_con_time'=>$holdTime),$upFilter);
                $msg = 'hold单成功，hold单名称:'.$config['name'].'('.$config['tid'].'),hold单时限：'.($holdTime == $this->maxHoldTime ? '手工操作' : date('Y-m-d H:i:s', $holdTime)) . ($skuRoleConfig ? ',hold单商品：' . implode(',', $skuRoleConfig) : '');
                app::get('ome')->model('operation_log')->write_log('order_modify@ome',$orderId,$msg);
                break;
            }
        }
    }

    /**
     * 检查涉及仓库选择的订单分组对像是否已经存在
     * 
     * @param void
     * @return void
     */
    private function initFilters() {
        $orderGroups = array();
        $filters = kernel::single('omeauto_auto_type')->getAutoHoldTypes();
        if ($filters) {
            foreach ($filters as $config) {
                $filter = new omeauto_auto_group();
                $filter->setConfig($config);
                $orderGroups[] = $filter;
            }
        }
        return $orderGroups;
    }

    /**
     * 生成订单结构
     * 
     * @param Array $orderId
     * @retun void
     */
    private function _instanceItemObject($orderId) {

        $rows = app::get('ome')->model('orders')->getList('*', array('order_id' => $orderId,'process_status'=>array('unconfirmed','is_retrial')));

        if (!$rows) return;
        $orders = array();
        foreach ($rows as $order) {
                $orders[$order['order_id']] = $order;
        }
        $ids = array_keys($orders);

        $objects = app::get('ome')->model('order_objects')->getList('*', array('order_id' => $ids));
        foreach ($objects as $object) {
            $orders[$object['order_id']]['objects'][$object['obj_id']] = $object;
        }

        $items = app::get('ome')->model('order_items')->getList('*', array('order_id' => $ids, 'delete' => 'false'));
        foreach ($items as $item) {
            if($orders[$item['order_id']]['objects'][$item['obj_id']]) {
                $orders[$item['order_id']]['objects'][$item['obj_id']]['items'][$item['item_id']] = $item;
            }
        }

        #过滤掉没有明细的订单
        foreach ($orders as $order_id => $order)
        {
            if($order['objects']) {
                foreach($order['objects'] as $ik => $item){
                    if (empty($item['items'])){
                        unset($orders[$order_id]['objects'][$ik]);
                    }
                }
            }
            if (empty($orders[$order_id]['objects'])) {
                unset($orders[$order_id]);
            }
        }

        if(empty($orders))
        {
            return ;
        }
        return new omeauto_auto_group_item($orders);
    }
}