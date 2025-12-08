<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/4/24
 * Time: 9:43
 */
class purchase_finder_order_wait
{
    public $addon_cols = '';
    public $detail_detail = '详情';
    public $detail_detail_order = '10';
    /**
     * detail_detail
     * @param mixed $id ID
     * @return mixed 返回值
     */

    public function detail_detail($id){
        $render = app::get('purchase')->render();
        $render->pagedata['detail'] = app::get('purchase')->model('order_wait_items')->getList('*', array('ow_id'=>$id));;
        return $render->fetch("admin/order/wait_items.html");
    }

    public $detail_log = '日志';
    public $detail_log_order = '20';
    /**
     * detail_log
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_log($id){
        $render = app::get('purchase')->render();
        $logObj = app::get('ome')->model('operation_log');
        $logs = $logObj->read_log(array('obj_id'=>$id,'obj_type'=>'order_wait@purchase'),0,-1);

        $render->pagedata['logs'] = $logs;
        return $render->fetch("admin/order/log.html");
    }
}