<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wmsmgr_mdl_smart extends channel_mdl_channel
{
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_channel_channel';
        }else{
           $table_name = 'channel';
        }
        
        return $table_name;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        return app::get('channel')->model('channel')->get_schema();
    }
    
    function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        $where = " 1 ";
        
        if ($filter['not_o2o_node_type']) {
            $where.=" AND ".$filter['not_o2o_node_type'];
        }
        
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }
}