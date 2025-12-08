<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class warehouse_mdl_iso extends dbeav_model{
    
    var $has_many = array('iso_items' => 'iso_items');

    //model层新加扩展数据层
    function iso_items($iso_id) {
        $eoObj = $this->app->model("iso_items");
        $rows['items'] = $eoObj->getList('product_name as name,nums as num,bn,price',array('iso_id'=>$iso_id));
        $total_num = 0;
        $total_price = 0;
        foreach($rows['items'] as $v){
            $total_num += intval($v['num']);
            $total_price += intval($v['num'])*floatval($v['price']);
        }
        $rows['total_num'] = $total_num;
        $rows['total_price'] = $total_price;
        return $rows;
    }

}
