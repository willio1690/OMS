<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_order_timing {

    /**
     * confirm
     * @param mixed $arrOrderId ID
     * @return mixed 返回值
     */
    public function confirm($arrOrderId) {
        $combine_select = app::get('ome')->getConf('ome.combine.select');
        $orders = app::get('ome')->model('orders')->getList('order_id, order_combine_hash, order_combine_idx, pay_status, is_cod, createtime, paytime, shop_id, order_bn', array('order_id' => $arrOrderId), 0, -1, 'createtime ASC');
        foreach ($orders as $key => $row) {
            if($combine_select== '1') {
                list($row['order_combine_hash'],$row['order_combine_idx']) = array(md5($row['order_bn'].'-'.$row['shop_id']),sprintf("%u",crc32($row['order_bn'].'-'.$row['shop_id'])));
            }
            $idx = sprintf('%s||%s', $row['order_combine_hash'], $row['order_combine_idx']);
            $orderGroup[$idx]['orders'][$key] = $row['order_id'];
            $orderGroup[$idx]['cnt'] += 1;
        }
        //合并订单条数限制
        $orderAuto = new omeauto_auto_combine();
        $orderGroup = $orderAuto->_restrictCombineLimit($orderGroup);

        $params = array();
        $allNum = 0;
        foreach($orderGroup as $k => $val) {
            list($hash, $idx) = explode('||', $k);
            $params[] = array(
                'idx' => $idx,
                'hash' => $hash,
                'orders' => $val['orders'],
            );
            $allNum += $val['cnt'];
        }
        $batchLogModel = app::get('ome')->model('batch_log');
        $op = kernel::single('ome_func')->getDesktopUser();
        $batchLog = array(
            'createtime'   => time(),
            'op_id'        => $op['op_id'],
            'op_name'      => $op['op_name'],
            'batch_number' => $allNum,
            'succ_number'  => '0',
            'fail_number'  => '0',
            'status'       => '0',
            'log_type'     => 'ordertaking',
            'log_text'     => serialize($params),
            'source'       => 'task'
        );
        $batchLogModel->save($batchLog);
        if (defined('SAAS_COMBINE_MQ') && SAAS_COMBINE_MQ == 'true') {
            foreach (array_chunk($params, 5) as $param) {
                $push_params = array(
                    'orderidx'  => json_encode($param),
                    'task_type' => 'ordertaking',
                    'log_id'    => $batchLog['log_id'],
                    'uniqid'    => 'combine_'.$batchLog['log_id'],
                );
                taskmgr_func::multiQueue($GLOBALS['_MQ_COMBINE_CONFIG'], 'TG_COMBINE_EXCHANGE', 'TG_COMBINE_QUEUE','tg.order.combine.*',$push_params);
            }
        } else {
           foreach (array_chunk($params, 5) as $param) {
               $push_params = array(
                   'data' => array(
                       'orderidx'  => json_encode($param),
                       'log_id'    => $batchLog['log_id'],
                       'task_type' => 'ordertaking'
                   ),
                   'url' => kernel::openapi_url('openapi.autotask','service')
               );
               kernel::single('taskmgr_interface_connecter')->push($push_params);
           }

       }
    }
}