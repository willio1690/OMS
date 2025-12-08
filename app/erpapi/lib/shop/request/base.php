<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 获取平台基础信息
 *
 * @author wangbiao@shopex.cn
 * @version 2024.03.07
 */
class erpapi_shop_request_base extends erpapi_shop_request_abstract
{
    /**
     * 获取京东Token信息
     * 
     * @param $params
     * @return array
     */

    public function getNpsToken($params=null)
    {
        $error_msg = '平台未对接';
        $msgcode = '304';
        
        return $this->error($error_msg, $msgcode);
    }

    /**
     * 获取pdd 前端检测插件的初始化code
     * @Author: XueDing
     * @Date: 2024/12/4 2:32 PM
     * @param $params
     * @return array
     */
    public function getPageCode($params = [])
    {
        return $this->succ('暂无请求');
    }
}