<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_ctl_admin_account_orders extends desktop_controller
{
    var $title = '实销实结明细列表';
    var $workground = 'ediws_center';
    
    private $_mdl = null; //model类
    private $_primary_id = null; //主键ID字段名
    private $_primary_bn = null; //单据编号字段名
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        parent::__construct($app);
        
        $this->_mdl = app::get('ediws')->model('account_orders');
        
        //primary_id
        $this->_primary_id = 'ord_id';
        
        //primary_bn
        $this->_primary_bn = 'orderNo';
    }
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $actions = array();
        $base_filter = array();
        
        //view
        $_GET['view'] = intval($_GET['view']);
        
        //action
        if(in_array($_GET['view'], array('1', '3','4'))){
            $actions[] = array(
                'label' => '生成供销单据',
                'submit' => $this->url .'&act=batchSales&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:600,height:200,title:'批量对勾选的单据生成供销单据'}",
            );
        }
        if(in_array($_GET['view'], array('0'))){
            $actions[] = array(
                'label' => '拉取实销实结单',
                'href' => $this->url .'&act=batchPullc&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:550,height:350,title:'拉取实销实结单'}",
            );
        }
        
        //params
        $orderby = $this->_primary_id .' DESC';
        $params = array(
            'title' => $this->title,
            'base_filter' => $base_filter,
            'actions'=> $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'orderBy' => $orderby,
        );
        
        $this->finder('ediws_mdl_account_orders', $params);
    }
    
    /**
     * _views
     * @return mixed 返回值
     */
    public function _views()
    {
        $base_filter = array();
        $sub_menu = array(
            array('label'=>'全部', 'filter'=>$base_filter, 'optional'=>false),
            array('label'=>'销售未处理', 'filter'=>array( 'sync_status'=>array('0'), 'refType'=>'1002'), 'optional'=>false),
            array('label'=>'退货未处理', 'filter'=>array('sync_status'=>array('0'), 'refType'=>'12'), 'optional'=>false),
            array('label'=>'销售处理失败', 'filter'=>array('sync_status'=>array('2'), 'refType'=>'1002'), 'optional'=>false),
            array('label'=>'退货处理失败', 'filter'=>array( 'sync_status'=>array('2'), 'refType'=>'12'), 'optional'=>false),
            array('label'=>'其它', 'filter'=>array( 'refType|notin'=>array('1002','12')), 'optional'=>false),
            array('label'=>'已生成', 'filter'=>array( 'sync_status'=>array('1')), 'optional'=>false),
        );
        
        foreach($sub_menu as $k=>$v)
        {
            if (!is_null($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $this->_mdl->count($v['filter']);
            $sub_menu[$k]['href'] = $this->url .'&act='.$_GET['act'].'&flt='.$_GET['flt'].'&view='.$k;
        }
        
        return $sub_menu;
    }
    
    /**
     * 批量对勾选的单据生成京东销售单
     */
    public function batchSales()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
     
        
        //ids
        $ids = $_POST[$this->_primary_id];
        
        if($_POST['isSelectedAll'] == '_ALL_'){
            die('不能使用全选功能,每次最多选择100条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的记录!');
        }
        
        //最多只支持100条
        if(count($ids) > 100){
            die('每次最多只能选择100条!');
        }
        
        //获取京东云仓店铺编码
        $error_msg = '';
        
        $this->pagedata['GroupList'] = json_encode($ids);
        
        $this->pagedata['request_url'] = $this->url .'&act=ajaxSales&view='.$_REQUEST['view'].'&finder_id='.$_REQUEST['finder_id'];
        
        //调用desktop公用进度条(第4个参数是增量传incr,否则默认一直为0)
        parent::dialog_batch('ediws_mdl_account_orders', false, 100, 'incr');
    }
    
    /**
     * ajax生成京东销售单
     */
    public function ajaxSales()
    {
       
        //ret
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'err_msg' => array(),
        );
        
        //获取单据
        parse_str($_POST['primary_id'], $postdata);
        if(!$postdata){
            echo 'Error: 请先选择操作的单据';
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
        $dataList = $this->_mdl->getList('ord_id,refType,sync_status', $filter, $offset, $limit);
        if(empty($dataList)){
            echo 'Error: 没有获取到数据';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        //list
        $extendBns = array();
        foreach ($dataList as $key => $row)
        {
            $order_bn = $row['orderNo'];
            
           
            if($row['sync_status']=='1') continue;
            
            list($rs,$msg) = kernel::single('ediws_accountorders')->createBill($row['ord_id']);
            if($rs){
                //succ
                $retArr['isucc'] += 1;
            }else{
                $error_msg = $msg;
                
                //fail
                $retArr['ifail'] += 1;
                
            }
            
           
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }


    /**
     * 拉取结算单分页查询
     */
    public function batchPullc()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
        //供应商编码列表
        $accountLib = kernel::single('ediws_autotask_timer_accountorders');
        $vendorCodeList = $accountLib->getJdlwmiShop();
        $this->pagedata['shopList'] = $vendorCodeList;
        
        //开始日期(默认本月一号)
        $start_time = date('Y-m-d', strtotime('-2 day'));
        $this->pagedata['start_time'] = $start_time;
        
        //结束日期(默认为昨天)
        $end_time = date('Y-m-d', strtotime('-1 day'));
        $this->pagedata['end_time'] = $end_time;
        
        //help帮助信息
        $this->pagedata['help_msg'] = '*温馨提醒：只能拉取同一个月内的结算单。';
        
        //post url
        $this->pagedata['postUrl'] = $this->url .'&act=ajaxDownloadData';
        
        $this->display('common/download_datalist.html');
    }
    
    /**
     * ajax拉取结算单分页查询
     */
    public function ajaxDownloadData()
    {
        //check
        if(empty($_POST['startTime'])){
            $retArr['err_msg'] = array('请先选择开始日期');
            echo json_encode($retArr);
            exit;
        }
        
        if(empty($_POST['endTime'])){
            $retArr['err_msg'] = array('请先选择结束日期');
            echo json_encode($retArr);
            exit;
        }
        
        //setting
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'total' => 1,
            'err_msg' => array(),
        );
        
        //page
        $page = isset($_POST['nextPage']) && $_POST['nextPage'] > 1 ? $_POST['nextPage'] : 1;
        
        //时间范围
        $startTime = strtotime($_POST['startTime'].' 00:00:00');
        $endTime = strtotime($_POST['endTime'].' 00:00:00');
        
        //check
        if(date('Y-m', $startTime) != date('Y-m', $endTime)){
            $retArr['err_msg'] = array('开始日期与结束日期必须在同一个月内,请检查!');
            echo json_encode($retArr);
            exit;
        }
        
        if($endTime <= $startTime){
            $retArr['err_msg'] = array('结束时间必须大于开始时间,请检查!');
            echo json_encode($retArr);
            exit;
        }
        $shop_id = $_POST['shop_id'];
        //params
        $params = array(
            
            'start_time' => strtotime($_POST['startTime']), //开始日期(年-月-日 时:分:秒)
            'end_time'  => strtotime($_POST['endTime']), //结束日期(年-月-日 时:分:秒)
           
        );

        $shopMdl = app::get('ome')->model('shop');

        $shops = $shopMdl->dump(array('shop_id'=>$shop_id),'shop_bn');

        $params['shop_bn'] = $shops['shop_bn'];
    
        //request
        list($rs,$data) = kernel::single('ediws_autotask_timer_accountorders')->getPullList($params, $shop_id);

        if ($rs) {
            $retArr['itotal'] ++; //本次拉取记录数
            $retArr['isucc'] ++; //处理成功记录数
          
            $retArr['total'] = $data['total'] ? $data['total'] : 1; 
            
        } else {
            $error_msg = $rs['error_msg'];
            $retArr['err_msg'] = array($error_msg);
        }
        
        echo json_encode($retArr);
        exit;
    }
}
