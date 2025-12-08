<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 经销店铺model类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.08
 */
class dealer_mdl_shop extends ome_mdl_shop
{
    function __construct($app)
    {
        parent::__construct(app::get('ome'));
    }
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        $table_name = 'shop';
        if($real){
            return kernel::database()->prefix.$this->app->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
    function searchOptions()
    {
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'name' => app::get('base')->_('店铺名称'),
            'shop_bn' => app::get('base')->_('店铺编码'),
        );
        
        return $Options = array_merge($parentOptions,$childOptions);
    }
    
//    function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null)
//    {
//        return parent::getList($cols, $filter, $offset, $limit, $orderby);
//    }
    
//    function _filter($filter,$tableAlias=null,$baseWhere=null)
//    {
//        @ini_set('memory_limit','512M');
//
//        $where = '1';
//
//        //店铺编码
//        if (isset($filter['shop_bn'])){
//            $where .= " AND shop_bn='". $filter['shop_bn'] ."'";
//
//            //unset
//            unset($filter['shop_bn']);
//        }
//
//        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
//    }
}