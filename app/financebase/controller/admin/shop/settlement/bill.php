<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 账单控制层
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_ctl_admin_shop_settlement_bill extends desktop_controller
{

    // 流水单
    /**
     * index
     * @return mixed 返回值
     */

    public function index(){

        $params = array(
            'actions'=>[],
            'title'=>'店铺收支明细',
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>true,
            'use_buildin_filter'=>false,
            'use_buildin_setcol'=>true,

            // 'finder_aliasname'=>'base',
            // 'object_method'=>array('count'=>'count_order_bill','getlist'=>'getlist_order_bill'),
            'finder_cols'=>'column_edit,shop_id,trade_no,financial_no,out_trade_no,order_bn,trade_time,member,money,trade_type,remarks,bill_category',
            //'base_query_string'=>$base_query_string,
            //'base_filter'=>array('time_from'=>$this->_params['time_from'],'time_to'=>$this->_params['time_to'],'shop_id'=>$this->_params['shop_id']),
            'orderBy'=> 'id desc',
        );
        $this->finder('financebase_mdl_base', $params);
    }

    //基础数据
    /**
     * base
     * @return mixed 返回值
     */
    public function base(){
        
        if(!isset($_POST['time_from'])) $_POST['time_from'] = date('Y-m-01', strtotime(date("Y-m-d")));
        if(!isset($_POST['time_to'])) $_POST['time_to'] = date('Y-m-d', strtotime("$_POST[time_from] +1 month -1 day"));
        kernel::single('financebase_base')->set_params($_POST)->display();
    }

    //收支单导入 - 账单导入
    /**
     * bill_import
     * @return mixed 返回值
     */
    public function bill_import(){
        // 显示tab
        $settingTabs = array(
            array('name' => '支付宝账单导入', 'file_name' => 'admin/bill/import/alipay.html', 'type' => 'alipay','order'=>'100'),
            array('name' => '京东日账单导入', 'file_name' => 'admin/bill/import/360buy.html', 'type' => 'jd','order'=>'200'),
            array('name' => '抖音账单导入', 'file_name' => 'admin/bill/import/luban.html', 'type' => 'luban','order'=>'600'),
            array('name' => '有赞账单导入', 'file_name' => 'admin/bill/import/youzan.html', 'type' => 'youzan','order'=>'700'),
            array('name' => '拼多多账单导入', 'file_name' => 'admin/bill/import/pinduoduo.html', 'type' => 'pinduoduo','order'=>'800'),
            array('name' => '微信账单导入', 'file_name' => 'admin/bill/import/wechatpay.html', 'type' => 'wechatpay','order'=>'900'),
            // array('name' => '一号店账单导入', 'file_name' => 'bill/import/yihaodian.html', 'app' => 'finance','order'=>'300'),
            // array('name' => '销售实收账单', 'file_name' => 'bill/import/normal.html', 'app' => 'finance','order'=>'400'),
            // array('name' => '销售应收账单', 'file_name' => 'bill/import/ar.html', 'app' => 'finance','order'=>'500'),
            array('name' => '微信小店账单导入', 'file_name' => 'admin/bill/import/wxpay.html', 'type' => 'wxpay','order'=>'1300'),
            array('name' => '京东钱包导入', 'file_name' => 'admin/bill/import/jdwallet.html', 'type' => 'jdwallet','order'=>'1400'),
        );
        // 定位到具体的TAB
        if (isset($_GET['tab']) && isset($settingTabs[intval($_GET['tab'])])) $settingTabs[intval($_GET['tab'])]['current'] = true;
        $this->pagedata['settingTabs'] = $settingTabs;

        /*$get = kernel::single('base_component_request')->get_get();

        $this->pagedata['ctler'] = 'financebase_mdl_bill';
        $this->pagedata['add'] = 'financebase';

        unset($get['app'],$get['ctl'],$get['act'],$get['add'],$get['ctler']);
        $this->pagedata['data'] = $get;

        $ioType = array();
        foreach( kernel::servicelist('omecsv_io') as $aIo ){
            $ioType[$aIo->io_type_name] = '.'.$aIo->io_type_name;
        }

        
        $this->pagedata['ioType'] = $ioType;*/
        $this->pagedata['init_time'] = app::get('finance')->getConf('finance_setting_init_time');
        $this->pagedata['shop_list_alipay'] = financebase_func::getShopList(array('taobao','tmall'));
        $this->pagedata['shop_list_360buy'] = financebase_func::getShopList('360buy');
        $this->pagedata['shop_list_luban'] = financebase_func::getShopList('luban');
        $this->pagedata['shop_list_youzan'] = financebase_func::getShopList('youzan');
        $this->pagedata['shop_list_pinduoduo'] = financebase_func::getShopList('pinduoduo');
        $this->pagedata['shop_list_wechatpay'] = financebase_func::getShopList(['wx','weixinshop','website','youzan']);
        $this->pagedata['shop_list_wxpay'] = financebase_func::getShopList(['wx','youzan','wxshipin']);
        $this->page('admin/bill/import.html');
    }

    // 待核销列表
    /**
     * unverification
     * @return mixed 返回值
     */
    public function unverification()
    {

        $this->title = '待核销列表';
        $base_filter = array('status'=>1);
        $actions = array();


        $params = array(
            'title'=>$this->title,
            'actions' => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>false,
            'base_filter' => $base_filter,
       );

       $this->finder('financebase_mdl_bill_unverification',$params);
    }



    // 导出设置页
    /**
     * export
     * @return mixed 返回值
     */
    public function export(){
  
        $this->pagedata['shop_list'] = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['billCategory']= app::get('financebase')->model('expenses_rule')->getBillCategory();
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/bill/export.html');
    }

    // 检查是否允许导出
    /**
     * 检查Export
     * @return mixed 返回验证结果
     */
    public function checkExport(){
        $mdlBill = app::get('financebase')->model('bill');
        $bill_start_time = strtotime($_POST['time_from']);
        $bill_end_time = strtotime($_POST['time_to'])+86400;
        $filter = array('shop_id'=>$_POST['shop_id'],'trade_time|between'=>array($bill_start_time,$bill_end_time));
        $_POST['bill_status'] == 'succ' and $filter['disabled'] = 'false';
        $_POST['bill_status'] == 'fail' and $filter['disabled'] = 'true';
        $total_num = $mdlBill->count($filter);
        echo $total_num?1:0;
    }

    // 导出csv
    /**
     * doExport
     * @param mixed $shop_id ID
     * @param mixed $time_from time_from
     * @param mixed $time_to time_to
     * @param mixed $bill_status bill_status
     * @return mixed 返回值
     */
    public function doExport($shop_id,$time_from,$time_to,$bill_status='all'){

        set_time_limit(0);
        $oFunc = kernel::single('financebase_func');

        $node_type_ref = $oFunc->getConfig('node_type');
        $page_size = $oFunc->getConfig('page_size');

        $params['shop_id'] = trim($shop_id);
        $params['bill_start_time'] = strtotime($time_from);
        $params['bill_end_time'] = strtotime($time_to)+86400;
        $params['bill_status'] = $bill_status;

        $mdlBill = app::get('financebase')->model('bill');
        $filter = array('shop_id'=>$params['shop_id'],'trade_time|between'=>array($params['bill_start_time'],$params['bill_end_time']));
        if(isset($_GET['bill_category'])) {
            if($_GET['bill_category'] != 'all') {
                $undefined = app::get('financebase')->getConf('expenses.rule.undefined');
                if ($_GET['bill_category'] == $undefined['bill_category']) {
                    $filter['bill_category'] = "";
                } else {
                    $filter['bill_category'] = $_GET['bill_category'];
                }
            }
        }
        $bill_status == 'succ' and $filter['disabled'] = 'false';
        $bill_status == 'fail' and $filter['disabled'] = 'true';
        $total_num = $mdlBill->count($filter);
        if(!$total_num) exit('无数据');
        $shopInfo = app::get('ome')->model('shop')->getList('node_type',array('shop_id'=>$params['shop_id']),0,1);


        if($shopInfo)
        {
            $file_name = sprintf("%s账单_(%s至%s)",$node_type_ref[$shopInfo[0]['node_type']],$time_from,$time_to);
            $class_name = sprintf("financebase_data_bill_%s",$node_type_ref[$shopInfo[0]['node_type']]);
            if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)){
                $csv_title = $instance->getTitle();
                $csv_title['bill_category'] = '具体类别';
                $csv_title['order_bn'] = '订单号';
                $csv_title['order_create_date'] = '订单创建时间';

                header('Content-Type: application/vnd.ms-excel;charset=utf-8');
                header("Content-Disposition:filename=" . $file_name . ".csv");

                $fp = fopen('php://output', 'a');
                $csv_title_value = array_values($csv_title);
                foreach ($csv_title_value as &$v) $v = mb_convert_encoding($v, 'GBK', 'UTF-8');//$oFunc->strIconv($v,'utf-8','gbk');
                fputcsv($fp, $csv_title_value);
                 
                $id = 0;
                while (true) {

                    $data = $instance->getExportData($filter,$page_size,$id);
                  
                    if($data){
                        foreach ($data as &$v) {
                            if(!$v['bill_category']) {
                                $v['bill_category'] = '未识别类型';
                            }
                            $tmp = array();
                            foreach ($csv_title as $title_key => $title_val) {
                                $tmp[] = isset($v[$title_key]) ? mb_convert_encoding($v[$title_key], 'GBK', 'UTF-8') : '';
                            }
                            fputcsv($fp, $tmp);
                        }
                    }else{
                        break;
                    }

                }

                exit;
                
            }else{
                exit("未找到此节点类型:".$shopInfo[0]['node_type']);
            }
        }
    
    }

    // 重新匹配页面
    /**
     * rematch
     * @return mixed 返回值
     */
    public function rematch(){
        $this->pagedata['request_url'] = $this->url .'&act=doMatch';
        /* if($_POST['bill_category'] != 'all' && empty($_POST['id'])) {
            die('具体类别有选择需要一页页选择，不支持全选');
        } */
        $_POST = array_filter($_POST);
        $this->dialog_batch('financebase_mdl_base', false, 100, 'incr');
    }

    /**
     * doMatch
     * @return mixed 返回值
     */
    public function doMatch(){
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'err_msg' => array(),
        );
        
        //获取发货单号
        parse_str($_POST['primary_id'], $postdata);
        if(!$postdata){
            echo 'Error: 请先选择收支明细';
            exit;
        }
        
        //filter
        $filter = $postdata['f'];
        $offset = intval($postdata['f']['offset']);
        $limit = intval($postdata['f']['limit']);
        
        if(empty($filter)){
            echo 'Error: 没有找到查询条件';
            exit;
        }
        
        //data
        $dataList = app::get('financebase')->model('base')->getList('id,unique_id,shop_id,trade_no', $filter, $offset, $limit, 'id asc');
        
        //check
        if(empty($dataList)){
            echo 'Error: 没有获取到发货单';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
    
        $oFunc = kernel::single('financebase_func');
        $node_type_ref = $oFunc->getConfig('node_type');
        $shopInfo = app::get('ome')->model('shop')->getList('shop_id,shop_type',array('shop_id'=>array_unique(array_column($dataList, 'shop_id'))));
        $shopInfo = array_column($shopInfo, null, 'shop_id');
        //list
        foreach ($dataList as $key => $value) {
            if(empty($node_type_ref[$shopInfo[$value['shop_id']]['shop_type']])) {
                $retArr['ifail'] ++;
                $retArr['err_msg'][] = $value['trade_no'].':缺少店铺类型';
                continue;
            }
            $worker = "financebase_data_bill_".$node_type_ref[$shopInfo[$value['shop_id']]['shop_type']];
            $params = [];
            $params['shop_id'] = $value['shop_id'];
            $params['ids'] = [$value['unique_id']];
            $cursor_id = '';
            $errmsg = '';
            kernel::single($worker)->rematch($cursor_id,$params,$errmsg);
            if($errmsg) {
                $retArr['ifail'] ++;
                $retArr['err_msg'][] = $value['trade_no'].':'.$errmsg;
            } else {
                $retArr['isucc']++;
            }
        }
        echo json_encode($retArr),'ok.';
        exit;
    }

    //  重新设置订单号
    /**
     * 重置OrderBn
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function resetOrderBn($id)
    {
        $bill_info = app::get('financebase')->model("bill")->getList('order_bn,id,credential_number,trade_no,money',array('id'=>$id,'status|lthan'=>2),0,1);
        $this->pagedata['bill_info'] = $bill_info[0];
        $this->singlepage("admin/bill/reset_orderbn.html");
    }

    //  保存设置订单号
    /**
     * 保存OrderBn
     * @return mixed 返回操作结果
     */
    public function saveOrderBn()
    {
        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_bill&act=index');
        $oBill = app::get('financebase')->model("bill");
     
        $id = intval($_POST['id']);
        $order_bn = trim($_POST['order_bn']);
        if(!$id)
        {
            $this->end(false, "ID不存在");
        }

        $bill_info = app::get('financebase')->model("bill")->getList('order_bn,bill_bn',array('id'=>$id,'status|lthan'=>2),0,1);

        if(!$bill_info)
        {
            $this->end(false, "流水单不存在");
        }

        if(!$order_bn)
        {
            $this->end(false, "订单号不存在");
        }

        $bill_info = $bill_info[0];
        $bill_bn = $bill_info['bill_bn'];

        if($order_bn == $bill_info['order_bn'])
        {
            $this->end(false, "订单号没有改变");
        }


        if($oBill->update(array('order_bn'=>$order_bn),array('id'=>$id,'status|lthan'=>2)))
        {

            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }

    // 导出未匹配订单号
    /**
     * exportUnMatch
     * @return mixed 返回值
     */
    public function exportUnMatch(){

        $oFunc = kernel::single('financebase_func');

        $this->pagedata['platform_list'] = $oFunc->getShopPlatform();
       
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/bill/export_unmatch.html');
    }

    /**
     * doUnMatchExport
     * @param mixed $platform_type platform_type
     * @return mixed 返回值
     */
    public function doUnMatchExport($platform_type = 'alipay'){
        set_time_limit(0);

        $oFunc = kernel::single('financebase_func');
        $mdlBill = app::get('financebase')->model('bill');
        
        $platform_list = $oFunc->getShopPlatform();
        $page_size = $oFunc->getConfig('page_size');
        $filter = array('status'=>0,'platform_type'=>$platform_type);

        $class_name = sprintf("financebase_data_bill_%s",$platform_type);

        $file_name = sprintf("%s平台未匹配订单号[%s]",$platform_list[$platform_type],date('Y-m-d'));

        $shop_list = financebase_func::getShopList(financebase_func::getShopType());

        $shop_list = array_column($shop_list,null,'shop_id');



        if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)){

            $csv_title = $instance->getTitle();
            $csv_title['shop_id'] = '所属店铺';
            $csv_title['bill_bn'] = '单据编号';
            $csv_title['order_bn'] = '订单号';

            header('Content-Type: application/vnd.ms-excel;charset=utf-8');
            header("Content-Disposition:filename=" . $file_name . ".csv");

            $fp = fopen('php://output', 'a');
            $csv_title_value = array_values($csv_title);
            foreach ($csv_title_value as &$v) $v = $oFunc->strIconv($v,'utf-8','gbk');
            fputcsv($fp, $csv_title_value);

            $id = 0;
            while (true) {

                $data = $instance->getExportData($filter,$page_size,$id);

                if($data){
                    foreach ($data as &$v) {
                        $tmp = array();
                        $v['shop_id'] = isset($shop_list[$v['shop_id']]) ? $shop_list[$v['shop_id']]['name'] : '';
                        foreach ($csv_title as $title_key => $title_val) {
                            $tmp[] = isset($v[$title_key]) ? $oFunc->strIconv($v[$title_key],'utf-8','gbk')."\t" : '';
                        }
                        fputcsv($fp, $tmp);
                    }
                }else{
                    break;
                }

            }

            exit;

        }
 
    }

    // 导入未匹配订单号
    /**
     * importUnMatch
     * @return mixed 返回值
     */
    public function importUnMatch(){

        $oFunc = kernel::single('financebase_func');

        $this->pagedata['platform_list'] = $oFunc->getShopPlatform();
       
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/bill/import_unmatch.html');
    }

    /**
     * doUnMatchImport
     * @return mixed 返回值
     */
    public function doUnMatchImport()
    {

        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_bill&act=index');

        $platform_type = $_POST['platform_type'] ? $_POST['platform_type'] : 'alipay';

        if( $_FILES['import_file']['name'] && $_FILES['import_file']['error'] == 0 ){
            $file_type = substr($_FILES['import_file']['name'],strrpos($_FILES['import_file']['name'],'.')+1);
            if(in_array($file_type, array('csv','xls','xlsx'))){

                $ioType = kernel::single('financebase_io_'.$file_type);
                $oProcess = kernel::single('financebase_data_bill_'.$platform_type);
                $oFunc = kernel::single('financebase_func');


                $page_size = $oFunc->getConfig('page_size');

                $file_name = $_FILES['import_file']['tmp_name'];
                $file_info = $ioType->getInfo($file_name); 
                $total_nums = $file_info['row'];
                $page_nums = ceil($total_nums / $page_size);

                for ($i=1; $i <= $page_nums ; $i++) {
                    $offset = ($i - 1) * $page_size;
                    $data = $ioType->getData($file_name,0,$page_size,$offset,true); 

                    $oProcess->updateOrderBn($data);
                }

                $this->end(true, app::get('base')->_('更新成功'));
            }else{
                $this->end(false, app::get('base')->_('不支持此文件'));
            }

        }else{
            $this->end(false, app::get('base')->_('没有导入成功'));
        }
    }


    // 查看核销详情
    /**
     * detailVerification
     * @param mixed $order_bn order_bn
     * @return mixed 返回值
     */
    public function detailVerification($order_bn)
    {
        echo $order_bn;
    }

    /**
     * 店铺收支单编辑
     *
     * @return void
     * @author 
     **/
    public function edit($id)
    {

        $bill = app::get('financebase')->model('bill')->dump($id);

        $this->pagedata['bill'] = $bill;

        $this->display('admin/shop/settlement/bill/edit.html');
    }

    /**
     * 店铺收支单保存
     *
     * @return void
     * @author 
     **/
    public function save()
    {
        $this->begin();

        $affect_rows = 0;
        if ($_POST['bill_category']) {
            $affect_rows = app::get('financebase')->model('bill')->update(array ('disabled'=>'false','bill_category' => $_POST['bill_category'],'split_status'=>'0'), array ('id'=>intval($_POST['id'])));

        }

        $this->end($affect_rows);
    }

}
