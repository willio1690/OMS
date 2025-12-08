<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商家取消订单审核消息
 * Class erpapi_shop_matrix_360buy_response_bookingrefund
 */
class erpapi_shop_matrix_360buy_response_bookingrefund extends erpapi_shop_response_abstract
{
    /**
     * ordermsg
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function ordermsg($params)
    {
        $this->__apilog['title']       = '商家取消订单审';
        $this->__apilog['original_bn'] = $params['orderId'];
        if ($params['auditStatus'] != '1') {
            $this->__apilog['result']['msg'] = '审核不通过';
            return false;
        }
        
        $sdf = [
            'tid'          => $params['orderId'],
            'msg_id'       => '',
            'seller_nick'  => '',
            'user_nick'    => '',
            'call_type'    => 'synchronous',
            'refundStatus' => 1,
            'shop_id'      => $this->__channelObj->channel['shop_id'],
        ];
        return $sdf;
    }
}