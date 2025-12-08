<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_ctl_admin_account_settlement_orders extends desktop_controller
{
    var $title = '结算单列表';
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
        
        $this->_mdl = app::get('ediws')->model('account_settlement_orders');
        
        //primary_id
        $this->_primary_id = 'oid';
        
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
        
        //action
        $_GET['view'] = intval($_GET['view']);
        $actions = array();
        
        if(in_array($_GET['view'], array('0','1','3'))){
            $actions[] = array(
                'label' => '生成财务账单',
                'submit' => $this->url .'&act=batchSales&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:600,height:200,title:'批量对勾选的单据生成财务账单'}",
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
        
        $this->finder('ediws_mdl_account_settlement_orders', $params);
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
            array('label'=>'未同步', 'filter'=>array('sync_status'=>array('0')), 'optional'=>false),
            array('label'=>'同步成功', 'filter'=>array('sync_status'=>array('1')), 'optional'=>false),
            array('label'=>'同步失败', 'filter'=>array('sync_status'=>array('2')), 'optional'=>false),
        );
        
        foreach($sub_menu as $k=>$v)
        {
            if (!is_null($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $this->_mdl->viewcount($v['filter']);
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
            die('不能使用全选功能,每次最多选择10条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的记录!');
        }
        
        //最多只支持100条
        if(count($ids) > 10){
            die('每次最多只能选择10条!');
        }
        
        //获取京东云仓店铺编码
        $error_msg = '';
        
        $this->pagedata['GroupList'] = json_encode($ids);
        
        $this->pagedata['request_url'] = $this->url .'&act=ajaxSales&view='.$_REQUEST['view'].'&finder_id='.$_REQUEST['finder_id'];
        
        //调用desktop公用进度条(第4个参数是增量传incr,否则默认一直为0)
        parent::dialog_batch('ediws_mdl_account_settlement_orders', false, 100, 'incr');
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
        $dataList = $this->_mdl->getlist('*',$filter);
        if(empty($dataList)){
            echo 'Error: 没有获取到数据';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        
        foreach ($dataList as $key => $row)
        {
           
           
            if($row['sync_status']=='1') continue;
            
            $data = array();
            $data[] = $row;
            list($rs,$msg) = kernel::single('ediws_accountsettlement')->process($data);
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
}