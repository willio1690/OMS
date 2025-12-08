<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_ecshopx_response_aftersale extends erpapi_shop_response_aftersale
{
    protected function _formatAddParams($params) {
        $sdf=parent::_formatAddParams($params);
        $shopId = $this->__channelObj->channel['shop_id'];
        $returnModel = app::get('ome')->model('return_product');
        $tgReturn = $returnModel->getList('return_id', array('shop_id'=>$shopId,'return_bn'=>$sdf['return_bn']));
        if($tgReturn) {
            $sdf['action'] = 'update';
            $sdf['return_id'] = $tgReturn[0]['return_id'];
        }
        if($sdf['status']=='1'||$sdf['status']=='5') {
            $sdf['refund_version_change'] = true;
        }
        return $sdf;
    }
    
    protected function _checkeditAftersale($tgReturn,$refund_version_change) {
        if($tgReturn&&$refund_version_change==true){
            return false;
        }else{
            return $tgReturn;
        }
    }
}
