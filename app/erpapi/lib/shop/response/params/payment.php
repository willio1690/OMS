<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/5/24
 * @describe 支付单数据验证
 */

class erpapi_shop_response_params_payment extends erpapi_shop_response_params_abstract {
    protected function add(){
        return array(
            'payment_bn' => array(
                'required' => 'true',
                'errmsg' => '支付单号不能为空'
            ),
            'status' => array(
                'required' => 'true',
                'errmsg' => '支付单状态值不能为空'
            ),
            'money' => array(
                'type' => 'method',
                'method' => 'validAddMoney',
                'errmsg' => '支付金额不正确'
            ),
            'order_info' => array(
                'type' => 'array',
                'col' => array(
                    'pay_status' => array(
                        'type' => 'enum',
                        'errmsg' => '订单已退款或已支付,不生成支付单',
                        'in_out' => 'out',
                        'value' => array('1', '5')
                    ),
                    'status' => array(
                        'type' => 'enum',
                        'errmsg' => '订单状态非活动,无法支付',
                        'value' => array('active')
                    ),
                    'process_status' => array(
                        'type' => 'enum',
                        'errmsg' => '订单已取消,不能生成支付单',
                        'in_out' => 'out',
                        'value' => array('cancel')
                    )
                )
            ),
        );
    }

    protected function validAddMoney($params) {
        if($params['money'] <= 0) {
            return false;
        }
        $payed = bcadd($params['order_info']['payed'], $params['money'],3);
        if (bccomp($payed, $params['order_info']['total_amount'], 3) == 1) {
            return false;
        }
        $money = $params['money'];
        if ($params['other_payment']) {
            foreach ($params['other_payment'] as $key => $payment) {
                $money += $payment['cur_money'];
            }
        }
        if (bccomp($money, $params['order_info']['total_amount'], 3) == 1) {
            return false;
        }
        return true;
    }

    protected function statusUpdate(){
        return array(
            'status' => array(
                'type'=>'enum',
                'errmsg' => '状态值不对',
                'value' => array('succ')
            ),
            'order_bn' => array(
                'required' => 'true',
                'errmsg' => '订单号不能为空'
            ),
            'payment_bn' => array(
                'required' => 'true',
                'errmsg' => '支付单号不能为空'
            )
        );
    }
}