<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_finder_product_serial{
    
    public $addon_cols = 'status';//调用字段
    
    var $column_edit  = '操作';
    var $column_edit_order = 1;
    var $column_edit_width = '100';
    function column_edit($row){
        $serial_id = $row['serial_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $status = $row[$this->col_prefix.'status'];

        $btn_cancel = sprintf('<a href="javascript:if (confirm(\'你确定要将当前唯一码作废吗？\')){W.page(\'index.php?app=wms&ctl=admin_product_serial&act=cancel&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">作废</a>',$serial_id,$finder_id);

        $btn_renew = sprintf('<a href="javascript:if (confirm(\'你确定要将当前唯一码上架吗？\')){W.page(\'index.php?app=wms&ctl=admin_product_serial&act=renew&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">上架</a>',$serial_id,$finder_id);

        //已入库可作废
        if($status == 0){
            return $btn_cancel;
        }elseif($status == 2){
            //已作废可重新上架
            return $btn_renew;
        }elseif($status == 3){
            //已退入可作废或重新上架
            return $btn_renew."  |  ".$btn_cancel;
        }
    }

    var $detail_log = "操作日志";
    function detail_log($serial_id){
        $render = app::get('wms')->render();
        $oOperation_log = app::get('ome')->model('operation_log');
        $logdata = $oOperation_log->read_log(array('obj_type'=>'product_serial@wms','obj_id'=>$serial_id),0,-1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/product/serial/operation_log.html');
    }

}