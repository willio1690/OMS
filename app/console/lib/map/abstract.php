<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class console_map_abstract {

    abstract protected function _getAddress($id);

    abstract protected function _dealResult($data, $sdf);

    /**
     * 获取Location
     * @param mixed $id ID
     * @param mixed $update update
     * @return mixed 返回结果
     */
    public function getLocation($id, $update = true) {
        if (!defined('AMAP_WEB_KEY')) {
            return array(false, '未启用');
        }
        
        $amap_web_key = AMAP_WEB_KEY;
        if (empty($amap_web_key)) {
            return array(false, '未启用');
        }
        
        $sdf = $this->_getAddress($id);
        if(!$update && $sdf['location']) {
            return array(true, $sdf['location']);
        }
        if(!$sdf['address']) {
            return array(false, '没有地址');
        }
        $param = array(
            'key' => AMAP_WEB_KEY,
            'address' => $sdf['address'], #如：北京市朝阳区阜通东大街6号
            'city' => $sdf['city'], #如北京，不支持县级市
        );
        $uri = '/v3/geocode/geo';
        $api_url = 'https://restapi.amap.com' . $uri . '?' . http_build_query($param);
        kernel::log("request : \n" . $api_url);
        $http = new base_httpclient;
        $rsp = $http->get($api_url);
        kernel::log("response : \n" . $rsp);
        $rsp = json_decode($rsp, true);
        $data = array(
            'rsp' => $rsp['status'] == '1' ? 'succ' : 'fail',
            'msg' => '<a target="_blank" href="https://lbs.amap.com/api/webservice/guide/tools/info">' . $rsp['info'].'</a>',
            'location' => $rsp['geocodes'][0]['location'],
        );
        $this->_dealResult($data, $sdf);
        if($data['rsp'] == 'fail') {
            return array(false, '获取失败：' . $data['msg']);
        }
        return array(true, $data['location']);
    }
}