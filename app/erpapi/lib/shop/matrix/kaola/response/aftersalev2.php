<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author 20180829 by wangjianjun
 * @describe 售后数据转换
 */

class erpapi_shop_matrix_kaola_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {
    
    protected function _getAddType($sdf){
        if($sdf['refund_type'] == 'return'){
            return 'returnProduct';
        }else{
            return 'refund';
        }
    }
    
    protected function _formatAddItemList($sdf, $convert=array()){
        if($sdf['refund_type'] == 'refund') {
            return array();
        }
        $convert = array(
                'sdf_field'=>'oid',
                'order_field'=>'oid',
                'default_field'=>'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }
    
    protected function _refundAddSdf($sdf){
        $sdf['shop_type'] = 'kaola';
        if(self::$refund_status[strtoupper($sdf['status'])] != '4') {
            $sdf['refund_type'] = 'apply';
        }
        $sdf = parent::_refundAddSdf($sdf);
        return $sdf;
    }
    
}