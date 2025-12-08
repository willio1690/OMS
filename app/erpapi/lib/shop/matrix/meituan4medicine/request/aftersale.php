<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 美团医药
 */
class erpapi_shop_matrix_meituan4medicine_request_aftersale extends erpapi_shop_request_aftersale
{
    protected function __afterSaleApi($status, $aftersale)
    {
        switch ($status) {
            case '3':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '5':
                $api_method = SHOP_REFUSE_RETURN_GOOD;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }
    
    /**
     * 卖家确认收货
     * @param $data
     */

    public function returnGoodsConfirm($sdf)
    {
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $returnModel = app::get('ome')->model('return_product');
        $returninfo = $returnModel->db_dump(array('return_id'=>$sdf['return_id'],'source'=>'matrix'),'order_id');
        $orderInfo = app::get('ome')->model('orders')->db_dump(array('order_id'=>$returninfo['order_id']),'order_bn');
        $params['tid'] = $orderInfo['order_bn'];
        $this->__caller->call(SHOP_AGREE_REFUND, $params, array(), $title, 10, $sdf['return_bn']);
    }
    
    public function __formatAfterSaleParams($aftersale, $status)
    {
        $params = array();
        switch ($status)
        {
            case '5':
                $params['reject_reason_code'] = $aftersale['memo']['reject_reason_code'];
                break;
        }
        return $params;
    }
}