<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_mdl_server extends dbeav_model{

    function modifier_type($row){
        $type = o2o_conf_server::getTypeList($row);
        if($type){
            return $type['label'];
        }else{
            return '-';
        }
    }

    function pre_recycle($data)
    {
        $flag = true;
        $storeObj = app::get('o2o')->model('store');
        $arr_server = array();

        foreach($data as $val){
            $arr_server[] = $val['server_id'];
        }
        $row = $storeObj->getList('*',array('server_id' => $arr_server));
        if($row){
            $row2 = $this->getList('name',array('server_id' => $row[0]['server_id']));
            $this->recycle_msg = $row[0]['name'].'绑定了服务端：'.$row2[0]['name'].'，不能删除';
            $flag = false;
        }

        return $flag;
    }
}