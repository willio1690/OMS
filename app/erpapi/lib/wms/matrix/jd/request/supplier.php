<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author
 * @describe 供应商推送
 */

class erpapi_wms_matrix_jd_request_supplier extends erpapi_wms_request_supplier
{
    protected $failMsgList = array('新增失败：记录已存在！');

    protected function _format_supplier_create_params($sdf)
    {
        $params = parent::_format_supplier_create_params($sdf);
        $params['deptNo'] = app::get('wms')->getConf('wms_storage_division_code_'.$this->__channelObj->channel['channel_id']);
        $params['vendor'] = $sdf['bn'];
        $params['contacts'] = $sdf['contacter'] ? $sdf['contacter'] : '供应商联系人';
        $params['phone_num'] = $sdf['telphone'] ? $sdf['telphone'] : '14111111111';
        $params['fax'] = $sdf['fax'];
        $params['country'] = $sdf['district'];
        return $params;
    }
}
