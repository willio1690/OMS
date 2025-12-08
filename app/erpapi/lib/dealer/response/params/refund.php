<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 售前退款据验证
 *
 * @version 2024.04.11
 */
class erpapi_dealer_response_params_refund extends erpapi_dealer_response_params_abstract {

    protected function add() {
        $arr = array(
            'order_bn' => array(
                'required' => 'true',
                'errmsg' => '缺少订单号'
            ),
            'refund_bn' => array(
                'required' => 'true',
                'errmsg' => '缺少退款单号'
            ),
            'status' => array(
                'required' => 'true',
                'errmsg' => '状态值不能为空'
            ),
            'money' => array(
                'type' => 'method',
                'method' => 'validAddMoney',
                'errmsg' => '金额不对'
            ),
            'refund_apply' => array(
                'type' => 'method',
                'method' => 'validAddRefundApply'
            ),
            'refund_type' => array(
                'type' => 'method',
                'method' => 'validAddRefundType'
            ),
        );
        return $arr;
    }

    protected function validAddMoney($params) {
        if($params['money'] <= 0) {
            if($params['cod_zero_accept']) {
                if($params['order']['is_cod'] == 'false' || ($params['order']['is_cod'] == 'true' && $params['order']['ship_status']!='0')) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    protected function validAddRefundApply($params) {
        $applyData = $params['refund_apply'];
        if($applyData) {
            if (in_array($applyData['status'], array('3'))&& !$params['refund_version_change']) {
                return array('rsp'=>'fail', 'msg'=>'退款申请单['. $applyData['refund_apply_bn'] .']已拒绝，无法退款！');
            }
            if (in_array($applyData['status'], array('4'))) {
                return array('rsp'=>'fail', 'msg'=>'退款申请单['. $applyData['refund_apply_bn'] .']已退款，无法退款！');
            }
        }
        return array('rsp'=>'succ');
    }

    protected function validAddRefundType($params) {
        $type = $params['refund_type'];
        $status = $params['status'];
        if ($params['version'] == '1') {
            if (bccomp($params['order']['payed'], $params['money'], 3) < 0) {
                return array('rsp' => 'fail', 'msg' => '退款失败,支付金额(' . $params['order']['payed'] . ')小于退款金额(' . $params['money'] . ')');
            }
            if($params['order']['process_status'] == 'cancel') {
                return array('rsp' => 'fail', 'msg' => '订单已取消不能退款');
            }
        }
        if($type == 'refund' || $status == 'succ') {
        } elseif ($type == 'apply') {
            $refundApply = $params['refund_apply'];
            if($refundApply) {
                // 判断申请退款单是否已经存在
                if ($refundApply['apply_id'] && $status == '0' && !$params['refund_version_change'] && ($refundApply['memo'] == $params['memo']) && ($refundApply['money'] == $params['money']) ){
                    return array('rsp'=>'fail', 'msg' => "退款申请单[{$refundApply['refund_apply_bn']}]已经存在 且status等于{$refundApply['status']}");
                }
                if ($status == '0' && $refundApply['status'] != '0' && !$params['refund_version_change']) {
                    return array('rsp'=>'fail','msg' => '退款申请单处理中，不允许更新状态');
                }
            }
        }
        return array('rsp'=>'succ');
    }

    protected function statusUpdate() {
        $arr = array(
            'order_bn' => array(
                'required' => 'true',
                'errmsg' => '缺少订单号'
            ),
            'refund_bn' => array(
                'required' => 'true',
                'errmsg' => '缺少退款单号'
            ),
            'status' => array(
                'type' => 'enum',
                'value' => array('succ'),
                'errmsg' => '只更新succ状态'
            )
        );
        return $arr;
    }
}