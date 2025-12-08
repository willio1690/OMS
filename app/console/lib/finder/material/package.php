<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/11/24 11:45:33
 * @describe: 类
 * ============================
 */
class console_finder_material_package {
    public $addon_cols = 'status,mp_bn,sync_status';

    public $column_edit = "操作";
    public $column_edit_width = "180";
    public $column_edit_order = "-1";
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row) {
        $btn = [];
        $mp_bn = $row[$this->col_prefix . 'mp_bn'];
        if(in_array($row[$this->col_prefix.'status'], ['1'])) {
            $btn[] = '<a class="lnk" target="_blank" 
                    href="index.php?app=console&ctl=admin_material_package&act=edit&id='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'">
                    编辑</a>';
            $btn[] = '<a class="lnk" href="javascript:if(confirm(\'确认单据'.$mp_bn.'?\')) {W.page(\'index.php?app=console&ctl=admin_material_package&act=singleConfirm&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'\');};">确认</a>';
            $btn[] = '<a class="lnk" 
                    href="javascript:if(confirm(\'取消单据'.$mp_bn.'?\')) {W.page(\'index.php?app=console&ctl=admin_material_package&act=cancel&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'\');};">取消</a>';
        }
        if(in_array($row[$this->col_prefix.'status'], ['2'])) {
            $btn[] = '<a class="lnk" 
                    href="javascript:if(confirm(\'取消单据'.$mp_bn.'?\')) {W.page(\'index.php?app=console&ctl=admin_material_package&act=cancel&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'\');};">取消</a>';

            if(in_array($row[$this->col_prefix.'sync_status'], ['1','3'])) {
                $btn[] = '<a class="lnk" 
                    href="javascript:if(confirm(\'同步单据'.$mp_bn.'?\')) {W.page(\'index.php?app=console&ctl=admin_material_package&act=sync&p[0]='.$row['id'].'&finder_id='.$_GET['_finder']['finder_id'].'\');};">同步</a>';
            }
        }
        return implode(' | ', $btn);
    }

    public $detail_item = "货品详情";
    /**
     * detail_item
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_item($id){
        $render = app::get('console')->render();
        $items = app::get('console')->model('material_package_items')->getList('*', ['mp_id'=>$id]);
        $details = app::get('console')->model('material_package_items_detail')->getList('*', ['mp_id'=>$id]);
        $render->pagedata['items'] = $items;
        $render->pagedata['details'] = $details;
        return $render->fetch('admin/material/package/items.html');
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
        $logdata = $opObj->read_log(array('obj_id'=>$id,'obj_type'=>'material_package@console'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('admin/oplog.html');
    }

    public $detail_useful = "有效期记录";
    /**
     * detail_useful
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_useful($id){
       
        $packageMdl = app::get('console')->model('material_package');
        $packages = $packageMdl->dump(array('id'=>$id,'mp_bn'));
        $render = app::get('console')->render();
        $usefulMdl = app::get('console')->model('useful_life_log');

        $usefuls = $usefulMdl->getlist('*',array('bill_type'=>'workorder','business_bn'=>$packages['mp_bn']));

        $render->pagedata['usefuls'] = $usefuls;

        return $render->fetch("admin/useful/iso.html");
    }
}