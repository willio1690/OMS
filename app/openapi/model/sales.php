<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_mdl_sales extends ome_mdl_sales{

    var $has_many = array(
       'sales_items' => 'sales_items',
    );

    function __construct($app){
        parent::__construct(app::get('ome'));
    }

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $table_name = "sales";
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

     function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){

        return parent::getList($cols, $filter, $offset, $limit, $orderby);

     }

    /**
     * modifier_shop_id
     * @param mixed $shop_id ID
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_shop_id($shop_id,$list,$row){
        static $shopList;

        if (isset($shopList)) {
            return $shopList[$shop_id];
        }

        $shopIds  = array_unique(array_column($list, 'shop_id'));
        $shopList = app::get('ome')->model('shop')->getList('shop_id,name', ['shop_id'=>$shopIds]);
        $shopList = array_column($shopList, 'name', 'shop_id');

        return $shopList[$shop_id];
    }

    /**
     * modifier_org_id
     * @param mixed $org_id ID
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_org_id($org_id,$list,$row){
        static $orgList;

        if (isset($orgList)) {
            return $orgList[$org_id];
        }

        $orgIds  = array_unique(array_column($list, 'org_id'));
        $orgList = app::get('ome')->model('operation_organization')->getList('org_id,name', ['org_id'=>$orgIds]);
        $orgList = array_column($orgList, 'name', 'org_id');

        return $orgList[$org_id];
    }


}

?>