<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class erpapi_channel_abstract
{
    /**
     * 路由 matrix|openapi|prism
     *
     * @var string
     **/
    protected $__adapter = '';


    /**
     * 请求平台
     *
     * @var string
     **/
    protected $__platform = '';
    protected $__platform_business = '';

    /**
     * 平台版本
     *
     * @var string
     **/
    protected $__ver = '1';

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function get_adapter()
    {
        return $this->__adapter;
    }

    /**
     * 请求平台
     *
     * @return void
     * @author 
     **/
    public function get_platform()
    {
        return $this->__platform;
    }
    /**
     * 请求平台业务
     *
     * @return void
     * @author 
     **/
    public function get_platform_business()
    {
        return (string)$this->__platform_business;
    }

    /**
     * 版本号
     *
     * @return void
     * @author 
     **/
    public function get_ver()
    {
        return $this->__ver;
    }

    /**
     * 初始化请求配置
     *
     * @return void
     * @author 
     **/
    abstract public function init($node_id,$channel_id);
}