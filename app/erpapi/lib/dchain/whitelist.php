<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/18
 * @Describe: 外部erp接口白名单
 */
class erpapi_dchain_whitelist
{
    private $whiteList;
    
    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        $this->whiteList = array(
            'taobao' => $this->taobao,
        );
    }
    
    /**
     * 获取WhiteList
     * @param mixed $nodeType nodeType
     * @return mixed 返回结果
     */
    public function getWhiteList($nodeType)
    {
        return $this->whiteList[$nodeType] ? array_merge($this->whiteList[$nodeType],
            $this->public_api) : $this->public_api;
    }
    
    #共有接口
    private $public_api = array();
    
    /**
     * 淘宝 RPC服务接口名列表
     * @access private
     */
    private $taobao = array();
}
