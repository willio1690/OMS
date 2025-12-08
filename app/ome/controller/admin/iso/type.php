<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @describe: 出入库类型
 * ============================
 */
class ome_ctl_admin_iso_type extends desktop_controller {
    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[] = array(
                'label'  => '新增',
                'href'   => $this->url.'&act=addEdit',
                'target' => 'dialog::{width:670,height:450,title:\'新增\'}',
        );
        $params = array(
                'title'=>'出入库类型',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_recycle'=>true,
                'actions'=>$actions,
                'orderBy'=>'id desc',
        );
        
        $this->finder('ome_mdl_iso_type', $params);
    }

    /**
     * 添加Edit
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function addEdit($id=0) {
        $isoType = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(1);
        unset($isoType[11]);
        $out = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(0);
        foreach($out as $k => $v) {
            $isoType[$k] = $v;
        }
        $data = [];
        if($id) {
            $data = app::get('ome')->model('iso_type')->db_dump(['id'=>$id]);
        }
        $this->pagedata['data'] = $data;
        $this->pagedata['iso_type'] = $isoType;
        $this->display('admin/iso/type_add.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        $model = app::get('ome')->model('iso_type');
        if($_POST['id']) {
            $model->update(['bill_type_name'=>$_POST['bill_type_name']], ['id'=>$_POST['id']]);
            $this->splash('success', $this->url, '修改成功');
        }
        $inData = [
            'type_id' => $_POST['type_id'],
            'bill_type' => $_POST['bill_type'],
            'bill_type_name' => $_POST['bill_type_name'],
        ];
        if(in_array($inData['type'], ['7','70'])){
            if(taoguaniostockorder_mdl_iso::$bill_type[$inData['bill_type']]) {
                $this->splash('error', $this->url, $inData['type'].':是系统默认业务类型');
            }
            if(strpos($inData['bill_type'], 'oms_') === 0) {
                $this->splash('error', $this->url, $inData['type'].':不能以oms_开头');
            }
        }
        $in = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(1);
        if($in[$inData['type_id']]) {
            $inData['type_name'] = $in[$inData['type_id']];
        } else {
            $out = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(0);
            $inData['type_name'] = $out[$inData['type_id']];
        }
        $rs = $model->insert($inData);
        if($rs) {
            $this->splash('success', $this->url, '新建成功');
        }
        $this->splash('error', $this->url, '新建失败：'.$model->db->errorinfo());
    }

    /**
     * 获取BillType
     * @return mixed 返回结果
     */
    public function getBillType() {
        $type_id = (int) $_POST['type_id'];
        $model = app::get('ome')->model('iso_type');
        $data = $model->getList('bill_type, bill_type_name', ['type_id'=>$type_id]);
        echo json_encode(['data'=>$data]);
    }
}