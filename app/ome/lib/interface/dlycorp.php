<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_interface_dlycorp{

    function __construct($app){
        $this->app = $app;
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        return $this->app->model('dly_corp')->getList($cols, $filter, $offset, $limit, $orderType);
    }

    public function count($filter=array()){
        return $this->app->model('dly_corp')->count($filter);
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
        return $this->app->model('dly_corp')->save($data);
    }

    /**
     * 更新
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function update($data, $filter){
        return $this->app->model('dly_corp')->update($data,$filter);
    }

    /**
     * 删除
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function delete($filter){
        return $this->app->model('dly_corp')->delete($filter);
    }
}