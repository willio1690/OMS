<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface base_interface_model{
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null);
    public function count($filter=null);
    public function get_schema();
}
