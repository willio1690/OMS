<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单同步处理
 * Class erpapi_shop_matrix_website_d1m_request_delivery
 */
class erpapi_shop_matrix_website_d1m_request_delivery extends erpapi_shop_request_delivery
{
    protected function getExpressOrgCode($code)
    {
        $ezrExpressOrgCode = $code;
        $list              = [
            'SF'        => 'shunfeng', //顺丰
        ];
        $list[$code] && $ezrExpressOrgCode = $list[$code];
        
        return $ezrExpressOrgCode;
    }
    
    protected function get_delivery_apiname($sdf)
    {
        return D1M_OPEN_DELIVERY_UPDATE_POST;
    }
    
    /**
     * confirm
     * @param mixed $sdf sdf
     * @param mixed $queue queue
     * @return mixed 返回值
     */

    public function confirm($sdf, $queue = false)
    {
        // 只处理已发货与部分发货状态
        if ($sdf['status'] != 'succ' && !in_array($sdf['orderinfo']['ship_status'], array('1', '2'))) return $this->succ('发货单未发货');
        
        $args[0] = $sdf;
        $_in_mq  = $this->__caller->caller_into_mq('delivery_confirm', 'shop', $this->__channelObj->channel['shop_id'], $args, $queue);
        if ($_in_mq) {
            return $this->succ('成功放入队列');
        }
        
        $params = $this->get_confirm_params($sdf);
        
        // 发货记录
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $log_id = uniqid($_SERVER['HOSTNAME']);
        $log    = array(
            'shopId'           => $this->__channelObj->channel['shop_id'],
            'ownerId'          => $opInfo['op_id'],
            'orderBn'          => $sdf['orderinfo']['order_bn'],
            'deliveryCode'     => $params['logi_no'],
            'deliveryCropCode' => $params['logi_code'],
            'deliveryCropName' => $params['logi_name'],
            'receiveTime'      => time(),
            'status'           => 'send',
            'updateTime'       => '0',
            'oid_list'         => $sdf['oid_list'] ? implode(',', $sdf['oid_list']) : '',
            'message'          => '',
            'log_id'           => $log_id,
        );
        
        $shipmentLogModel = app::get('ome')->model('shipment_log');
        $shipmentLogModel->insert($log);
        
        // 更新订单状态
        $orderModel = app::get('ome')->model('orders');
        $orderModel->update(array('sync' => 'run'), array('order_id' => $sdf['orderinfo']['order_id']));
        
        // 整理参数格式
        $title = sprintf('发货状态回写[%s]-%s', $sdf['delivery_bn'], $sdf['orderinfo']['order_bn']);
        
        $paramsJson = [
            'json_data' => json_encode($params)
        ];
        
        $callback_params = array(
            'params' => array(
                'shipment_log_id' => $log_id,
                'order_id'        => $sdf['orderinfo']['order_id'],
                'logi_no'         => $params['logi_no'],
                'obj_bn'          => $sdf['orderinfo']['order_bn'],
                'company_code'    => $params['logi_code'],
            ),
        
        );
        
        //请求接口名
        $api_method = $this->get_delivery_apiname($sdf);
    
    
        $result = $this->__caller->call($api_method, $paramsJson, [], $title, 10, $sdf['orderinfo']['order_bn']);
        // token 异常,发起重试
        if ($result['rsp'] == 'fail' && in_array($result['err_msg'], $this->__resultObj->retryErrorMsgList())) {
            kernel::single('erpapi_router_request')->set('shop', $this->__channelObj->channel['shop_id'])->base_get_access_token();
            $result = $this->__caller->call($api_method, $paramsJson, [], $title, 10, $sdf['orderinfo']['order_bn']);
        }
        
        $this->confirm_callback($params, $callback_params['params'],$result);
        
        return $result;
    }
    
    protected function get_confirm_params($sdf)
    {
        // 物流发货单去BOM头
        $pattrn  = chr(239) . chr(187) . chr(191);
        $logi_no = trim(str_replace($pattrn, '', $sdf['logi_no']));
        
        $items = [];
        foreach ($sdf['delivery_items'] as $k => $v) {
            if ($v['shop_goods_id'] && $v['shop_goods_id'] != '-1') {
                $items[] = [
                    'product_bn'   => $v['bn'],
                    'product_name' => $v['name'],
                    'number'       => $v['number'],
                ];
            }
        }
        $param = array(
            't_confirm' => date('Y-m-d H:i:s', $sdf['delivery_time']),//发货时间
            'order_bn'  => $sdf['orderinfo']['order_bn'], // 订单号
            'date'      => date('Y-m-d H:i:s', time()),
//            'node_id'   => '',//店铺id
            'logi_no'   => strval($logi_no), // 运单号
            'logi_code' => trim($this->getExpressOrgCode($sdf['logi_type'])), // 物流编号
            'logi_name' => strval($sdf['logi_name']), // 物流公司
            'items'     => $items,
        );
        return $param;
    }
    
    /**
     * 发货回调
     *
     * @return void
     * @author
     **/
    public function confirm_callback($response, $callback_params,$result = [])
    {
        $rsp             = $result['rsp'];
        $err_msg         = $result['err_msg'];
        $order_id        = $callback_params['order_id'];
        $shipment_log_id = $callback_params['shipment_log_id'];
        
        $orderModel = app::get('ome')->model('orders');
        $order      = $orderModel->getList('sync,shop_type', array('order_id' => $order_id), 0, 1);
        
        // 已经回写成功，不需要再改
        if ($order[0]['sync'] == 'succ') {
            $rsp = 'succ';
        }
        
        $rsp == 'success' ? 'succ' : $rsp;
        $status         = 'succ';
        $sync_fail_type = 'none';
        $message        = '';
        // ERP没有发起成功且请求失败
        if ($rsp != 'succ') {
            $status = 'fail';
            
            // 错误信息
            $message = $err_msg;
        }
        
        // 更新订单状态
        if ($order_id) {
            $updateOrderData = array(
                'sync'           => $status,
                'up_time'        => time(),
                'sync_fail_type' => $sync_fail_type,
            );
            
            $orderModel->update($updateOrderData, array('order_id' => $order_id, 'sync|noequal' => 'succ'));
        }
        
        $shipmentModel = app::get('ome')->model('shipment_log');
        
        // 更新发货日志状态
        if ($shipment_log_id) {
            $updateShipmentData = array(
                'status'     => $status,
                'updateTime' => time(),
                'message'    => $message,
            );
            
            $shipmentModel->update($updateShipmentData, array('log_id' => $shipment_log_id));
        }
        
        return $this->callback($response, $callback_params);
    }
}