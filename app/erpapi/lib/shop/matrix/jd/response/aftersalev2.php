<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/12/7
 * @describe 京东供应商平台
 */

class erpapi_shop_matrix_jd_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        $sdf['refund_type'] = 'apply';
        return $sdf;
    }

    protected function _getAddType($sdf) {
        return 'refund';#只有售前退款单
    }

    protected function _formatAddItemList($sdf, $convert=array()) {
        $convert = array(
            'sdf_field'=>'oid',
            'order_field'=>'oid',
            'default_field'=>'outer_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }

}