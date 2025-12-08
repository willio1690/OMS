<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_mdl_gift extends dbeav_model{
    
    public function filter($filter = array()){
        return parent::filter($filter);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null){
        return parent::getList($cols, $filter, $offset, $limit, $orderby);
    }

    public function count($filter = array()){
        return parent::count($filter);
    }

    /**
     * 保存
     * @param mixed $data 数据
     * @param mixed $mustUpdate mustUpdate
     * @return mixed 返回操作结果
     */
    public function save(&$data, $mustUpdate = NULL){
        return parent::save($data, $mustUpdate);
    }

}