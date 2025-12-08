<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 插件接口
 *
 * @author shiyao744@sohu.com
 * @version 0.1b
 */

interface ome_groupon_plugin_interface {

    /**
     * 执行入口
     *
     * @param Array $group 要处理的订单组
     * @return Array
     */
    public function process($data,$post);
    
    public function convertToRowSdf($row,$post);

}