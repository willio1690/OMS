<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/4/12
 * @describe 售后相关
 */

class erpapi_shop_matrix_shunguang_request_aftersale extends erpapi_shop_request_aftersale
{

    protected function __afterSaleApi($status, $returnInfo=null) {
        $apiName = '';
        if(in_array($status, array('3', '5'))) {
            $apiName = SHOP_ADD_REFUND_RPC;
        }
        return $apiName;
    }

    protected function __formatAfterSaleParams($aftersale, $status){
        $sdf = array(
            'refund_id' => $aftersale['return_bn'],
            'agree' => $status == '3' ? 1 : ($status == '5' ? 2 : ''),
            'handle_remark' => kernel::single('desktop_user')->get_name() . '操作,' . $aftersale['refuse_message']
        );
        return $sdf;
    }
}