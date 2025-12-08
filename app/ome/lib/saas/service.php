<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_saas_service {
    
    private $site;
    
    /**
     * __construct
     * @param mixed $site site
     * @return mixed 返回值
     */
    public function __construct(ome_saas_site &$site) {
        $this->site = $site;
    }
    
    /**
     * @获取ome_saas_site实例化的对象
     * @access public
     * @param void
     * @return object
     */
    public function getSite(){
        return $this->site;
    }
    
    /**
     * @获取服务到期的剩余天数
     * @access public
     * @param void
     * @return int
     */
    public function getValidityDate() {
        return $this->site->getManager ()->getValidityDate ();
    }

    /**
     * @获取服务基本信息
     * @access public
     * @param void
     * @return int
     */
    public function getInfo() {
        return $this->site->getManager ()->getInfo ();
    }
    
}