<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_vopreturn
{
    function __construct()
    {
        if($_REQUEST['action'] == 'exportcnf' || $_REQUEST['action'] == 'to_export'){
            unset($this->column_confirm);
        }
    }

    public $addon_cols = 'status,return_sn,shop_type';
    public $column_confirm = "操作";
    public $column_confirm_order = 1;
    public $column_confirm_width = 160;
    /**
     * column_confirm
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_confirm($row)
    {
        $itemBtn = <<<HTML
        <a href='index.php?app=console&ctl=admin_vopreturn&act=getItem&p[0]={$row['id']}&finder_id={$_GET["_finder"]["finder_id"]}'>获取明细</a>
HTML;
        $return_sn = $row[$this->col_prefix.'return_sn'];
        $shop_type = $row[$this->col_prefix.'shop_type'];
        
        $checkBtn = <<<HTML
        <a href='index.php?app=console&ctl=admin_vopreturn&act=check&p[0]={$row['id']}&finder_id={$_GET["_finder"]["finder_id"]}' target='_blank'>手工确认</a>
HTML;
    
        $importCheckBtn = <<<HTML
        <a href='index.php?app=console&ctl=admin_vopreturn&act=importCheck&p[0]={$row['id']}&finder_id={$_GET["_finder"]["finder_id"]}' target="dialog::{width:700,height:400,title:'唯品退供单【{$return_sn}】导入确认'}">导入确认</a>
HTML;
    
        $editBtn = <<<HTML
        <a href='index.php?app=console&ctl=admin_vopreturn&act=edit&p[0]={$row['id']}&finder_id={$_GET["_finder"]["finder_id"]}' target="_blank">编辑</a>
HTML;
        $btn = [];
        if($row[$this->col_prefix.'status'] == '0') {
            $itemObj = app::get('console')->model('vopreturn_items');
            if(!$itemObj->db_dump(['return_id'=>$row['id']],'id')) {
                $btn[] = $itemBtn;
            }
            $btn[] = $checkBtn;
            if ($shop_type == 'vop') {
                $btn[] = $importCheckBtn;
            }
            $btn[] = $editBtn;
        }
        if($row[$this->col_prefix.'status'] == '4') {
            $btn[] = $checkBtn;
            if ($shop_type == 'vop') {
                $btn[] = $importCheckBtn;
            }
        }
        
        return implode(' | ', $btn);
    }

    public $detail_items = '明细列表';
    /**
     * detail_items
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_items($id)
    {
        $render = app::get('console')->render();
        $items  = app::get('console')->model('vopreturn_items')->getList('*', array('return_id' => $id));
        foreach($items as $key => $val){
            $items[$key]['source_name'] = $val['source'] == 'local' ? '手工新增' : '平台获取';
        }
        $render->pagedata['items'] = $items;
        return $render->fetch('admin/vop/return_items.html');
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
        $logdata = $opObj->read_log(array('obj_id'=>$id,'obj_type'=>'vopreturn@console'), 0, 10);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['logs'] = $logdata;
        return $render->fetch('admin/vop/logs.html');
    }

    
}
