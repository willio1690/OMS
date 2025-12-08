<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class o2o_autostore_type_abstract{
    
    /**
     * 规则类型
     * 
     * @var Array
     */
    protected $type;

    /**
     * 获取TmplConf
     * @return mixed 返回结果
     */
    public function getTmplConf(){
        return $this->config['tmpl'];
    }
}