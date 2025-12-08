<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_product_serial_history extends desktop_controller{

    var $name = "唯一码历史";

    var $workground = "aftersale_center";

    function index(){
        $actions = [];
        /* $actions[] = array(
                'label'  => '导出模板',
                'href'   => $this->url.'&act=exportTemplate',
                'target' => '_blank',
        );
        $actions[] = array(
                'label'  => '同步平台',
                'submit'   => $this->url.'&act=syncPlat',
                'target' => 'dialog::{title:\'同步平台\'}',
        ); */
       $params = array(
            'title'=>'唯一码历史',
            'actions'=>$actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>false,
            'use_buildin_importxls'=>false,
            'use_buildin_filter' => true,
            'base_filter' => $filter,
         );
       $this->finder('ome_mdl_product_serial_history',$params);
    }

    /**
     * exportTemplate
     * @return mixed 返回值
     */
    public function exportTemplate()
    {
        $row = app::get('ome')->model('product_serial_history')->getTemplateColumn();
        $lib = kernel::single('omecsv_phpexcel');
        $lib->newExportExcel(null, '唯一码导入模板', 'xls', $row);
    }

    function search(){
        $this->pagedata['tag'] = false;
        if($_POST['serial_number']){
            $serial_number = $_POST['serial_number'];
            $serial['merge'] = $this->app->getConf('ome.product.serial.merge');//false
            $serial['separate'] = $this->app->getConf('ome.product.serial.separate');//null
            if($serial['merge']=='true' && $pos = strpos($serial_number,$serial['separate'])){
                $serial_number = substr($serial_number,$pos+1);
            }

            $userObj = app::get('desktop')->model('users');
            $basicMaterialObj = app::get('material')->model('basic_material');
            $branchObj = $this->app->model('branch');
            $prdSerialHistoryObj = $this->app->model('product_serial_history');


            $data = $prdSerialHistoryObj->getList('*',array('serial_number'=>$serial_number), 0, -1, 'act_time desc');
            if($data){
                foreach($data as $key => $history){
                    $branch = $branchObj->dump($history['branch_id'],'name');
                    $data[$key]['branch_name'] = $branch['name'];

                    $data[$key]['act_type'] = $prdSerialHistoryObj->modifier_act_type($history['act_type']);
                    $data[$key]['bill_type'] = $prdSerialHistoryObj->modifier_bill_type($history['bill_type']);
                    $data[$key]['bill_no'] = $history['bill_no'];

                    if($val['act_owner'] == 16777215){
                        $data[$key]['act_owner'] = 'system';
                    }else{
                        $user = $userObj->dump($history['act_owner'],'name');
                        $data[$key]['act_owner'] = $user['name'];
                    }
                }
            }

            $this->pagedata['serial_number'] = $_POST['serial_number'];
            $this->pagedata['data'] = $data;
            $this->pagedata['tag'] = true;
        }
        $this->page("admin/product/serial/search.html");
    }

    /**
     * syncPlat
     * @return mixed 返回值
     */
    public function syncPlat() {

        if (!$_POST['history_id']) {
            die('暂不支持全选');
        }
        $rows = app::get('ome')->model('product_serial_history')->getList(
            'distinct bill_id', 
            ['history_id'=>$_POST['history_id'],'bill_type'=>'3','sync|noequal'=>'succ']);
        $billIds = array_column($rows, 'bill_id');
        $this->pagedata['GroupList']   = json_encode($billIds);
        $this->pagedata['request_url'] = $this->url.'&act=doSyncPlat';

        parent::dialog_batch();
    }
    
    function doSyncPlat()
    {
        $primary_id = explode(',', $_POST['primary_id']);
        if (!$primary_id) { echo 'Error: 请先选择数据';exit;}

        $retArr = array(
            'itotal'  => count($primary_id),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        //一单一单处理
        foreach ($primary_id as $id)
        {
            $rs = kernel::single('ome_event_trigger_shop_order')->order_serial_sync($id);
            if($rs['rsp'] == 'succ'){
                $retArr['isucc']++;
            }else{
                $retArr['ifail']++;
                $retArr['err_msg'][] = '同步失败：' . $rs['msg'];
            }
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }
}