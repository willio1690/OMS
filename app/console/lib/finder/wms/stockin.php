<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/10/20 17:58:06
 * @describe: 第三方入库单
 * ============================
 */
class console_finder_wms_stockin {
    public $addon_cols = 'wms_status,iso_status';

    /*public $column_edit = "操作";
    public $column_edit_width = "80";
    public $column_edit_order = "-1";
    public function column_edit($row) {
        $btn = [];
        return implode('|', $btn);
    }*/

    public $detail_item = "货品详情";
    public function detail_item($id){
        $render = app::get('console')->render();
        $items = app::get('console')->model('wms_stockin_items')->getList('*', ['wsi_id'=>$id]);
        foreach ($items as $key => $value) {
            if($value['sn_list']) {
                $sn_list = json_decode($value['sn_list'],1);
                $sn_list['sn'] = is_array($sn_list['sn']) ? $sn_list['sn'] : [$sn_list['sn']];
                $items[$key]['sn_list'] = '唯一码：'.(is_array($sn_list['sn']) ? implode(', ', $sn_list['sn']) : $sn_list['sn']);
            }
            if($value['batch']) {
                $batch = json_decode($value['batch'], 1);
                $batch = isset($batch[0]) ? $batch : [$batch];
                $title = array_keys($batch[0]);
                array_unshift($batch, $title);
                $items[$key]['batch'] = $batch;
            }
        }
        $render->pagedata['items'] = $items;
        return $render->fetch('admin/wms/stockin_items.html');
    }

    public $detail_oplog = "操作记录";
    public function detail_oplog($id){
        $render = app::get('console')->render();
        $opObj  = app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>$id,'obj_type'=>'wms_stockin@console'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/oplog.html');
    }
}