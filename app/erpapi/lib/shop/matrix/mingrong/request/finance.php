<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/03/15
 * Time: 15:02
 */
class erpapi_shop_matrix_mingrong_request_finance extends erpapi_shop_request_finance
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

        $api_name = SHOP_AGREE_REFUND;
        $title = '店铺('.$this->__channelObj->channel['name'].')同意退款(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';

        $userName = kernel::single('desktop_user')->get_name();
        $params = array(
            'description' => $userName . '同意',  # 审核意见
            'refund_id' => $refund['refund_apply_bn'],  # 售前退款数据唯一标示
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
        switch( $status ){
            case '2':
                $api_method = SHOP_AGREE_REFUND;
                break;
            case '3':
                $api_method = SHOP_REFUSE_REFUND;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
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
            'description' => $oper['op_name'] . 'erp操作',  # 审核意见
            'refund_id' => $refund['refund_apply_bn'],  # 售前退款数据唯一标示
        );

        return $params;
    }
}