<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2018/12/07
 * Time: 15:02
 */
class erpapi_shop_matrix_jd_request_finance extends erpapi_shop_request_finance
{
    /**
     * _getAddRefundParams
     * @param mixed $refund refund
     * @return mixed 返回值
     */

    public function _getAddRefundParams($refund){
        $addon = unserialize($refund['addon']);
        if (!$refund || $addon['reship_id']) {
            return array();
        }

        $api_name = SHOP_REFUND_CHECK;
        $title = '店铺('.$this->__channelObj->channel['name'].')同意退款(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';

        $userName = kernel::single('desktop_user')->get_name();
        $params = array(
            'tid' => $refund['order_bn'],  # 订单编号
            'approval_suggestion' => $userName . '同意',  # 审核意见
            'approval_state' => 1,  # 审核状态 1:审核通过 2:审核不通过
            'refund_id' => $refund['refund_apply_bn'],  # 售前退款数据唯一标示
            'operator_state' => 5,  # 操作状态：5新订单;9正在出库;10 出库成功;15正在发货;16发货成功
        );
        return array($api_name, $title, $params);
    }

    /**
     * 退款申请单状态同步接口名
     * @param  string $status 2:已接受申请、3:已拒绝
     * @return [type]         [description]
     */
    protected function _updateRefundApplyStatusApi($status, $refundInfo=null)
    {
        return SHOP_REFUND_CHECK;
    }

    /**
     * 退款申请单接口数据
     * @param  array $refund 退款申请单明细
     * @param  string $status 2:已接受申请、3:已拒绝
     * @return [type]         [description]
     */
    public function _updateRefundApplyStatusParam($refund, $status)
    {
        $oper = kernel::single('ome_func')->getDesktopUser();
        $params = array(
            'tid' => $refund['order_bn'],  # 订单编号
            'approval_suggestion' => $oper['op_name'] . '操作',  # 审核意见
            'approval_state' => $status == '2' ? 1 : 2,  # 审核状态 1:审核通过 2:审核不通过
            'refund_id' => $refund['refund_apply_bn'],  # 售前退款数据唯一标示
            'operator_state' => 5,  # 操作状态：5新订单;9正在出库;10 出库成功;15正在发货;16发货成功
        );

        return $params;
    }
}