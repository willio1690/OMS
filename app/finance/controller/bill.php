<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ctl_bill extends desktop_controller{
    var $flag = 'sale';
    /**
     * sale
     * @return mixed 返回值
     */
    public function sale(){
        $is_export = kernel::single('desktop_user')->has_permission('finance_export');#增加销售收退款单导出权限
        $this->title = '销售收退款单';
        $base_filter = array('fee_type_id'=>'0');
        $this->base_filter = $base_filter;
        $this->flag = 'sale';
        $params = array(
            'title'=>$this->title,
            'actions'=>array(
                array(
                    'label' => '批量审核记账',
                    'submit' => 'index.php?app=finance&ctl=bill&act=do_charge&finder_id='.$_GET['finder_id'],
                ),
                array(
                    'label' => '添加账单',
                    'href' => 'index.php?app=finance&ctl=bill&act=add_bill&flt=sale&finder_id='.$_GET['finder_id'],
                    'target' => "dialog::{width:300,height:350,title:'添加账单'}",
                ),
                array(
                    'label' => '拉取账单',
                    'href' => 'index.php?app=finance&ctl=bill&act=trade_search&flt=sale&finder_id='.$_GET['finder_id'],
                    'target' => "dialog::{width:550,height:150,title:'拉取账单'}",
                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'finder_aliasname'=>'ar_sale',
            'finder_cols'=>'column_edit,bill_bn,channel_name,member,trade_time,order_bn,fee_item,money,status,unconfirm_money,confirm_money,charge_status,charge_time,monthly_status',
            'base_filter' => $base_filter,
        );
        $this->finder('finance_mdl_bill',$params);
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $this->title = '销售费用单';
        $this->flag = 'unsale';
        $base_filter = array('fee_type_id|noequal'=>'1');
        $this->base_filter = $base_filter;
        $params = array(
            'title'=>$this->title,
            'actions'=>array(
                array(
                    'label' => '批量审核记账',
                    'submit' => 'index.php?app=finance&ctl=bill&act=do_charge&finder_id='.$_GET['finder_id'],
                ),
                array(
                    'label' => '添加费用单',
                    'href' => 'index.php?app=finance&ctl=bill&act=add_bill&finder_id='.$_GET['finder_id'],
                    'target' => "dialog::{width:300,height:350,title:'添加费用单'}",
                ),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'finder_aliasname'=>'ar_unsale',
            'finder_cols'=>'bill_bn,channel_name,fee_obj,member,trade_time,order_bn,fee_type,fee_item,money,charge_status,charge_time,monthly_status,column_edit',
            'base_filter' => $base_filter,
        );
        $this->finder('finance_mdl_bill',$params);
    }

    function _views(){
        if($this->flag == 'sale'){
            $sub_menu = $this->_viewsale();
        }else{
            $sub_menu = $this->_viewunsale();
        }
        return $sub_menu;
    }

    function _viewsale(){
        $arObj = $this->app->model('bill');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('fee_type_id'=>1),'addon'=>$arObj->count(array('fee_type_id'=>1)),'optional'=>false),
            1 => array('label'=>app::get('base')->_('未记账'),'filter'=>array('fee_type_id'=>1,'charge_status'=>0),'addon'=>$arObj->count(array('fee_type_id'=>1,'charge_status'=>0)),'optional'=>false),
            2 => array('label'=>app::get('base')->_('待核销'),'filter'=>array('fee_type_id'=>1,'status|noequal'=>2,'charge_status'=>1),'addon'=>$arObj->count(array('fee_type_id'=>1,'status|noequal'=>2,'charge_status'=>1)),'optional'=>false),
            3 => array('label'=>app::get('base')->_('已核销'),'filter'=>array('fee_type_id'=>1,'status'=>2),'addon'=>$arObj->count(array('fee_type_id'=>1,'status'=>2)),'optional'=>false),
        );
        return $sub_menu;
    }

    function _viewunsale(){
        $arObj = $this->app->model('bill');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('fee_type_id|noequal'=>1),'addon'=>$arObj->count(array('fee_type_id|noequal'=>1)),'optional'=>false),
            1 => array('label'=>app::get('base')->_('未记账'),'filter'=>array('fee_type_id|noequal'=>1,'charge_status'=>0),'addon'=>$arObj->count(array('fee_type_id|noequal'=>1,'charge_status'=>0)),'optional'=>false),
            2 => array('label'=>app::get('base')->_('已记账'),'filter'=>array('fee_type_id|noequal'=>1,'charge_status'=>1),'addon'=>$arObj->count(array('fee_type_id|noequal'=>1,'charge_status'=>1)),'optional'=>false),
        );
        return $sub_menu;
    }

    //批量记账
    /**
     * do_charge
     * @return mixed 返回值
     */
    public function do_charge(){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        $billObj = app::get('finance')->model('bill');
        if($_POST['isSelectedAll'] == '_ALL_'){
            $where = $billObj->_filter($_POST);
            $sql = 'select bill_id from sdb_finance_bill where '.$where;
            $rs = kernel::database()->select($sql);
            $ids = array();
            foreach($rs as $v){
                $ids['bill_id'][] =$v['bill_id'];
            }
        }else{
            $ids = $_POST;
        }
        $res = kernel::single('finance_bill')->do_charge($ids);
        if($res['status'] == 'fail'){
            $this->end(false,$res['msg'],'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        }
        $this->end(true,'操作成功！','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
    }

    //添加收退款单
    /**
     * 添加_bill
     * @return mixed 返回值
     */
    public function add_bill(){
        if($_GET['flt'] == 'sale'){
            $this->pagedata['fee_type_data'] = kernel::single('finance_bill')->get_fee_type_item_relation('sale');
        }else{ 
            $this->pagedata['fee_type_data'] = kernel::single('finance_bill')->get_fee_type_item_relation('unsale');
        }
        $this->pagedata['json'] = json_encode($this->pagedata['fee_type_data']);
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('bill/add.html');
    }

    //处理
    // ‘order_bn’=>’业务单据号bn’,
    //                     ‘member’=>’客户/会员或交易对方ID’,
    //                     ‘channel_id=>’渠道ID（店铺，仓库）’,
    //                     ‘channel_name’=>’渠道名称（店铺，仓库）’,
    //                     ‘trade_time’=>’单据的完成日期’,
    //                     ‘fee_obj’=>’费用对象’,
    //                     ‘money’=>’区分正负’,
    //                     ‘fee_item’=>’费用项名称’
    //                     'credential_number'=>'凭据号',
    /**
     * do_add_bill
     * @return mixed 返回值
     */
    public function do_add_bill(){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        
        $orderdata = kernel::single('finance_func')->order_is_exists($_POST['order_bn']);
        if($orderdata == false){
            $this->end(false,'业务单据号不存在');
        }
        $shopinfo = kernel::single('finance_func')->getShopByShopID($orderdata['shop_id']);
        $sdf=array(
            'order_bn'=>$_POST['order_bn'],
            'member'=>$_POST['member'],
            'channel_id'=>$shopinfo['shop_id'],
            'channel_name'=>$shopinfo['shop_name'],
            'trade_time'=>$_POST['trade_time'],
            'fee_obj'=>$_POST['fee_obj'],
            'money'=>$_POST['money'],
            'fee_item'=>$_POST['fee_item'],
            'credential_number'=>$_POST['credential_number'],
            'unique_id'=>kernel::single('finance_func')->unique_id(array($_POST['credential_number'],$_POST['fee_obj'],$_POST['fee_item'])),
        );
        $init_flag = kernel::single('finance_monthly_report')->get_init_time();
        if($init_flag['flag'] === 'false') $this->end(false,'未设置初始化完成，不能添加');
        $res = kernel::single('finance_bill')->do_save($sdf);
        if($res['status'] == 'fail'){
            $this->end(false,$res['msg']);
        }
        $this->end(true,'添加成功');
    }

    //核销
    /**
     * verificate
     * @return mixed 返回值
     */
    public function verificate(){
        $billObj = &app::get('finance')->model('bill');
        $bill_id = $_GET['bill_id'];
        $bill_data = kernel::single('finance_bill')->get_bill_by_bill_id($bill_id,'order_bn');
        $ar_data = kernel::single('finance_bill')->get_ar_by_bill_id($bill_id,'order_bn');
        $next_bill_id = kernel::single('finance_verification')->get_next_data($bill_id);
        $this->pagedata['bill_data'] = $bill_data;
        $this->pagedata['ar_data'] = $ar_data;
        $this->pagedata['bill_id'] = $bill_id;
        $this->pagedata['next_bill_id'] = $next_bill_id['bill_id'];
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        if(isset($_GET['flag']) && $_GET['flag'] == 'replace'){
            $this->pagedata['replace'] = true;
        }else{
            $this->pagedata['replace'] = false;
        }
        $html = $this->fetch('bill/verificate.html');
        echo $html;
    }

    //分类展示
    /**
     * sync_verificate
     * @return mixed 返回值
     */
    public function sync_verificate(){
        $bill_id = $_POST['bill_id'];
        $flag = $_POST['flag'];
        $bill_data = kernel::single('finance_bill')->get_bill_by_bill_id($bill_id,$flag);
        $ar_data = kernel::single('finance_bill')->get_ar_by_bill_id($bill_id,$flag);
        $data['bill_data'] = $bill_data;
        $data['ar_data'] = $ar_data;
        echo json_encode($data);
    }

    //收支单导入 - 账单导入
    /**
     * bill_import
     * @return mixed 返回值
     */
    public function bill_import(){
        // 显示tab
        $settingTabs = array(
            array('name' => '支付宝账单导入', 'file_name' => 'bill/import/zhifubao.html', 'app' => 'finance','order'=>'100'),
            // array('name' => '京东账单导入', 'file_name' => 'bill/import/jingdong.html', 'app' => 'finance','order'=>'200'),
            // array('name' => '一号店账单导入', 'file_name' => 'bill/import/yihaodian.html', 'app' => 'finance','order'=>'300'),
            // array('name' => '销售实收账单', 'file_name' => 'bill/import/normal.html', 'app' => 'finance','order'=>'400'),
            // array('name' => '销售应收账单', 'file_name' => 'bill/import/ar.html', 'app' => 'finance','order'=>'500'),
        );
        // 定位到具体的TAB
        if (isset($_GET['tab']) && isset($settingTabs[intval($_GET['tab'])])) $settingTabs[intval($_GET['tab'])]['current'] = true;
        $this->pagedata['settingTabs'] = $settingTabs;
        $get = kernel::single('base_component_request')->get_get();

        $this->pagedata['ctler'] = 'finance_mdl_bill';
        $this->pagedata['add'] = 'finance';

        unset($get['app'],$get['ctl'],$get['act'],$get['add'],$get['ctler']);
        $this->pagedata['data'] = $get;

        $ioType = array();
        foreach( kernel::servicelist('omecsv_io') as $aIo ){
            $ioType[$aIo->io_type_name] = '.'.$aIo->io_type_name;
        }

        
        $this->pagedata['ioType'] = $ioType;
       
        $this->page('bill/import.html');
    }

    //确认核销
    /**
     * do_verificate
     * @return mixed 返回值
     */
    public function do_verificate(){
        $this->begin('javascript:finderGroup["'.$_POST['finder_id'].'"].refresh();');
        if(empty($_POST['bill_id']) || empty($_POST['ar_id'])){
            $this->end(false,'应收单据或者实收单据不能为空');
        }
        $rs = kernel::single('finance_bill')->do_verificate($_POST['bill_id'],$_POST['ar_id'],$_POST['trade_time']);
        if($rs['status'] == 'fail'){
            $this->end(false,$rs['msg']);
        }
        $this->end(true,'操作成功');
    }

    //异步判断核销金额的大小
    /**
     * sync_do_verificate
     * @return mixed 返回值
     */
    public function sync_do_verificate(){
        if(empty($_POST['bill_id']) || empty($_POST['ar_id'])){
            $res = array('status'=>'fail','msg'=>'应收单据或者实收单据不能为空');
            echo json_encode($res);exit;
        }
        $res = kernel::single('finance_bill')->do_verificate($_POST['bill_id'],$_POST['ar_id'],$_POST['trade_time'],1);
        if($res['status'] == 'success'){
            switch ($res['msg_code']) {
                case '1':
                    $res['msg'] = '全额核销，是否确认？';
                    break;
                
                case '2':
                    $res['msg'] = '未核销应收合计大于实收，将按未核销金额由低到高的顺序核销，是否确认？';
                    break;
                case '3':
                    $res['msg'] = '未核销实收大于应收合计，实收账单将部分核销，是否确认？';
                    break;
            }
        }
        echo json_encode($res);
    }

    function findbill(){
        $filter = array('fee_type_id'=>'1','status|noequal'=>'2','charge_status'=>'1');
        $params = array(
            'title'=>'实收单据',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_view_tab' => false,
            'use_buildin_filter'=>true,
            'base_filter' => $filter,
        );
        $this->finder('finance_mdl_bill', $params);
    }

    /**
     * 获取bill
     * @return mixed 返回结果
     */
    public function getbill(){
        $billObj = &app::get('finance')->model('bill');
        $bill_id = $_POST['bill_id'];
        $data = $billObj->getList('*',array('bill_id'=>$bill_id));
        foreach($data as $k=>$v){
            $data[$k] = $v;
            $data[$k]['trade_time'] = date('Y-m-d',$v['trade_time']);
        }
        echo json_encode($data);
    }

    function findar(){
        $filter = array('status|noequal'=>'2','charge_status'=>'1');
        $params = array(
            'title'=>'应收单据',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_view_tab' => false,
            'use_buildin_filter'=>true,
            'base_filter' => $filter,
        );
        $this->finder('finance_mdl_ar', $params);
    }

    /**
     * 获取ar
     * @return mixed 返回结果
     */
    public function getar(){
        $arObj = &app::get('finance')->model('ar');
        $ar_id = $_POST['ar_id'];
        $data = $arObj->getList('*',array('ar_id'=>$ar_id));
        foreach($data as $k=>$v){
            $data[$k] = $v;
            $data[$k]['trade_time'] = date('Y-m-d',$v['trade_time']);
            $data[$k]['type'] = kernel::single('finance_ar')->get_name_by_type($v['type']);
        }
        echo json_encode($data);
    }

    /**
     * sync_do_charge
     * @return mixed 返回值
     */
    public function sync_do_charge(){
        $res = array('status'=>'succ','msg'=>'');
        $bill_id = $_POST['bill_id'];
        $id = array('bill_id'=>array('0'=>$bill_id));
        $res = kernel::single('finance_bill')->do_charge($id);
        if($res['status'] == 'fail') {
            $res = array('status'=>'fail','msg'=>'记账失败');
            echo json_encode($res);
            exit;
        }
        $billObj = &app::get('finance')->model('bill');
        $cols = 'bill_id,bill_bn,member,order_bn,trade_time,credential_number,fee_obj,fee_item,money,unconfirm_money,confirm_money,charge_status';
        $data = $billObj->getList($cols,array('bill_id'=>$bill_id));
        foreach($data as $k=>$v){
            $data[$k]['trade_time'] = date("Y-m-d",$v['trade_time']);
        }
        $res['msg'] = $data[0];
        echo json_encode($res);
    }

    function sync_do_cancel(){
        $id = $_POST['id'];
        $finder_id = $_GET['finder_id'];
        $data = array('res'=>'succ','msg'=>'','finder_id'=>$finder_id);
        $billObj = &app::get('finance')->model('bill');
        $rs = $billObj->delete(array('bill_id'=>$id));
        if(!$rs){
            $data = array('res'=>'fail','msg'=>'作废不成功');
            echo json_decode($data);exit;
        }
        echo json_encode($data);
    }

    /**
     * trade_search
     * @return mixed 返回值
     */
    public  function  trade_search(){
        
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('bill/trade_search.html');
    }

    /**
     * do_trade_search
     * @return mixed 返回值
     */
    public function do_trade_search(){
        $start_time = date("Y-m-d H:i:s",strtotime($_POST['start_time']));
        $end_time = date("Y-m-d H:i:s",strtotime($_POST['end_time']));
        $page_no = $_POST['page_no'];
        $alipay_order_no = $_POST['alipay_order_no'];
        $merchant_order_no = $_POST['merchant_order_no'];
        #店铺信息:淘宝已授权
        $funcObj = kernel::single('finance_func');
        $shop_list = $funcObj->taobao_shop_list();
        $shop_num = count($shop_list);
        $tmp_num = 0;
        if ($shop_list) {
            foreach ($shop_list as $key=>$shop) {
                 $data = kernel::single('erpapi_router_request')->set('shop', $shop['shop_id'])->finance_trade_search($start_time, $end_time, $page_no,100,$alipay_order_no,$merchant_order_no);
            }

            if ($data['rsp'] == 'succ' ) {
                $record_list = isset($data['data']) && $data['data'] ? $data['data']['total_records'] : '';
                if ($record_list && is_array($record_list)) {
                    #添加账单
                    kernel::single('finance_rpc_response_func_bill')->batch_trade_add($record_list, $shop['node_id']);
                }
            }
            $tmp_num += $data['data']['total_pages'];
            if($tmp_num==$page_no && $data['rsp']=='succ' && $shop_num==$key+1){
                echo 'finish@100';
            }elseif ($data['data']['total_pages']>$page_no && $data['rsp']=='succ'){
                $schedule = (100*$page_no)/$data['data']['total_results']*100/$shop_num;
                echo 'success@'.$schedule;
            }else{
                echo '获取失败';
            }
        }
    }
}