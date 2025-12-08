<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_order_branch {

    /**
     * 获取BranchIdByOrder
     * @param mixed $order_id ID
     * @return mixed 返回结果
     */
    public function getBranchIdByOrder($order_id) {
        $deliveryMdl = app::get('ome')->model('delivery');
        $dly = $deliveryMdl->getDeliversByOrderId($order_id);
        if($dly) {
            return array_column($dly, 'branch_id');
        }
        $orderIds = [$order_id];

        $combineObj = kernel::single('omeauto_auto_combine');
        $branchPlugObj = new omeauto_auto_plugin_branch();

        $groups = [];
        $groups[] = array('idx' => '1', 'hash' => '1', 'orders' => $orderIds);

        $itemObjects = $combineObj->getItemObject($groups);
        foreach ($itemObjects as $key => $item) {
            $orders = $item->getOrders();
            foreach ($orders as $order => $orderVal) {
                foreach ($orderVal['objects'] as $object => $objVal)
                {
                    if ($objVal['store_code']) {
                        $storeCode = $objVal['store_code'];
                        $branch = kernel::database()->selectrow("SELECT branch_id,name FROM sdb_ome_branch WHERE branch_bn='".$storeCode."'");
                        return [$branch['branch_id']];
                    }
                }
            }
            $item = new omeauto_auto_group_item($orders);
            
            $branchPlugObj->process($item);
            return $item->getBranchId();
        }
        return [];
    }

    /**
     * preSelect
     * @param mixed $order_id ID
     * @return mixed 返回值
     */
    public function preSelect($order_id) {
        $order = app::get('ome')->model('orders')->db_dump($order_id, 'pay_status,status,order_type,is_cod,order_id,shop_type');
        $isAuto = $order['status'] == 'active' 
                && in_array($order['order_type'], kernel::single('ome_order_func')->get_normal_order_type()); 
        if(!$isAuto){
            return false;
        }
        $cfg_pre    = app::get('ome')->getConf('ome.order.pre_sel_branch');
        if($cfg_pre != 'true') {
            return false;
        }
        //获取system账号信息
        $opinfo = kernel::single('ome_func')->get_system();
        
        //自动审单_批量日志
        $blObj  = app::get('ome')->model('batch_log');
        
        $bldata = array(
                'op_id' => $opinfo['op_id'],
                'op_name' => $opinfo['op_name'],
                'createtime' => time(),
                'batch_number' => 1,
                'log_type'=> 'pre_select_branch',
                'log_text'=> serialize([$order_id])
        );
        $result = $blObj->save($bldata);
        
        //自动审单_任务队列(改成多队列多进程)
        if (defined('SAAS_COMBINE_MQ') && SAAS_COMBINE_MQ == 'true') {
            $data = array();
            $data['spider_data']['url'] = kernel::openapi_url('openapi.autotask','service');

            $push_params = array(
                'log_text'  => $bldata['log_text'],
                'log_id'    => $bldata['log_id'],
                'task_type' => 'pre_select_branch',
            );
            $push_params['taskmgr_sign'] = taskmgr_rpc_sign::gen_sign($push_params);
            foreach ($push_params as $key => $val) {
                $postAttr[] = $key . '=' . urlencode($val);
            }

            $data['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
            $data['relation']['to_node_id']   = base_shopnode::node_id('ome');
            $data['relation']['from_node_id'] = '0';
            $data['relation']['tid']          = $bldata['log_id'];
            $data['relation']['to_url']       = $data['spider_data']['url'];
            $data['relation']['time']         = time();

            $routerKey = 'tg.order.combine.'.$data['nodeId'];

            $message = json_encode($data);
            $mq = kernel::single('base_queue_mq');
            $mq->connect($GLOBALS['_MQ_COMBINE_CONFIG'], 'TG_COMBINE_EXCHANGE', 'TG_COMBINE_QUEUE');
            $mq->publish($message, $routerKey);
            $mq->disConnect();
        } else {
            $push_params = array(
                'data' => array(
                        'log_text'  => $bldata['log_text'],
                        'log_id'    => $bldata['log_id'],
                        'task_type' => 'pre_select_branch',
                ),
                'url' => kernel::openapi_url('openapi.autotask','service')
            );
            
            kernel::single('taskmgr_interface_connecter')->push($push_params);  
        }

        return true;
    }
}