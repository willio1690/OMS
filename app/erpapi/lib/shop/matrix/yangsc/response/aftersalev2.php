<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author sunjing
 * @describe 售后数据转换
 */

class erpapi_shop_matrix_yangsc_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    
   
    
    protected function _getAddType($sdf) {

        if ($sdf['refund_type'] == 'return') {
            if (in_array($sdf['order']['ship_status'],array('0'))) {
                #退款单
                return 'refund';
            }else{
                #退货申请单
                return 'returnProduct';
            }
        }else{
            #退款单
            return 'refund';
        }
    }

    protected function _formatAddItemList($sdf, $convert=array()) {
        $convert = array(
            'sdf_field'=>'item_id',
            'order_field'=>'shop_goods_id',
            'default_field'=>'item_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

    protected function _refundAddSdf($sdf){
        
        $sdf = parent::_refundAddSdf($sdf);
        $refund_fee = 0;
        if($sdf['refund_fee']<=0){
            foreach($sdf['refund_item_list'] as $v){
                $refund_fee+=$v['price']*$v['num'];
            }
            $sdf['refund_fee'] = $refund_fee;
        }


        return $sdf;
    }


  

    protected function _returnProductAddSdf($sdf) {
        
        $sdf = parent::_returnProductAddSdf($sdf);
        if ($sdf['refund_fee']<=0){
            foreach($sdf['refund_item_list'] as $v){
                $refund_fee+=$v['price']*$v['num'];
            }
            $sdf['refund_fee'] = $refund_fee;
        }
        return $sdf;
    }

}