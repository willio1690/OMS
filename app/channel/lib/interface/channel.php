<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class channel_interface_channel{

    function __construct($app){
        $this->app = $app;
    }

    /**
     * dump
     * @param mixed $filter filter
     * @param mixed $field field
     * @param mixed $subSdf subSdf
     * @return mixed 返回值
     */
    public function dump($filter,$field = '*',$subSdf = null){
        return $this->app->model('channel')->dump($filter, $field, $subSdf);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        return $this->app->model('channel')->getList($cols, $filter, $offset, $limit, $orderType);
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
        return $this->app->model('channel')->save($data);
    }

    /**
     * 删除
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function delete($filter){
        return $this->app->model('channel')->delete($filter);
    }
}