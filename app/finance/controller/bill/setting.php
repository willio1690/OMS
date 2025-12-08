<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_bill_setting extends desktop_controller{


    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        //显示数据不取缓存数据，取表中的数据
    	// $feeitemObj = &app::get('finance')->model('bill_fee_item');
    	// $fee_item_tmp = $feeitemObj->getList('fee_item_id,fee_type_id,fee_item,inlay',array('delete'=>'false'));
     //    $fee_item = array();
     //    foreach($fee_item_tmp as $v){
     //        $fee_type = app::get('finance')->model('bill_fee_type')->getList('fee_type',array('fee_type_id'=>$v['fee_type_id']));
     //        $fee_item[$v['fee_type_id']]['name'] = $fee_type[0]['fee_type'];
     //        $fee_item[$v['fee_type_id']]['item'][$v['fee_item_id']]['inlay'] = $v['inlay'];
     //        $fee_item[$v['fee_type_id']]['item'][$v['fee_item_id']]['name']= $v['fee_item'];
     //        $fee_item[$v['fee_type_id']]['item'][$v['fee_item_id']]['fee_item_id']= $v['fee_item_id'];
     //    }

     //    $this->pagedata['fee_item'] = $fee_item;
     //    $this->page('bill/feemap.html');
        $params = array(
            'actions'=>array(
                array(
                    'label' => '同步费用科目',
                    'href' => 'index.php?app=finance&ctl=bill_setting&act=bill_account_get&flt=sale&finder_id='.$_GET['finder_id'],
                    'target' => "dialog::{width:550,height:100,title:'同步费用科目'}",
                ),
            ),
            'use_buildin_recycle' => false,
            'base_filter' => array(
                'fee_type_id|noequal' => '9',
            ),
        );

        $this->finder('finance_mdl_bill_fee_type',$params);
    }

    /**
     * bill_account_get
     * @return mixed 返回值
     */
    public  function  bill_account_get(){
        
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('bill/bill_account_get.html');
    }

    /**
     * do_bill_account_get
     * @return mixed 返回值
     */
    public function do_bill_account_get(){
        $funcObj = kernel::single('finance_func');
        $shop_list = $funcObj->shop_list(array('node_type'=>'taobao','tbbusiness_type'=>'B'));

        if ($shop_list) {
            foreach ($shop_list as $key=>$shop) {
                if(!$shop['node_id']){
                    continue;
                }else{
                    $shop_id= $shop['shop_id'];
                }
            }
            if($shop_id)
               kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_bill_account_get();
                echo 'finish@100';
        }
    }

    /**
     * 添加item
     * @param mixed $fee_type_id ID
     * @return mixed 返回值
     */
    public function additem($fee_type_id = ''){
        $this->pagedata['fee_type_id'] = $fee_type_id;
        $this->page('bill/feeitem_add.html');
    }

    /**
     * do_additem
     * @return mixed 返回值
     */
    public function do_additem(){
        if($_POST){
            $this->begin();
            $res =array('status'=>'succ');
            $type_id = $_POST['fee_type_id'];
            $fee_item = $_POST['name'];
            if(empty($fee_item)){
                $res=array('status'=>'fail','msg'=>'请输入费用项名称');
                $this->end(false,$res['msg'],'',$rs);
                return;
            }
            $rs = kernel::single('finance_bill')->is_exist_item_by_table($fee_item);
            if($rs == 'false'){
                $res=array('status'=>'fail','msg'=>'费用项已存在');
                $this->end(false,$res['msg'],'',$res);
                return;
            }
            $data = kernel::single('finance_bill')->add_fee_item($type_id,$fee_item);
            if(!$data){
                $res=array('status'=>'fail','msg'=>'保存不成功');
                $this->end(false,$res['msg'],'',$res);
                return;
            }
            $res =array('status'=>'succ','msg'=>'保存成功');
            $this->end(true,$res['msg'],'',$res);
        }
    }

    //删除自定义的费用项
    /**
     * do_delitem
     * @param mixed $fee_item_id ID
     * @return mixed 返回值
     */
    public function do_delitem($fee_item_id = ''){
        $this->begin();
        $res =array('status'=>'succ');
        $data['fee_item_id']= $fee_item_id ? $fee_item_id : $_POST['fee_item_id'];
        $data['delete'] = 'true';
        $feeitemObj = app::get('finance')->model('bill_fee_item');
        $rs = $feeitemObj->save($data);
        if(!$rs){
            $res =array('status'=>'fail','msg'=>'删除失败');
            $this->end(false,$res['msg'],'',$res);
        }
        $this->end(true,'','',$res);
    }
}