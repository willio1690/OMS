<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing 2018-06-27
 * @describe 商品分类相关请求
 */
class erpapi_wms_request_category extends erpapi_wms_request_abstract
{

    /**
     * category_getList
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function category_getList($params)
    {

        $sdf = $this->_format_create_params($params);

        switch ($params['level']) {
            case 1:
                $apiname = WMS_CATEGORIES_FIRST_LEVEL_GET;
                $title   = $this->__channelObj->channel['channel_name'] . '同步一级分类';
                break;
            case 2:
                $apiname = WMS_CATEGORIES_SECODE_LEVEL_GET;
                $title   = $this->__channelObj->channel['channel_name'] . '同步二级分类';
                break;
            case 3:
                $apiname = WMS_CATEGORIES_THIRD_LEVEL_GET;
                $title   = $this->__channelObj->channel['channel_name'] . '同步三级分类';
                break;
        }

        if (!$apiname) {
            return $this->error('分类未匹配到应接口名');
        }

        $rs = $this->__caller->call($apiname, $sdf, array(), $title, 10);

        if ($rs['rsp'] == 'succ') {
            $rs['data'] = json_decode($rs['data'], true);
        }

        return $rs;
    }

    protected function _format_create_params($params)
    {
        $sdf = array();
        if ($params['first_category_no']) {
            $sdf['first_category_no'] = $params['first_category_no'];
        }

        if ($params['second_category_no']) {
            $sdf['second_category_no'] = $params['second_category_no'];
        }

        if ($params['third_category_no']) {
            $sdf['third_category_no'] = $params['third_category_no'];
        }

        return $sdf;
    }
}
