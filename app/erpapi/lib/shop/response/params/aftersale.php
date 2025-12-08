<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc 售后单数据验证
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_response_params_aftersale extends erpapi_shop_response_params_abstract {

    protected function add() {
        $arr = array(
            'status' => array(
                'required' => 'true',
                'errmsg' => '售后状态不能为空'
            ),
            'order' => array(
                'type' => 'array',
                'col' => array(
                    'ship_status' => array(
                        'type' => 'enum',
                        'in_out' => 'out',
                        'value' => array('0', '4'),
                        'errmsg' => '订单未发货或已退货，不能申请'
                    )
                )
            ),
            'return_product_items' => array(
                'type' => 'array',
                'errmsg' => '售后商品格式不正确',
                'col' => array(
                    'product_id' => array(
                        'required' => 'true',
                        'errmsg' => '有商品被删除'
                    ),
                    'sendNum' => array(
                        'type' => 'method',
                        'method' => 'validAddSendNum',
                        'errmsg' => '退货商品超过了已发货商品'
                    )
                )
            )
        );
        return $arr;
    }

    protected function validAddSendNum($params) {
        if($params['sendNum'] < $params['num']) {
            return false;
        }
        return true;
    }

    protected function statusUpdate() {
        $arr = array(
            'status' => array(
                'required' => 'true',
                'errmsg' => '售后申请单状态不能为空',
            ),
            'return_bn' => array(
                'required' => 'true',
                'errmsg' => '售后申请单单号不能为空',
            ),
            'order_bn' => array(
                'required' => 'true',
                'errmsg' => '订单单号不能为空'
            )
        );
        return $arr;
    }

    protected function logisticsUpdate() {
        $arr = array(
            'return_bn' => array(
                'required' => 'true',
                'errmsg' => '售后申请单单号不能为空',
            ),
            'order_bn' => array(
                'required' => 'true',
                'errmsg' => '订单单号不能为空'
            ),
            'process_data' => array(
                'type' => 'array',
                'errmsg' => '缺少物流信息'
            )
        );
        return $arr;
    }
}