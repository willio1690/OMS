<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 供应商推送
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_supplier extends erpapi_wms_request_abstract
{
    /**
     * 供应商创建
     *
     * @return void
     * @author
     **/
    public function supplier_create($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '供应商添加';

        $params = $this->_format_supplier_create_params($sdf);
        $rs = $this->__caller->call(WMS_VENDORS_GET, $params, null, $title, 10);
        if ($rs['rsp'] == 'succ') {
            $rs['data'] = json_decode($rs['data'],true);
        }

        return $rs;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    protected function _format_supplier_create_params($sdf)
    {
        $area = $sdf['area'];

        if ($area) {
            $area        = explode(':',$area);
            $area_detail = explode('/',$area[1]);
            $state       = $area_detail[1];
            $city        = $area_detail[0];
        }

        $params = array(
            'CustomerID'   => $sdf['bn'],//
            'vendor_ename' => $sdf['name'],//
            'vendor_name'  => $sdf['name'],
            'address'      => $sdf['addr'] ? $sdf['addr'] : '供应商详细地址',//
            'state'        => $state,//
            'city'         => $city,//
            'country'      => '中国',//
        );

        return $params;
    }

    public function update($sdf)
    {
        $title = $this->__channelObj->channel['channel_name'] . '供应商(' . $sdf['name'] . ')更新';

        $params = $this->_format_create_params($sdf);
        $params['eclp_supplier_no'] = $sdf['wms_supplier_bn'];

        $rs = $this->__caller->call(WMS_VENDORS_UPDATE, $params, null, $title, 10);

        return $rs;
    }
}
