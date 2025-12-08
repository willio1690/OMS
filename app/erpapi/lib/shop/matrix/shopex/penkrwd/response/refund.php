<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_penkrwd_response_refund extends erpapi_shop_matrix_shopex_response_refund {

    //全民分销未生成退款单前允许编辑
    /**
     * _formatAddParams
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _formatAddParams($params) {
        $sdf=parent::_formatAddParams($params);
        if($sdf['status']!='4') {
            $sdf['refund_version_change'] = true;
        }
        return $sdf;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params) {
        $sdf=parent::add($params);
        if ($sdf['order']['ship_status']) {
            $sdf['refund_refer']=in_array($sdf['order']['ship_status'],array('1','3')) ? '1' : '0';
        }
        return $sdf;
    }
}