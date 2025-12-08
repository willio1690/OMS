<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_hupu_request_order extends erpapi_shop_request_order
{
    /**
     * @param $arrOrderBn
     * @return array
     * 1=>’订单创建’,
    2=>’已发货’,
    3=>’订单取消’,
    4=>’订单缺货’,
    5=>’退货中’,
    6=>’已完成’,
     */
    public function getOrderStatus($arrOrderBn)
    {
        $rsp = array('rsp' => 'succ', 'data' => array());
        return array('rsp' => 'fail', 'err_msg' => '接口被禁止'); //已接取消状态
        foreach($arrOrderBn as $orderBn) {
            $params = array('tid' => $orderBn);
            $title = "店铺(" . $this->__channelObj->channel['name'] . ")获取前端店铺" . $orderBn . "的订单状态";
            $tmpRsp = $this->__caller->call(SHOP_GET_ORDER_STATUS, $params, array(), $title, 10, $orderBn);
            if($tmpRsp['rsp'] == 'succ' && $tmpRsp['data']) {
                $data = json_decode($tmpRsp['data'], true);
                if(in_array($data['status'], array('1','2','4','6'))) {
                    $rsp['data'][$orderBn] = true;
                } else {
                    $rsp = array(
                        'rsp' => 'fail',
                        'msg' => $orderBn . '状态不能发货：' . $data['text']
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