<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/10/23
 * @describe 订单处理
 */
class erpapi_shop_matrix_haoshiqi_request_order extends erpapi_shop_request_order
{

    /**
     * 获取OrderStatus
     * @param mixed $arrOrderBn arrOrderBn
     * @return mixed 返回结果
     */

    public function getOrderStatus($arrOrderBn)
    {
        $rsp = array('rsp' => 'succ', 'data' => array());
        foreach($arrOrderBn as $orderBn) {
            $tmpRsp = $this->get_order_detial($orderBn);
            if($tmpRsp['rsp'] == 'succ') {
                if($tmpRsp['data']['trade']['pay_status'] == 'PAY_FINISH'
                    || ($tmpRsp['data']['trade']['is_cod'] == 'true'
                        && $tmpRsp['data']['trade']['pay_status'] == 'PAY_NO')) {
                    $rsp['data'][$orderBn] = true;
                } else {
                    $rsp = array(
                        'rsp' => 'fail',
                        'msg' => $orderBn . '支付状态不对，可能存在退款'
                    );
                    break;
                }
            } else {
                $rsp = array(
                    'rsp' => 'fail',
                    'msg' => $orderBn . '请求状态失败：' . $tmpRsp['err_msg']
                );
                break;
            }
        }
        return $rsp;
    }
}