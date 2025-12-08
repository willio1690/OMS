<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/4 17:42:03
 * @describe: 复审订单处理
 * ============================
 */
class ome_order_retrial {

    #检查价格异常
    /**
     * 检查MonitorAbnormal
     * @param mixed $order order
     * @return mixed 返回验证结果
     */

    public function checkMonitorAbnormal($order)
    {
        if($order['is_fail'] == 'true') {
            return array(false, '失败订单不参与监控');//跳出
        }
        #价格监控[获取订单复审配置]
        $setting_is_monitor = app::get('ome')->getConf('ome.order.is_monitor');//是否开启价格监控
        $cost_multiple      = app::get('ome')->getConf('ome.order.cost_multiple');//成本价倍数
        $sales_multiple     = app::get('ome')->getConf('ome.order.sales_multiple');//销售价倍数
        #未开启监控
        if($setting_is_monitor != 'true')
        {
            return array(false, '未开启监控');//跳出
        }
        if($cost_multiple['flag'] && floatval($cost_multiple['value'])) {
            #基础物料价格监控
            $orderItems = array();
            $bmIds = array();
            foreach ($order['order_objects'] as $object) {
                foreach ($object['order_items'] as $item) {
                    $orderItems[$item['item_id']] = $item;
                    $bmIds[$item['product_id']] = $item['product_id'];
                }
            }
            $bmRows = app::get('material')->model('basic_material_ext')->getList('bm_id,cost', array('bm_id'=>$bmIds));
            $bmIdCost = array();
            foreach ($bmRows as $v) {
                $bmIdCost[$v['bm_id']] = $v['cost'];
            }
            list($rs, $msg) = $this->materialMonitor($orderItems, $bmIdCost, floatval($cost_multiple['value']),$order['total_amount']);
            if($rs) {
                return array(true, '基础' . $msg);
            }
        }
        if($sales_multiple['flag'] && floatval($sales_multiple['value'])) {
            #销售物料价格监控
            $orderObjets = array();
            $smIds = array();
            foreach ($order['order_objects'] as $object) {
                $orderObjets[$object['obj_id']] = $object;
                $smIds[$object['goods_id']] = $object['goods_id'];
            }
            $smRows = app::get('material')->model('sales_material_ext')->getList('sm_id,lowest_price', array('sm_id'=>$smIds));
            $smIdCost = array();
            foreach ($smRows as $v) {
                $smIdCost[$v['sm_id']] = $v['lowest_price'];
            }
            list($rs, $msg) = $this->materialMonitor($orderObjets, $smIdCost, floatval($cost_multiple['value']),$order['total_amount']);
            if($rs) {
                return array(true, '销售' . $msg);
            }
        }
        return array(false, '未检查到异常');
    }

    /**
     * materialMonitor
     * @param mixed $data 数据
     * @param mixed $bsmIdCost ID
     * @param mixed $multiple multiple
     * @param mixed $totalAmount totalAmount
     * @return mixed 返回值
     */
    public function materialMonitor($data, $bsmIdCost, $multiple, $totalAmount) {
        $orderOrGoods = app::get('ome')->getConf('ome.order.monitor.ordergoods');
        if($orderOrGoods == '1') {
            foreach ($data as $v) {
                $bsmId = $v['product_id'] ? : $v['goods_id'];
                if(!$bsmIdCost[$bsmId]) {
                    continue;
                }
                $num = $v['quantity'] ? : $v['nums'];
                $bmCost = bcmul($bsmIdCost[$bsmId], $num, 2);
                $bmCostMultiple = bcmul($bmCost, $multiple, 2);
                if(bccomp($v['divide_order_fee'], $bmCostMultiple, 2) < 0) {
                    return array(true, '物料:'.$v['bn'].'异常，实付：'.$v['divide_order_fee'].',成本/最低价：'.$bmCost.',倍数：'.$multiple);
                }
            }
        } else {
            $bmCostTotal = 0;
            $zeroBm = array();
            foreach ($data as $v) {
                $bsmId = $v['product_id'] ? : $v['goods_id'];
                if(!$bsmIdCost[$bsmId]) {
                    $zeroBm[] = $v['bn'];
                    continue;
                }
                $num = $v['quantity'] ? : $v['nums'];
                $bmCost = bcmul($bsmIdCost[$bsmId], $num, 2);
                $bmCostTotal = bcadd($bmCost, $bmCostTotal, 2);
            }
            $bmCostTotalMultiple = bcmul($bmCostTotal, $multiple, 2);
            if(bccomp($totalAmount, $bmCostTotalMultiple, 2) < 0) {
                return array(true, '物料订单总额异常，总额：'.$totalAmount.',成本/最低价：'.$bmCostTotal.',倍数：'.$multiple.($zeroBm ? '，未设置：'.implode(',', $zeroBm) : ''));
            }
        }
        return array(false, '物料价格监控没有异常');
    }

    #价格监控异常
    /**
     * monitorAbnormal
     * @param mixed $orderId ID
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function monitorAbnormal($orderId, $msg) {
        $retrial_msg       = '系统监控到订单销售价格有异常，自动进入价格复审:'.$msg;
        $oOperation_log    = app::get('ome')->model('operation_log');
        
        //改变订单为异常
        $oOrder        = app::get('ome')->model('orders');
        $update_order  = array();
        
        $update_order['order_id']          = $orderId;
        $update_order['process_status']    = 'is_retrial';//复审状态
        $update_order['abnormal']          = 'true';//异常
        $update_order['pause']             = 'true';//订单暂停
        
        $oOrder->save($update_order);
        
        # [设置]为订单异常
        $abnormal_data  = array();
        $abnormal_data['order_id']         = $orderId;
        $abnormal_data['op_id']            = 0;
        $abnormal_data['group_id']         = 0;
        $abnormal_data['abnormal_type_id'] = 9;//订单异常类型
        $abnormal_data['is_done']          = 'false';
        $abnormal_data['abnormal_memo']    = $retrial_msg;
        
        $oOrder->set_abnormal($abnormal_data);

        # [增加]价格复审
        $oRetrial      = app::get('ome')->model('order_retrial');
        $retrial_arr   = array();
        $order = $oOrder->db_dump($orderId, 'order_bn');
        $retrial_arr['order_id']       = $orderId;
        $retrial_arr['order_bn']       = $order['order_bn'];
        $retrial_arr['retrial_type']   = 'audit';//价格复审类型
        $retrial_arr['status']         = 0;//待审核
        $retrial_arr['kefu_remarks']   = $retrial_msg;
        $retrial_arr['dateline']       = time();
        $retrial_arr['lastdate']       = time();
        
        $op_id     = kernel::single('desktop_user')->get_id();
        $retrial_arr['op_id']          = intval($op_id);//操作员

        $retrial_id = $oRetrial->insert($retrial_arr);
        
        //写日志
        $oOperation_log->write_log('order_retrial@ome', $orderId, $retrial_msg);
    }
}