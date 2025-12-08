<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class  organization_interface_store{

    function __construct($app){
        $this->app = $app;
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $filter['org_type'] = 2;
        return $this->app->model('organization')->getList($cols, $filter, $offset, $limit, $orderType);
    }

}