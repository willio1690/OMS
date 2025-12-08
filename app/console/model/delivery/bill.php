<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 物流包裹单Model类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class console_mdl_delivery_bill extends dbeav_model
{
    var $defaultOrder = array('log_id',' DESC');
    
    //是否支持导出字段定义
    var $has_export_cnf = true;
    
    //导出的文件名
    var $export_name = '物流包裹单';
    
    var $ioTitle = array();
    var $export_flag = false;
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
            $table_name = 'sdb_ome_delivery_bill';
        }else{
            $table_name = 'delivery_bill';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('ome')->model('delivery_bill')->get_schema();
    }
    
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        $where = '';
        
        //多包裹号查询
        if($filter['package_bn'] && is_string($filter['package_bn']) && strpos($filter['package_bn'], "\n") !== false){
            $filter['package_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['package_bn']))));
        }
        
        //多订单号查询
        if($filter['order_bn'] && is_string($filter['order_bn']) && strpos($filter['order_bn'], "\n") !== false){
            $filter['order_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['order_bn']))));
        }
        
        //多发货单单号查询
        if($filter['delivery_bn'] && is_string($filter['delivery_bn']) && strpos($filter['delivery_bn'], "\n") !== false){
            $filter['delivery_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['delivery_bn']))));
        }
        
        //[兼容]已发货的状态
        if($filter['status'] == '1'){
            $filter['status'] = array('1', '4'); //签收状态也是已发货
        }
        
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }
    
}