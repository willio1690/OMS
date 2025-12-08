<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_mdl_shop_stocksync extends dbeav_model {

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false)
    {
        $table_name = 'shop';
        if($real){
            return kernel::database()->prefix.app::get('ome')->app_id.'_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        $schema = app::get('ome')->model('shop')->get_schema();
        
        # 重新排序
        $schema['columns']['name']['order'] = 10;
        $schema['columns']['shop_type']['order'] = 20;
        $schema['columns']['bs_id']['label'] = '经销商';

        # 重新进行FINDER排序
        unset($schema['in_list'],$schema['default_in_list']);
        $schema['in_list'] = array(
            0 => 'name',    
            1 => 'shop_type',
            2 => 'bs_id',
        );

        $schema['default_in_list'] = array(
            0 => 'name',
            1 => 'shop_type',
            2 => 'bs_id',
        );

        return $schema;
    }

    /**
     * modifier_shop_type
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_shop_type($row)
    {
        $row = $row ? ome_shop_type::shop_name($row) : '';
        return $row;
    }

    /**
     * modifier_bs_id
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_bs_id($col)
    {
        $col = app::get('dealer')->model('business')->db_dump($col, 'name')['name'];
        return $col;
    }
}