<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/9/13
 * @describe 店铺相关请求
 */
class erpapi_wms_request_shop extends erpapi_wms_request_abstract {

    /**
     * shop_create
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function shop_create($params) {
        if(empty($params)) {
            return $this->error('缺少参数');
        }
        $title = $this->__channelObj->wms['channel_name'] . '店铺('. $params['name'] .')新增';
        $primaryBn = $params['shop_bn'];
        $sdf = $this->_format_create_params($params);

        $rs = $this->__caller->call(WMS_SHOP_CREATE, $sdf,array(), $title, 10, $primaryBn);
        if(strpos($rs['msg'], $primaryBn . '已存在！') !== false) {
            return $this->succ('新建成功');
        }
        if ($rs['rsp'] == 'succ') {
            $rs['data'] = json_decode($rs['data'],true);
        }
        return $rs;
    }

    protected function _format_create_params($params) {
        $sdf = array(
            'isvShopNo' => $params['shop_id'] ? $this->_getShopCode($params) : $params['shop_bn'],
            'spSourceNo' => $params['shop_type'] ? $params['shop_type'] : 'OTHER',
            'shopName' => $params['name'],
            'contacts' => $params['default_sender'],
            'phone' => $params['mobile'] ? $params['mobile'] : $params['tel'],
            'province' => $params['province'],
            'city' => $params['city'],
            'district' => $params['district'],
            'addr' => $params['addr']
        );
        return $sdf;
    }

    /**
     * shop_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function shop_update($params) {
        if(empty($params)) {
            return $this->error('缺少参数');
        }

        $title = $this->__channelObj->wms['channel_name'] . '店铺('. $params['name'] .')更新';

        $sdf = $this->_format_create_params($params);
        $sdf['wmsShopNo'] = $params['wms_shop_bn'];

        $rs = $this->__caller->call(WMS_SHOP_UPDATE, $sdf,array(), $title, 10, $params['shop_bn']);
        
        return $rs;
    }
}
