<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_mdl_recycle extends dbeav_model{

    function  save(&$data,$mustUpdate = null){
        $return = parent::save($data,$mustUpdate);
    }
    function modifier_app_key($app_key){
        $app = app::get('base')->model('apps');
        $rows = $app->getList('app_name',array('app_id'=>$app_key));
        return $rows[0]['app_name'];
    }
    function get_item_type(){
        $rows = $this->db->select('select distinct(item_type) from '.$this->table_name(true).' ');
        return $rows;
    }
}
