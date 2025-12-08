<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/4/23
 * Time: 17:45
 */
class purchase_ctl_admin_order_wait extends desktop_controller{
    public $name = "待寻仓订单";
    public $workground = "purchase_manager";

    /**
     * index
     * @return mixed 返回值
     */

    public function index(){
        //view
        $_GET['view'] = (empty($_GET['view']) ? '0' : $_GET['view']);
        $params =
            array(
                'title'=>$this->name,
                'actions' => [],
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>false,
                'orderBy' => 'ow_id desc',
            );
        if(in_array($_GET['view'], [0,1,2])){
            $params['actions'][] = [
                'label'   => '寻仓结果推送',
                'submit'  => 'index.php?app=purchase&ctl=admin_order_wait&act=feedbackDelivery',
                'target'  => 'dialog::{width:600,height:230,title:\'寻仓结果推送\'}'
            ];
        }
        $this->finder('purchase_mdl_order_wait', $params);
    }

    //tab展示
    /**
     * _views
     * @return mixed 返回值
     */
    public function _views()
    {
        $waitMdl  = $this->app->model('order_wait');

        $base_filter = [];

        $sub_menu = array(
            0  => array('label' => __('全部'), 'filter' => $base_filter, 'optional' => false),
            1  => array('label' => __('NEW'), 'filter' => array('status'=>'NEW'), 'optional' => false),
            2  => array('label' => __('CONFIRMING'), 'filter' => array('status' => 'CONFIRMING'), 'optional' => false),
            3  => array('label' => __('CONFIRMED'), 'filter' => array('status' => 'CONFIRMED'), 'optional' => false),
            4  => array('label' => __('ROLLBACK'), 'filter' => array('status' => 'ROLLBACK'), 'optional' => false),
        );

        $act = 'index';
        foreach ($sub_menu as $k => $v) {            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon']  = $v['addon'] ? $v['addon'] : $waitMdl->viewcount($v['filter']);
            $sub_menu[$k]['href']   = 'index.php?app=purchase&ctl=admin_order_wait&act=' . $act . '&view=' . $k;
        }
        
        return $sub_menu;
    }

    /**
     * feedbackDelivery
     * @return mixed 返回值
     */
    public function feedbackDelivery() {
        $this->pagedata['request_url'] = 'index.php?app=purchase&ctl=admin_order_wait&act=ajaxFeedbackDelivery';

        parent::dialog_batch('purchase_mdl_order_wait',false,10,0);
    }

    /**
     * ajaxFeedbackDelivery
     * @return mixed 返回值
     */
    public function ajaxFeedbackDelivery() {
        parse_str($_POST['primary_id'], $postdata);

        if (!$postdata['f']) { echo 'Error: 请先选择待寻仓订单';exit;}

        $retArr = array(
            'itotal'  => 0,
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        $orderWaitMdl = app::get('purchase')->model('order_wait');
        $orderWaitMdl->filter_use_like = true;

        $orderWait = $orderWaitMdl->getList('ow_id,order_bn',$postdata['f'],$postdata['f']['offset'],$postdata['f']['limit']);

        $retArr['itotal'] = count($orderWait);

        foreach ($orderWait as $ow) {
            list($rs, $msg) = kernel::single('purchase_branch')->feedbackDelivery($ow['ow_id']);

            if (!$rs) {
                $retArr['ifail']++;
                $retArr['err_msg'][] = $ow['order_bn'] . ':' . $msg;
                continue;
            }

            $retArr['isucc']++;
        }

        echo json_encode($retArr),'ok.';exit;
    }
}