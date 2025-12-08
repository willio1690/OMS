<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 物流
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_logistics extends erpapi_wms_request_abstract
{
    /**
     * 获取物流公司
     *
     * @return void
     * @author
     **/

    public function logistics_getlist($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'获取物流公司';

        return $this->__caller->call(WMS_LOGISTICS_COMPANIES_GET, null, null, $title, 10);
    }

    public function logistics_create($params) {
        if(empty($params)) {
            return $this->error('缺少参数');
        }
        $title = $this->__channelObj->wms['channel_name'] . '推送快递公司('. $params['name'] .')';
        $primaryBn = $params['type'];
        $sdf = $this->_format_create_params($params);
        return $this->__caller->call(WMS_LOGISTICS_CREATE, $sdf, array(), $title, 10, $primaryBn);
    }

    protected function _format_create_params($params) {

        $sdf = array(
            'carrierNo' => $params['corp_id'] ? $this->get_wmslogi_code($this->__channelObj->wms['channel_id'],$params['type']) : $params['type'],
            'carrierName' => $params['name']
        );
        return $sdf;
    }
}
