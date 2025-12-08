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
 * @author liuzecheng<liuzecheng@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_vop_request_order extends erpapi_shop_request_order
{

	/*
     * 10:订单已审核（已处理),22:已发货,25:已签收,45:退款处理中,49:已退款,53:退货未审核,54:退货已审核,55:拒收回访,58:退货已返仓,60:已完成,70:用户已拒收,97:订
单已取消,117:退货审核中,118:订单申请断货,119：断货申请通过，300：拒收异常
     */

    protected function doGetOrderStatusRet($rsp)
    {
        $data = array();
        if($rsp['data']) {
            $tmp = json_decode($rsp['data'], 1);
            $tmp = json_decode($tmp['msg'], 1);
            foreach($tmp['result']['success_data'] as $val){
                $data[$val['order_id']] = in_array($val['order_status'], array(10,22,25,60)) ? true : false;
            }
        }
        $rsp['data'] = $data;
        $rsp['rsp'] = $data ? 'succ' : 'fail';
        return $rsp;
    }
}