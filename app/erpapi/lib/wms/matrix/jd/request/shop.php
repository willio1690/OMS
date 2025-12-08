<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author
 * @describe 店铺推送
 */

class erpapi_wms_matrix_jd_request_shop extends erpapi_wms_request_shop {

    protected function _format_create_params($params)
    {
        $sdf = parent::_format_create_params($params);
        $sdf['deptNo'] = app::get('wmsmgr')->getConf('department_no_'.$this->__channelObj->wms['channel_id']);
        $sdf['address'] = $sdf['province'] ? $sdf['province'] . $sdf['city'] . $sdf['district'] . $sdf['addr'] : '店铺详细地址';
        $sdf['afterSaleContacts'] = $sdf['contacts'] ? $sdf['contacts'] : '售后人';
        $sdf['afterSaleAddress'] = $sdf['address'];
        $sdf['afterSalePhone'] = $sdf['phone'] ? $sdf['phone'] : '14111111111';
        $sdf['spSourceNo'] = $params['shop_type']=='360buy' ? '1' : '6';
        return $sdf;
    }
}
