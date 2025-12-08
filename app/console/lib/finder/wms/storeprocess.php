<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/12/5 10:21:25
 * @describe: 加工单
 * ============================
 */
class console_finder_wms_storeprocess {

    public $detail_item = "货品详情";
    public function detail_item($id){
        $render = app::get('console')->render();
        $materialItems = app::get('console')->model('wms_storeprocess_materialitems')->getList('*', ['wsp_id'=>$id]);
        $productItems = app::get('console')->model('wms_storeprocess_productitems')->getList('*', ['wsp_id'=>$id]);
        $render->pagedata['material_items'] = $materialItems;
        $render->pagedata['product_items'] = $productItems;
        return $render->fetch('admin/wms/storeprocess_items.html');
    }

    /*public $detail_oplog = "操作记录";
    public function detail_oplog($id){
        $render = app::get('console')->render();
        $opObj  = app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>$id,'obj_type'=>'wms_stockout@console'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/oplog.html');
    }*/
}