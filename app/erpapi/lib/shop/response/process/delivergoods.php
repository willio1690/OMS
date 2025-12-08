<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单催发货
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 0.1
 */
class erpapi_shop_response_process_delivergoods extends erpapi_shop_response_abstract
{
    /**
     * urgent
     * @param mixed $order order
     * @return mixed 返回值
     */

    public function urgent($order)
    {
        //更新订单为"催发货"状态
        $orderMdl       = app::get('ome')->model('orders');
        $deliveryMdl    = app::get('ome')->model('delivery');
        $branchMdl      = app::get('ome')->model('branch');

        $order_bool_type = $order['order_bool_type'] | ome_order_bool_type::__URGENT_DELIVERY;

        $result = $orderMdl->update(array('order_bool_type'=>$order_bool_type), array('order_id'=>$order['order_id']));
        if(!$result){
            return array('rsp'=>'fail', 'msg'=>'催发货: 订单更新为催发货,失败!');
        }
        
        $logisticTime = ''; $processCode = 30;
        if (in_array($order['process_status'], ['splitting', 'splited'])){
            $processCode = 50;
            $deliveryList = $deliveryMdl->getDeliversByOrderId($order['order_id']);
            $delivery = array_shift($deliveryList);

            $branch = $branchMdl->dump((int)$delivery['branch_id'], 'latest_delivery_time');

            if ($branch['latest_delivery_time']){
                $logisticTime = strtotime(substr($branch['latest_delivery_time'],0,2).':'.substr($branch['latest_delivery_time'],2,2));

                $logisticTime = $logisticTime>time()?:$logisticTime+86400;

                $logisticTime = date('Y-m-d H:i:s',$logisticTime);
            }

            if ($delivery['logi_no']) {
                $processCode = 10;
            }

            if ($delivery['expre_status'] == 'true'){
                $processCode = 90;
            }

            if ($delivery['verify'] == 'true'){
                $processCode = 70;
            }

            if ($delivery['process'] == 'true'){
                $processCode = 99;
            }
        }

        $seller_name = $order['seller_name'];
        if (!$seller_name) {
            $shop = app::get('ome')->model('shop')->dump($order['shop_id'], 'addon');

            $seller_name = $shop['addon']['nickname'];
        }
        

        $data = [
            'tid'           => $order['order_bn'],
            'logisticTime'  => $logisticTime,
            'sellerNick'    => $seller_name,
            'processCode'   => $processCode,
        ];

        
        //日志
        $memo = '催发货: 买家催发货，时间：'.$order['logistics_time'];
        app::get('ome')->model('operation_log')->write_log('order_modify@ome', $order['order_id'], $memo);
        
        return array('rsp'=>'succ','msg'=>'催发货: 订单更新为催发货成功!','data' => $data);
    }

    /**
     * promise
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function promise($sdf) {
        if($sdf['event_type'] == 'latest_delivery_time') {
            $orderExtendObj = app::get('ome')->model('order_extend'); 
            $extendinfo = [
                'order_id' => $sdf['order']['order_id'], 
                'latest_delivery_time' => kernel::single('ome_func')->date2time($sdf['pick_date'])
            ];
            $orderExtendObj->save($extendinfo);
            return ['rsp'=>'succ', 'msg'=>'最晚发货时间更新成功'];
        }
        return ['rsp'=>'fail', 'msg'=>'缺少类型'];
    }
}
