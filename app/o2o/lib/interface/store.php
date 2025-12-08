<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_interface_store{

    function __construct($app){
        $this->app = $app;
    }

    /**
     * dump
     * @param mixed $filter filter
     * @param mixed $fields fields
     * @return mixed 返回值
     */
    public function dump($filter, $fields){
        $storeObj = $this->app->model('store');
        return $storeObj->dump($filter, $fields);
    }
}