<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/10/20 14:34:12
 * @describe: 控制器
 * ============================
 */
class console_ctl_admin_wms_stockchange extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[] = array(
            'label' => '生成调整单',
            'submit' => 'index.php?app=console&ctl=admin_wms_stockchange&act=generateAdjust',
            'target'  => 'dialog::{width:600,height:250,title:\'生成调整单\'}',
        );
        $params = array(
                'title'=>'第三方异动单',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'id desc',
        );
        
        $this->finder('console_mdl_wms_stock_change', $params);
    }

    /**
     * generateAdjust
     * @return mixed 返回值
     */
    public function generateAdjust() {
        $finder_id = $_GET['finder_id'];
        $filter = array(
            'adjust_status' => '1',
        );
        $filter = array_merge($filter, $_POST);
        $list = app::get('console')->model('wms_stock_change')->getList('id', $filter);
        if(empty($list)) {
            echo "<button id='close_btn'>未找到单据</button><script>;if(finderGroup['{$finder_id}']) finderGroup['{$finder_id}'].refresh.delay(100,finderGroup['{$finder_id}']);var oDialog = $('close_btn').getParent('.dialog').retrieve('instance');oDialog.close.delay(2000, oDialog);</script>";
            exit;
        }
        $GroupList = array_column($list, 'id');
        $this->pagedata['itemCount'] = count($GroupList);
        $this->pagedata['GroupList'] = json_encode($GroupList);
        $this->pagedata['request_url'] = 'index.php?app=console&ctl=admin_wms_stockchange&act=ajaxGenerateAdjust';
        parent::dialog_batch();
    }

    /**
     * ajaxGenerateAdjust
     * @return mixed 返回值
     */
    public function ajaxGenerateAdjust() {
        $itemIds = explode(',',$_POST['primary_id']);

        if (!$itemIds) { echo 'Error: 缺少调整单明细';exit;}

        $retArr = array(
            'itotal'  => count($itemIds),
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );
        foreach ($itemIds as $itemId) {
            list($rs, $rsData) = kernel::single('console_receipt_stockchange')->doAdjust($itemId);
            if($rs) {
                $retArr['isucc'] += 1;
            } else {
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = $rsData['msg'];
            }
        }

        echo json_encode($retArr),'ok.';exit;
    }
}