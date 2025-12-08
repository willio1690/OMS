<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 * Class erpapi_shop_matrix_zkh_request_delivery
 */
class erpapi_shop_matrix_zkh_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_delivery_apiname($type)
    {
        $api_method = '';
        switch ($type) {
            case 'ack':
                $api_method = ZKH_OPEN_DELIVERY_CONFIRM_POST;//采购单发货确认
                break;
            case 'delivery':
                $api_method = ZKH_OPEN_GET_DELIVERY_POST;//发货单确认详情查询
                break;
            case 'print':
                $api_method = ZKH_OPEN_GET_DELIVERY_DETAIL;//供应商获取送货单详情 pdf
                break;
        }
        return $api_method;
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
        
        //发货单确认详情查询
        $resPart = $this->getDeliveryPart($sdf);
        if ($resPart['rsp'] != 'succ') {
            return $resPart;
        }
        
        //物流回写组参数
        $params = $this->get_ack_params($sdf, $resPart);
        $args[0] = $sdf;
        $_in_mq  = $this->__caller->caller_into_mq('delivery_confirm', 'shop', $this->__channelObj->channel['shop_id'], $args, $queue);
        if ($_in_mq) {
            return $this->succ('成功放入队列');
        }
        
        // 发货记录
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $log_id = uniqid($_SERVER['HOSTNAME']);
        $log    = array(
            'shopId'           => $this->__channelObj->channel['shop_id'],
            'ownerId'          => $opInfo['op_id'],
            'orderBn'          => $sdf['orderinfo']['order_bn'],
            'deliveryCode'     => $params['logisticsCode'],
            'deliveryCropCode' => $params['company_code'],
            'deliveryCropName' => $params['logisticsName'],
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
        $title = sprintf('采购单发货确认[%s]-%s', $sdf['delivery_bn'], $sdf['orderinfo']['order_bn']);
        
        $callback_params = array(
            'params' => array(
                'shipment_log_id' => $log_id,
                'order_id'        => $sdf['orderinfo']['order_id'],
                'logi_no'         => $params['logisticsCode'],
                'obj_bn'          => $sdf['orderinfo']['order_bn'],
                'company_code'    => $params['company_code'],
            ),
        
        );
    
        $deliveryMdl  = app::get('ome')->model('delivery');
        $deliveryInfo = $deliveryMdl->db_dump(['delivery_id' => $sdf['delivery_id']], 'delivery_order_number');
    
        //采购确认不能多次调用，如果已有送货单号 直接返回成功
        if (isset($deliveryInfo['delivery_order_number']) && !$deliveryInfo['delivery_order_number']) {
            //供应商采购单发货确认 V1  /openPoApi/v1/purchaseOrder/ackDeliveryOrderPart
            $api_method = $this->get_delivery_apiname('ack');
            $result     = $this->__caller->call($api_method, $params, [], $title, 10, $sdf['orderinfo']['order_bn']);
            //更新送货单号
            $data = json_decode($result['data'], true);
            $deliveryMdl->update(['delivery_order_number' => $data['data']['deliveryCode']], ['delivery_id' => $sdf['delivery_id']]);
        } else {
            $result = ['rsp' => 'succ', 'err_msg' => ''];
        }
        $this->confirm_callback($result, $callback_params['params']);
        
        return $result;
    }
    
    /**
     * 获取_ack_params
     * @param mixed $params 参数
     * @param mixed $result result
     * @return mixed 返回结果
     */
    public function get_ack_params($params, $result)
    {
        $data  = json_decode($result['data'], true);
        $lines = $data['data']['lines'];
        $lines = array_column($lines, null, 'zkhSku');
        
        $itemList = [];
        foreach ($params['delivery_items'] as $val) {
            if (!$val['oid']) {
                continue;//赠品明细，不需要回传
            }
            $item       = [
                'purchaseOrderId'  => $params['orderinfo']['order_bn'],
                'planItemNumber'   => $lines[$val['shop_goods_id']]['planItemNumber'],//计划行号
                'itemLineNumber'   => $val['oid'],//订单行号
                'zkhSku'           => $val['shop_goods_id'],//zkh货号 shop_product_id捆绑商品时没有值，顾使用shop_goods_id
                'materialDescribe' => $lines[$val['shop_goods_id']]['materialDescribe'],//物料描述
                'number'           => (int)$val['number'],//数量
                'unit'             => $lines[$val['shop_goods_id']]['unit'],//单位
            ];
            $itemList[] = $item;
        }
        
        //多包裹逗号拼接
        if($params['delivery_package']){
            $logiNos = array_column($params['delivery_package'],'logi_no');
            if($logiNos){
                $params['logi_no'] = implode(',',array_unique($logiNos));
            }
        }
        $newParams = [
            'company_code'          => $params['logi_type'],
            'deliveryTime'          => date('Y-m-d', $params['delivery_time']),//预计发货时间
            'deliveryWay'           => 1,//发货方式(发货方式：1 商家联系物流，2自主车辆配送，3 震坤行自提，4 坤合物流,5 震坤行提货)
            'logisticsName'         => $params['logi_name'],//物流公司名称
            'logisticsCode'         => $params['logi_no'],//物流单号
            'fromProvince'          => isset($province) ? $province : '',//发货省份
            'fromCity'              => isset($city) ? $city : '',//发货城市
            'deliveryVehicleNumber' => '',//送货车牌号 deliveryWay=2时必填
            'driverName'            => '',//司机姓名
            'driverPhone'           => '',//司机联系方式
            'signReceiptUrl'        => '',//签单图片
            'remark'                => $params['memo'],//备注
            'itemList'              => json_encode($itemList),//预计发货时间
        ];
        return $newParams;
    }
    
    /**
     * 发货单确认详情查询
     * @param $sdf
     * @return mixed
     * @author db
     * @date 2023-09-27 4:43 下午
     */
    public function getDeliveryPart($sdf)
    {
        // 整理参数格式
        $title = sprintf('发货单确认详情查询[%s]-%s', $sdf['delivery_bn'], $sdf['orderinfo']['order_bn']);
        
        $params['purchaseOrderId'] = $sdf['orderinfo']['order_bn'];
        //请求接口名
        $api_method = $this->get_delivery_apiname('delivery');
        $result     = $this->__caller->call($api_method, $params, [], $title, 10, $sdf['orderinfo']['order_bn']);
        
        if ($result['rsp'] != 'succ') {
            $result['error_msg'] = '发货单确认详情查询失败';
            return $result;
        }
        
        return $result;
    }
    
    /**
     * 获取发货单打印数据 pdf格式连接
     * @param $sdf
     * @author db
     * @date 2023-10-08 4:03 下午
     */
    public function getPrintDelivery($sdf)
    {
        $title = sprintf('获取送货单详情pdf[%s]-%s', $sdf['delivery_bn'], $sdf['order_bn']);
        
        if (!$sdf['deliveryCode']) {
            return array('rsp' => 'fail', 'msg' => '送货单号为空!');
        }
        $params     = ['deliveryCode' => $sdf['deliveryCode']];
        $api_method = $this->get_delivery_apiname('print');
        $result     = $this->__caller->call($api_method, $params, [], $title, 10, $sdf['delivery_bn']);
        
        return $result;
    }
}