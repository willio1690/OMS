<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_taobao_request_order extends erpapi_shop_request_order
{
    private $__status = array(
        // 收单
        'build' => [
            'event' => [
                ['key' => 'QIMEN_ERP_TRANSFER', 'value' => '已转单'],
            ],
        ],
        // 审单
        'check' => [
            'event' => [
                ['key' => 'QIMEN_ERP_CHECK', 'value' => '已客审'],
            ],
        ],
        // 推仓
        'to_wms' => [
            'event' => [
                ['key' => 'QIMEN_CP_NOTIFY', 'value' => '已通知配货'],
            ],
        ],
        // // 打印拣货单
        // 'print_stock' => [
        //     'event' => [
        //         ['key' => 'X_SORT_PRINTED', 'value' => '已打拣货单'],
        //     ],
        // ],
        // // 打印发货单
        // 'print_deliv' => [
        //     'event' => [
        //         ['key' => 'X_SEND_PRINTED', 'value' => '已打发货单'],
        //     ],
        // ],
        // // 打印物流单
        // 'print_expre' => [
        //     'event' => [
        //         ['key' => 'X_LOGISTICS_PRINTED', 'value' => '已打物流单'],
        //     ],
        // ],
        // // 拣货
        // 'picking' => [
        //     'event' => [
        //         ['key' => 'X_SORTED', 'value' => '已拣货'],
        //         ['key' => 'X_EXAMINED', 'value' => '已验货'],
        //     ],
        // ],
        // 出库
        'dispatch' => [
            'event' => [
                ['key' => 'QIMEN_CP_OUT', 'value' => '已出库'],
            ],
        ],
    );

    /**
     * 淘宝全链路
     *
     * @return void
     * @author 
     **/

    public function message_produce($sdf_arr,$queue=false)
    {
        foreach ($sdf_arr as $sk => $sdf) {
            if (!isset($this->__status[$sdf['message_produce_status']])) {
                unset($sdf_arr[$sk]);
                continue;
            }
        }
        if (!$sdf_arr) {
            return $this->succ();
        }

        $args = func_get_args();array_pop($args);
        $_in_mq = $this->__caller->caller_into_mq('order_message_produce','shop',$this->__channelObj->channel['shop_id'],$args,$queue);
        if ($_in_mq) {
            return $this->succ('成功放入队列');
        }

        foreach ($sdf_arr as $sk => $sdf) {

            $status_list = $this->__status[$sdf['message_produce_status']]['event'];
            foreach ($status_list as $status_info) {
                $status = $status_info['key'];

                // 整理参数格式
                $title = sprintf('淘宝全链路%s[%s]',$status,$sdf['order_bn']); 


                $remark = $sdf['remark'] ? $sdf['remark'] : $status_info['value'];

                $order_ids = array();
                foreach ((array) $sdf['order_objects'] as $key => $value) {
                    if ($value['oid']) $order_ids[] = $value['oid'];
                }

                $params = array(
                    'topic'       => 'taobao_jds_TradeTrace', 
                    'tid'         => $sdf['order_bn'],
                    'order_ids'   => implode(',',$order_ids),
                    'status'      => $status,
                    'action_time' => date("Y-m-d H:i:s"),
                    'remark'      => $remark,
                );

                $callback = array(
                   'class' => get_class($this),
                   'method' => 'callback',
                    'params' => array(
                        'obj_bn' => $sdf['order_bn'],
                    ),
                );

                $res = $this->__caller->call(SHOP_TMC_MESSAGE_PRODUCE, $params, $callback, $title,10,$sdf['order_bn'],true);
            }
        }
        return $this->succ();
    }

    protected function __formatUpdateOrderShippingInfo($order) {
        $consignee_area = $order['consignee']['area'];
        if(strpos($consignee_area,":")){
            $t_area            = explode(":",$consignee_area);
            $t_area_1          = explode("/",$t_area[1]);
            $receiver_state    = $t_area_1[0];
            $receiver_city     = $t_area_1[1];
            $receiver_district = $t_area_1[2];
        }
        $params = array();
        $params['tid']               = $order['order_bn'];
        $params['receiver_name']     = $order['consignee']['name']?$order['consignee']['name']:'';
        $params['receiver_phone']    = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
        $params['receiver_mobile']   = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
        $params['receiver_state']    = $receiver_state ? $receiver_state : '';
        $params['receiver_city']     = $receiver_city ? $receiver_city : '';
        $params['receiver_district'] = $receiver_district ? $receiver_district : '';
        $params['receiver_address']  = $order['consignee']['addr']?$order['consignee']['addr']:'';
        $params['receiver_zip']      = $order['consignee']['zip']?$order['consignee']['zip']:'';
        return $params;
    }
}