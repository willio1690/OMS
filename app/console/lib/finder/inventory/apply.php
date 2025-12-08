<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_inventory_apply{
   
    var $detail_item = "详情";

   
    
    function detail_item($apply_id){
        $render = app::get('console')->render();
        $inv_aiObj = app::get('console')->model('inventory_apply_items');
        
        $count = $inv_aiObj->count(array('inventory_apply_id'=>$apply_id));
        if ($count > 20){
            $render->pagedata['many'] = 'true';
            $rows = $inv_aiObj->getList('*', array('inventory_apply_id'=>$apply_id), 0, 20);
        }else {
            $rows = $inv_aiObj->getList('*', array('inventory_apply_id'=>$apply_id), 0, -1);
        }
        $render->pagedata['apply_id'] = $apply_id;
        $render->pagedata['rows'] = $rows;
        return $render->fetch("admin/inventory/apply/item.html");
    }
    
    public $detail_oplog = "操作记录";
    /**
     * detail_oplog
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_oplog($id){
        $render = app::get('console')->render();
        $opObj  = app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>$id,'obj_type'=>'inventory_apply@console'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/oplog.html');
    }

    var $column_operation = '操作';
    var $column_operation_width = 70;
    /**
     * column_operation
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_operation($row){
        $apply_id = $row['inventory_apply_id'];
        $inv_aObj = app::get('console')->model('inventory_apply');
        $info = $inv_aObj->dump($apply_id);
        $id = $info['inventory_apply_id'];
        $fid = $_GET['_finder']['finder_id'];
        
        if (in_array($info['status'], ['unconfirmed', 'confirming'])){
            $return = ' <a href="index.php?app=console&ctl=admin_inventory_apply&act=do_confirm&p[0]='.$id.'&finder_id='.$fid.'" target="_blank">确认</a>';
            $return .= ' | '.sprintf('<a href="javascript:if (confirm(\'确认要关闭吗？\')){W.page(\'index.php?app=console&ctl=admin_inventory_apply&act=do_close&p[0]=%s&finder_id=%s\', $extend({method: \'get\'}, JSON.decode({})), this);}void(0);" target="">关闭</a>',$id,$fid);
        }

        return $return;
    }


    public $detail_useful = "有效期列表";
    /**
     * detail_useful
     * @param mixed $apply_id ID
     * @return mixed 返回值
     */
    public function detail_useful($apply_id){
        $render = app::get('console')->render();
        $inv_aiObj = app::get('console')->model('inventory_apply_items');
        $rows = $inv_aiObj->getList('batch,material_bn', array('inventory_apply_id'=>$apply_id), 0, -1);

      
        foreach($rows as &$v){

            $v['batch'] = json_decode($v['batch'],true);
            
        }


        $render->pagedata['batchs'] = $rows;

        return $render->fetch("admin/useful/item.html");

    }
    
}
?>
