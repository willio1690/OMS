<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_service_template{
    /**
     * get print template list
     * 获取定义的快递单打印项的配置列表
     * @return array();
     */
    public function getElements(){
       return kernel::single('wms_delivery_template')->defaultElements();
    }


    /**
     * get default print content
     * 获取快递单打印项的对应内容
     * @param unknown_type $value_list
     * @return string
     */
    public function getElementContent($value_list){
        return kernel::single('wms_delivery_template')->processElementContent($value_list);
    }
}