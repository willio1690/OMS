<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_interface_branch{

    function __construct($app){
        $this->app = $app;
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        $filter['check_permission'] = 'false';
        return $this->app->model('branch')->getList($cols, $filter, $offset, $limit, $orderType);
    }

    public function count($filter=array()){
        $filter['check_permission'] = 'false';
        return $this->app->model('branch')->count($filter);
    }

    /**
     * 保存
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function save(&$data){
        if(!$data){
            return false;
        }
        return $this->app->model('branch')->save($data);
    }

    /**
     * 更新
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function update($data, $filter){
        $filter['check_permission'] = 'false';
        return $this->app->model('branch')->update($data,$filter);
    }

    /**
     * 删除
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function delete($filter){
        $filter['check_permission'] = 'false';
        return $this->app->model('branch')->delete($filter);
    }
}
