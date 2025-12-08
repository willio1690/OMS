<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/20
 * @Describe: 外部erp 优仓 模型
 */
class dchain_mdl_branch extends channel_mdl_channel{
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false){
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
    public function get_schema(){
        return app::get('channel')->model('channel')->get_schema();
    }
    
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null) {
        $filter['channel_type'] = 'dchain';
        return parent::_filter($filter,$tableAlias=null,$baseWhere=null);
    }
}