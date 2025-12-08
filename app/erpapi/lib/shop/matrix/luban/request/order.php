<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_luban_request_order extends erpapi_shop_request_order
{
    private $__status = array(
        /*
        0  => [ 'key' => 'ERP_TRANSFER',  'value' => '完成转单' ],
        1  => [ 'key' => 'ERP_CHECK',     'value' => '完成审单' ],
        3  => [ 'key' => 'CP_NOTIFY',     'value' => '完成通知仓储配货' ],
        4  => [ 'key' => 'ACCEPT',        'value' => '仓库接单完成' ],
        5  => [ 'key' => 'PRINT',         'value' => '完成打印拣货单/完成打印面单' ],
        8  => [ 'key' => 'PICK',          'value' => '完成拣货' ],
        9  => [ 'key' => 'CHECK',         'value' => '拣货复核完成' ],
        10 => [ 'key' => 'PACKAGE',       'value' => '仓库完成打包' ],
        12 => [ 'key' => 'CONFIRM',       'value' => '仓库通知ERP出库' ],
        */

        // 收单
        'build' => [
            'event' => [
                ['key' => 'ERP_TRANSFER', 'value' => '完成转单'],
            ],
        ],
        // 审单
        'check' => [
            'event' => [
                ['key' => 'ERP_CHECK', 'value' => '完成审单'],
            ],
        ],
        // 推仓
        'to_wms' => [
            'event' => [
                ['key' => 'CP_NOTIFY', 'value' => '完成通知仓储配货'],
                ['key' => 'ACCEPT', 'value' => '仓库接单完成']
            ],
        ],
        // 打印
        'print_stock' => [
            'event' => [
                ['key' => 'PRINT', 'value' => '完成打印拣货单/完成打印面单'],
            ],
        ],
        // 拣货
        'picking' => [
            'event' => [
                ['key' => 'PICK', 'value' => '完成拣货'],
                ['key' => 'CHECK', 'value' => '拣货复核完成'],
            ],
        ],
        // 出库
        'dispatch' => [
            'event' => [
                ['key' => 'PACKAGE', 'value' => '仓库完成打包'],
                ['key' => 'CONFIRM', 'value' => '仓库通知ERP出库'],
            ],
        ],
    );

    /**
     * confirmModifyAdress
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function confirmModifyAdress($sdf){
        $params = [
            'tid'=>$sdf['order_bn'],
            'is_approved'=>$sdf['confirm'] ? '0' : '1003', /*：0:同意;
拒绝需要传入以下参数：
1001:订单已进入拣货环节
1002:订单已进入配货环节
1003:订单已进入仓库环节
1004:订单已进入出库环节
1005:订单已进入发货环节*/
        ];
        $title = '买家修改地址确认修改';
        $rs = $this->__caller->call(SHOP_CONFIRM_ADDRESS_MODIFY,$params,array(),$title,20,$sdf['order_bn']);
        return $rs;
    }

    /**
     * 抖音全链路
     * 
     * @return void
     * @author 
     * */
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

        $trace_list = [];
        foreach ($sdf_arr as $sk => $sdf) {
            $status_list = $this->__status[$sdf['message_produce_status']]['event'];
            foreach ($status_list as $status_info) {
                $status = $status_info['key'];

                $product_list = [];
                foreach ($sdf['order_objects'] as $k => $v) {
                    $product = [
                        'sku_order_id'          =>  $v['oid'],
                        'count'                 =>  (int)$v['quantity'],
                        'sub_product_info_list' =>  [],
                    ];
                    foreach ($v['items'] as $ik => $iv) {
                        $product['sub_product_info_list'][] = [
                            'sku_id'     => $iv['shop_product_id'],
                            'product_id' => $iv['shop_goods_id'],
                            'count'      => $iv['nums'],
                        ];
                    }
                    $product_list[] = $product;
                }

                $trace_list[] = [
                    'order_id'      =>  $sdf['order_bn'],
                    'status'        =>  $status,
                    'record_time'   =>  date('Y-m-d H:i:s', $sdf['last_modified']),
                    'product_list'  =>  $product_list,
                ];
            }
        }

        $trace_lists = array_chunk($trace_list, 20);
        foreach ($trace_lists as $trace_list) {

            $order_bns_str = implode(',', array_unique(array_column($trace_list, 'order_id')));
            $title = sprintf('抖音全链路[%s]',$order_bns_str); 

            $params = array(
                'upload_params' => json_encode([
                    'trace_list' => json_encode($trace_list),
                    'node_id' => $this->__channelObj->channel['node_id'],
                ]),
            );

            $callback = array(
                'class' => get_class($this),
                'method' => 'callback',
                'params' => array(
                    'obj_bn' => '',
                ),
            );

            $res = $this->__caller->call(SHOP_UPLOAD_ORDER_RECORD, $params, $callback, $title,10,substr($order_bns_str, 0, 50),true);
        }
        return $this->succ();
    }

        /**
     * serial_sync
     * @param mixed $serialNumber serialNumber
     * @param mixed $order_bn order_bn
     * @return mixed 返回值
     */
    public function serial_sync($serialNumber,$order_bn ='') {
        $params = [];

        foreach ($serialNumber as $value) {
            $params[$order_bn][] = $value['serial_number'];
        }
        $params = ['serial_number_data'=>json_encode($params)];
        $title = '唯一码上传';

        $res = $this->__caller->call(SHOP_SERIALNUMBER_UPDATE,$params,array(),$title,10,$order_bn);

        return $res;
    }
}