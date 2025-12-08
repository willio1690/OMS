<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/8/24 16:09:06
 * @describe: 第三方退货单
 * ============================
 */
class console_finder_wms_reship {
    public $addon_cols = 'wms_status,reship_status,order_type';

    /*public $column_edit = "操作";
    public $column_edit_width = "80";
    public $column_edit_order = "-1";
    public function column_edit($row) {
        $btn = [];
        if(in_array($row[$this->col_prefix.'wms_status'], ['FINISH'])
            && in_array($row[$this->col_prefix.'reship_status'], ['1','4'])
            && in_array($row[$this->col_prefix.'order_type'], ['WTJRK'])) {
            $btn[] = '<a class="lnk" target="dialog::{width:850,height:450,title:\'单据匹配\'}" 
                    href="index.php?app=console&ctl=admin_wms_reship&act=match&id='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'">
                    匹配</a>';
        }
        return implode('|', $btn);
    }*/

    public $detail_item = "货品详情";
    public function detail_item($id){
        $render = app::get('console')->render();
        $items = app::get('console')->model('wms_reship_items')->getList('*', ['wr_id'=>$id]);
        $render->pagedata['items'] = $items;
        return $render->fetch('admin/wms/reship_items.html');
    }

    public $detail_oplog = "操作记录";
    public function detail_oplog($id){
        $render = app::get('console')->render();
        $opObj  = app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>$id,'obj_type'=>'wms_reship@console'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/oplog.html');
    }
}