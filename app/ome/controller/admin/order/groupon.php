<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_order_groupon extends desktop_controller{

    var $name = "团购订单批量导入";
    var $workground = "order_groupon_center";
    
 	function index(){

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $base_filter['org_id'] = $organization_permissions;
        }

        $this->finder('ome_mdl_order_groupon',array(
            'title'=>'团购订单批量导入',
            'actions' => array(
        		//array('label'=>'导出模板','href'=>'index.php?app=ome&ctl=admin_order_groupon&act=exportOrderTemplate','target'=>'_blank')
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>false,
            'base_filter'            => $base_filter,
             'orderBy' =>'order_groupon_id DESC'
            ));
    }
    
    function import(){
        $shopObj = $this->app->model("shop");
        $filter = array('s_type'=>1);

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $filter['org_id'] = $organization_permissions;
        }

        $shopData = $shopObj->getList('shop_id,name,shop_type',$filter, 0, -1);
        $this->pagedata['shopData'] = $shopData;
        $this->pagedata['pluginList'] =  kernel::single('ome_groupon_import')->getPluginList();
        
        $oPayment = $this->app->model('payments');
        $aRet = $oPayment->getAccount();
        $aAccount = array('--使用已存在帐户--');
        foreach ($aRet as $v){
            $aAccount[$v['bank']."-".$v['account']] = $v['bank']." - ".$v['account'];
        }
        $this->pagedata['pay_account'] = $aAccount;
        
        $this->pagedata['typeList'] = ome_payment_type::pay_type();
        
        $payment = $oPayment->getMethods();
        $this->pagedata['payment'] = $payment;
        
        echo $this->page('admin/order/import/import.html');
    }
    
    function doImport(){
        $result = kernel::single('ome_groupon_import')->process($_POST);
        header("content-type:text/html; charset=utf-8");

         //团购订单批量导入操作日志
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'import',
            'params' => array(),
        );
        ome_operation_log::insert('order_groupon_bat_import', $logParams);
        if($result['rsp'] == 'succ'){
            echo json_encode(array('result' => 'succ', 'msg' =>'上传成功'));
        }else{
            echo json_encode(array('result' => 'fail', 'msg' =>(array)$result['res']));
        }
    }

    function exportOrderTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $oObj = kernel::single('ome_groupon_import');
        $title1 = $oObj->exportOrderTemplate();
        
        // 输出标题行
        echo '"'.implode('","',$title1).'"' . "\n";
        
        // 添加两条测试数据
        $testData1 = array(
            'TEST001',           // 订单号
            '张三',              // 收件人
            '广东省',            // 省
            '深圳市',            // 市
            '南山区',            // 区（县）
            '科技园南区科技中一路10号', // 收件人地址
            '13800138001',      // 手机
            '0755-12345678',    // 电话
            '顺丰快递',          // 快递公司
            '请尽快发货',        // 发货时间/备注
            '2024-01-15 10:30:00', // 购买时间
            '客户要求包装精美',  // 客户备注
            '15.00',            // 配送费用
            '商家备注信息',      // 商家备注
            'false',            // 货到付款
            'SM001',            // 销售物料编码
            '2',                // 数量
            '99.00'             // 单价
        );
        foreach($testData1 as $key=>$val){
            $testData1[$key] = kernel::single('base_charset')->utf2local($val);
        }
        
        $testData2 = array(
            '',                 // 订单号（空，表示追加商品到TEST001）
            '',                 // 收件人
            '',                 // 省
            '',                 // 市
            '',                 // 区（县）
            '',                 // 收件人地址
            '',                 // 手机
            '',                 // 电话
            '',                 // 快递公司
            '',                 // 发货时间/备注
            '',                 // 购买时间
            '',                 // 客户备注
            '',                 // 配送费用
            '',                 // 商家备注
            '',                 // 货到付款
            'SM002',            // 销售物料编码
            '1',                // 数量
            '199.00'            // 单价
        );
        foreach($testData2 as $key=>$val){
            $testData2[$key] = kernel::single('base_charset')->utf2local($val);
        }
        
        // 输出测试数据
        echo '"'.implode('","',$testData1).'"' . "\n";
        echo '"'.implode('","',$testData2).'"' . "\n";
    }
    
 }

?>