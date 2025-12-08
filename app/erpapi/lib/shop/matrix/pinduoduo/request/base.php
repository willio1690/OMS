<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2024/12/4
 * @Describe: 获取拼多多平台基础信息
 */
class erpapi_shop_matrix_pinduoduo_request_base extends erpapi_shop_request_base
{
    /**
     * 获取拼多多 page code 信息
     *
     * @param $params
     * @return array
     */
    public function getPageCode($params = [])
    {
        $title       = $this->__channelObj->channel['name'] . '获取检测页面code获取';
        $original_bn = 'pinduoduo_get_page_code';

        $params['httpReferer'] = 'client';
        $params['userId']      = base_enterprise::ent_id();

        $result = $this->__caller->call(SHOP_ISV_PAGE_CODE, $params, [], $title, 10, $original_bn);

        // $result = json_decode($result, 1);
        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }
}
