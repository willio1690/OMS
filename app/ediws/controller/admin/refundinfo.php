<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_ctl_admin_refundinfo extends desktop_controller{

    var $name = "售后退货";
    var $workground = "ediws_center";

    
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){


        $this->title = '退货单';
        $actions = array();
        
        $actions[] = array(
                'label' => '获取明细',
                'href' => $this->url .'&act=sync&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:550,height:350,title:'获取明细'}",
        );
       
        if(in_array($_GET['view'],array('5','8'))){
            
        }
         if(in_array($_GET['view'],array('5'))){
            
        }

        $params = array(
            'title'=>$this->title,
            'base_filter' =>array(),
            
            'orderBy' => 'refundinfo_id DESC',
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_filter'=>true,
            'actions'=>$actions,
        );
        
        $this->finder('ediws_mdl_refundinfo',$params);
    }


    
    /**
     * sync
     * @return mixed 返回值
     */
    public function sync() {
        
        $this->pagedata['start_time'] = strtotime('-7 days');
        $this->pagedata['end_time'] = time();
        $shop = app::get('ome')->model('shop')->getList('shop_id, name', ['node_type'=>'360buy','business_type'=>'jdlvmi']);

        $this->pagedata['shop'] = $shop;
        $this->pagedata['request_url'] = $this->url.'&act=do_sync';
        $this->display('syncrefundinfo.html');
    }

    /**
     * do_sync
     * @return mixed 返回值
     */
    public function do_sync() {
        $shop_id = $_POST['shop_id'];
        $warehouse = $_POST['warehouse'];
        $start_time   = $_POST['start_time'].' '.$_POST['_DTIME_']['H']['start_time'].':'.$_POST['_DTIME_']['M']['start_time'].':00';
        $end_time     = $_POST['end_time'].' '.$_POST['_DTIME_']['H']['end_time'].':'.$_POST['_DTIME_']['M']['end_time'].':00';
        $pageNo = (int) $_POST['page_no'];

        if (strtotime($start_time) >= strtotime($end_time)) {
            echo json_encode(array('total'=>0));exit;
        }
        
        $ret = array('total'=>0,'succ'=>0,'fail'=>0);
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        $bill_id = $_POST['bill_id'];
        $sdf = array('start_time'=>$start_time,'end_time'=>$end_time,'bill_id'=>$bill_id,'shop_id'=>$shop_id);
        
        $result = kernel::single('ediws_task_refundinfo')->getRefundinfo($sdf);
    
        if($result) {
            $ret['succ'] += 1;
        } else {
            $ret['fail'] += 1;
            $ret['err_msg'][] = $msg;
        }
        echo json_encode($ret);exit;
    }

    /**
     * batchsync
     * @return mixed 返回值
     */
    public function batchsync(){
        // $this->begin('');
        // kernel::database()->exec('commit');

        $refundinfo_ids = $_POST['refundinfo_id'];
        if (!empty($refundinfo_ids)) {
            foreach ($refundinfo_ids as  $refundinfo_id) {
                
            }
        }

        $this->splash('success', null, '命令已经被成功发送！！');
    }
    

    /**
     * batchsyncsap
     * @return mixed 返回值
     */
    public function batchsyncsap(){

        // $this->begin('');
        // kernel::database()->exec('commit');
       
        $refundinfo_ids = $_POST['refundinfo_id'];
        if (!empty($refundinfo_ids)) {
            foreach ($refundinfo_ids as  $refundinfo_id) {

                
            }
        }

        $this->splash('success', null, '命令已经被成功发送！！');
    }

    

    /**
     * batchsyncrefund
     * @return mixed 返回值
     */
    public function batchsyncrefund(){
        // $this->begin('');
        // kernel::database()->exec('commit');

        $db = kernel::database();
        $refundinfo_ids = $_POST['refundinfo_id'];
        if (!empty($refundinfo_ids)) {
            foreach ($refundinfo_ids as  $refundinfo_id) {
                
            }
        }

        $this->splash('success', null, '命令已经被成功发送！！');
    }
}