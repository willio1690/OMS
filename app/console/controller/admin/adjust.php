<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/7/29 14:08:52
 * @describe: 库存调整单
 * ============================
 */
class console_ctl_admin_adjust extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[] = array(
                'label'  => '新增',
                'href'   => $this->url.'&act=add',
                'target' => '_blank',
        );
        /*$actions[] = array(
                'label'  => '确认',
                'submit' => $this->url.'&act=confirm',
                'target' => 'dialog::{width:550,height:250,title:\'批量确认\'}',
        );
        $actions[] = array(
                'label'  => '取消',
                'submit' => $this->url.'&act=cancel&p[0]=0',
                'confirm' => '确定取消选中单据？',
                'target' => 'refresh',
        );*/
        $actions[] = array(
                'label'  => '导入模板',
                'href'   => $this->url.'&act=exportTemplate',
                'target' => '_blank',
        );
        if($_GET['init'] == 'wms') {
            $actions[] = array(
                'label'  => 'wms库存初始化',
                'href'   => $this->url.'&act=execlImportDailog&p[0]=adjust_init_wms',
                'target' => 'dialog::{width:500,height:300,title:\'wms库存初始化\'}',
            );
        }
        $base_filter                   = [];
        $base_filter['adjust_channel'] = 'branchadjust';

        $params = array(
                'title'=>'库存调整单',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>true,
                'use_buildin_import'=>true,
                'use_buildin_importxls'=>true,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'id desc',
                'base_filter' => $base_filter,
        );
        
        $this->finder('console_mdl_adjust', $params);
    }

    /**
     * exportTemplate
     * @return mixed 返回值
     */
    public function exportTemplate()
    {
        $row = app::get('console')->model('adjust')->getTemplateColumn();
        $lib = kernel::single('omecsv_phpexcel');
        $data = [
            ['增量/全量', '仓库编码','库存初始化/手工调账', '', '否','完成', '123456', 'code001', '1'],
            ['', '', '', '','','', '123456', 'code002', '-1'],
        ];
        $lib->newExportExcel($data, '库存调整单模板-'.date('Y-m-d'), 'xls', $row);
    }

    /**
     * 添加
     * @return mixed 返回值
     */
    public function add() {
        $branchList = app::get('ome')->model('branch')->getList('branch_id,name', [
            'b_type' => '1',
            'is_ctrl_store'=>'1',
        ]);

        $branchOptions = [];
        foreach ($branchList as $branch) {
            $branchOptions[$branch['branch_id']] = $branch['name'];
        }

        $this->pagedata['branchOptions'] = $branchOptions;

       
        $this->singlepage('admin/adjust/add.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        $sn = [];
        if($_POST['sn']) {
            foreach($_POST['sn'] as $k => $val) {
                if($val) {
                    $sn[$k] = explode(',', $val);
                }
            }
        }
        $data = [
            'adjust_mode' => $_POST['adjust_mode'],
            'is_check' => '0',//调整自动完成
            'iso_status'=>'confirm',//调整自动完成
            'branch_id' => $_POST['branch_id'],
            'negative_branch_id' => [$_POST['branch_id']],
            'memo' => $_POST['memo'],
            'source'=>'手工新增',
            'items' => $_POST['number'],
            'sn' => $sn,
        ];
       
        list($rs, $rsData) = kernel::single('console_adjust')->dealSave($data);
        $this->splash(($rs?'success':'error'), null, $rsData['msg']);
    }

    /**
     * confirm
     * @return mixed 返回值
     */
    public function confirm() {
        $ids = $_POST['id'];
        $finder_id = $_GET['finder_id'];
        if(empty($ids)) {
            echo "<button id='close_btn'>不支持全选关闭</button><script>;finderGroup['{$_GET['finder_id']}'].refresh.delay(100,finderGroup['{$_GET['finder_id']}']);var oDialog = $('close_btn').getParent('.dialog').retrieve('instance');oDialog.close.delay(2000, oDialog);</script>";
            exit;
        }
        $list = app::get('console')->model('adjust')->getList('id, adjust_bn', ['id'=>$ids, 'bill_status'=>["1", "2"]]);
        if(empty($list)) {
            echo "<button id='close_btn'>未选择可用数据关闭</button><script>;finderGroup['{$finder_id}'].refresh.delay(100,finderGroup['{$finder_id}']);var oDialog = $('close_btn').getParent('.dialog').retrieve('instance');oDialog.close.delay(2000, oDialog);</script>";
            exit;
        }
        $this->pagedata['data'] = $list;
        $this->display('admin/adjust/confirm.html');
    }

    /**
     * singleConfirm
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function singleConfirm($id) {
        $finder_id = $_GET['finder_id'];
        $adjust = app::get('console')->model('adjust')->db_dump($id, 'id, adjust_bn, bill_status,source');
        if(empty($adjust)) {
            echo "<button id='close_btn'>未选择可用数据关闭</button><script>;if(finderGroup['{$finder_id}']) finderGroup['{$finder_id}'].refresh.delay(100,finderGroup['{$finder_id}']);var oDialog = $('close_btn').getParent('.dialog').retrieve('instance');oDialog.close.delay(2000, oDialog);</script>";
            exit;
        }
        if(!in_array($adjust['bill_status'], ['1','2'])) {
           echo "<button id='close_btn'>{$adjust['adjust_bn']}不可确认关闭</button><script>;if(finderGroup['{$finder_id}']) finderGroup['{$finder_id}'].refresh.delay(100,finderGroup['{$finder_id}']);var oDialog = $('close_btn').getParent('.dialog').retrieve('instance');oDialog.close.delay(2000, oDialog);</script>";
            exit;
        }

        
        $filter = array(
            'adjust_id' => $id,
            'adjust_status' => '0',
        );
        $list = app::get('console')->model('adjust_items')->getList('id', $filter);
        if(empty($list)) {
            app::get('console')->model('adjust')->update(['bill_status'=>'4'], ['id'=>$id]);
            echo "<button id='close_btn'>{$adjust['adjust_bn']}已完成关闭</button><script>;if(finderGroup['{$finder_id}']) finderGroup['{$finder_id}'].refresh.delay(100,finderGroup['{$finder_id}']);var oDialog = $('close_btn').getParent('.dialog').retrieve('instance');oDialog.close.delay(2000, oDialog);</script>";
            exit;
        }
        $GroupList = array_column($list, 'id');
        $this->pagedata['adjust'] = $adjust;
        $this->pagedata['custom_html'] = $this->fetch('admin/adjust/single_confirm.html');
        $this->pagedata['request_url'] = $this->url.'&act=doSingleConfirm';
        $this->pagedata['itemCount'] = count($GroupList);
        $this->pagedata['GroupList'] = json_encode($GroupList);
        $this->pagedata['maxNum']    = 100000;
        $this->pagedata['startNow']  = (int)$_GET['start'];
        parent::dialog_batch();
    }

    /**
     * doSingleConfirm
     * @return mixed 返回值
     */
    public function doSingleConfirm() {
        $itemIds = explode(',',$_POST['primary_id']);

        if (!$itemIds) { echo 'Error: 缺少调整单明细';exit;}

        $retArr = array(
            'itotal'  => count($itemIds),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        $main = app::get('console')->model('adjust')->db_dump($_POST['adjust_id']);
        $ckrs = true;
        //需判断如果是门店
        if($main['source'] == 'store'){
            $main['iso_status'] = 'confirm';
            
        }
        if($ckrs){
            list($rs, $rsData) = kernel::single('console_adjust')->confirmItems($itemIds, $main);
        }
        
        if($rs) {
            $retArr['isucc'] += count($itemIds);
        } else {
            $retArr['ifail'] += $rsData['fail_num'];
            $retArr['isucc'] += $retArr['itotal'] - $rsData['fail_num'];
            $retArr['err_msg'][] = $rsData['msg'];
        }

        echo json_encode($retArr),'ok.';exit;
    }

    /**
     * cancel
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function cancel($id) {
        $ids = $id ? [$id] : $_POST['id'];
        if(empty($ids)) {
            $this->splash('error', $this->url, '不支持全选');
        }
        $adjustMdl = app::get('console')->model('adjust');
        $adjustItemsMdl = app::get('console')->model('adjust_items');
        $list = $adjustMdl->getList('id, adjust_bn, branch_id,source', ['id'=>$ids, 'bill_status'=>["1", "2"]]);
        foreach ($list as $key => $value) {
            if($value['source'] == 'store'){
                
            
            }
            $rs = $adjustMdl->update(['bill_status'=>'3'], ['id'=>$value['id'], 'bill_status'=>['1','2']]);
            if(!is_bool($rs)) {
                app::get('ome')->model('operation_log')->write_log('adjust@console',$value['id'],"操作取消");
            }
        }
        $this->splash('success', $this->url, '操作完成');
    }
}