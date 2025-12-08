<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author sunjing
 * @describe 售后数据转换
 */

class erpapi_shop_matrix_xiaomi_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    protected function _getAddType($sdf) {

        if ($sdf['has_good_return'] == 'true') {//需要退货才更新为售后单
            if (in_array($sdf['order']['ship_status'],array('0'))) {
                #有退货，未发货的,做退款
                return 'refund';
            }else{
                //识别如果是已完成的售后，转成退款单更新的逻辑
                 if(strtolower($sdf['status']) == 'success'){
                    $refundOriginalObj = app::get('ome')->model('return_product');
                    #退货状态必须是已完成
                    $refundOriginalInfo = $refundOriginalObj->getList('return_id', array('return_bn'=>$sdf['refund_bn'],'status' =>'4') , 0 , 1);
                    
                    if($refundOriginalInfo){
                        $refundApplyObj = app::get('ome')->model('refund_apply');
                        #售后退款申请单的退款状态，不能是已退款
                        $refundApplyInfo = $refundApplyObj->getList('refund_apply_bn', array('return_id'=>$refundOriginalInfo[0]['return_id'],'status' =>array('0','1','2','5','6')) , 0 , 1);



                        if($refundApplyInfo){
                            $sdf['refund_bn'] = $refundApplyInfo[0]['refund_apply_bn'];
                            return 'refund';
                        }
                    }
                }
                #有退货，已发货的,做售后
                return 'returnProduct';
            }
        }else{
            #无退货的，直接退款
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
        if(strtolower($sdf['status']) == 'success'){
            $refundOriginalObj = app::get('ome')->model('return_product');
            #退货状态必须是已完成
            $refundOriginalInfo = $refundOriginalObj->getList('return_id', array('return_bn'=>$sdf['refund_bn'],'status' =>'4') , 0 , 1);
            
            if($refundOriginalInfo){
                $refundApplyObj = app::get('ome')->model('refund_apply');
                #售后退款申请单的退款状态，不能是已退款
                $refundApplyInfo = $refundApplyObj->getList('refund_apply_bn', array('return_id'=>$refundOriginalInfo[0]['return_id'],'status' =>array('0','1','2','5','6')) , 0 , 1);
                if($refundApplyInfo){
                    $sdf['refund_bn'] = $refundApplyInfo[0]['refund_apply_bn'];
                    
                }
            }
        }
        $sdf = parent::_refundAddSdf($sdf);
        
        return $sdf;
    }

}