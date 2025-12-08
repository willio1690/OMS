<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrix_yihaodian_request_aftersale extends erpapi_shop_request_aftersale {
    protected function __afterSaleApi($status, $returnInfo=null) {
        switch( $status ){
            case '3':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '4':
                $api_method = SHOP_CHECK_REFUND_GOOD;
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

    protected function __formatAfterSaleParams($aftersale,$status) {
        $oReturn_yhd = app::get('ome')->model('return_product_yihaodian');
        $oReturn_items = app::get('ome')->model('return_product_items');
        $return_yhd = $oReturn_yhd->dump(array('return_bn'=>$aftersale['return_bn']));
        $params = array(
            'refund_id'=>$aftersale['return_bn'],
        );
        $return_id = $aftersale['return_id'];
        switch ($status) {
            case '3':
                $items = $oReturn_items->getList('*',array('return_id'=>$return_id),0,-1);
                $return_num = 0;
                $amount = 0;
                foreach($items as $item){
                    $return_num+=$item['num'];
                    $amount+=$item['num'] * $item['price'];
                }
                $params['return_num'] = $return_num;
                $params['amount'] = $amount;
                $params['is_postfee'] = $return_yhd['isdeliveryfee'];
                $params['is_sendtype'] = $return_yhd['sendbacktype'];
                $params['seller_logistics_address_id'] = $return_yhd['isdefaultcontactname'];
                $params['memo'] = '同意退货';
                if ($return_yhd['isdefaultcontactname'] == '0') {
                    $params['receiver_name'] = $return_yhd['contactname'];
                    $params['receiver_phone'] = $return_yhd['contactphone'];
                    $params['receiver_address'] = $return_yhd['sendbackaddress'];
                }
                break;
            case '4':
                break;
            case '5':
                $params['message'] = $aftersale['refuse_message'];
                break;
            default: break;
        }
        return $params;
    }
}