<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class purchase_ctl_admin_pick_order_items extends desktop_controller
{
    var $title = 'JIT订单明细';
    var $workground = 'purchase_manager';
    
    private $_appName = 'purchase';
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
        
        $this->_mdl = app::get($this->_appName)->model('pick_order_items');
        
        //primary_id
        $this->_primary_id = 'item_id';
        
        //primary_bn
        $this->_primary_bn = 'order_sn';
    }
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $user = kernel::single('desktop_user');
        $actions = array();
        
        //filter
        $base_filter = array();
    
        //button
        $buttonList = array();
        $buttonList['dispose'] = array(
            'label' => '批量处理',
            'submit' => $this->url.'&act=batchDispose',
            'target' => 'dialog::{width:600,height:230,title:\'批量处理订单\'}'
        );
        
        //view
        $_GET['view'] = (empty($_GET['view']) ? '0' : $_GET['view']);
        switch ($_GET['view'])
        {
            case '1':
            case '2':
            case '3':
                $actions[] = $buttonList['dispose'];
                
                break;
            default:
                //---
        }
        
        //导出权限
        $use_buildin_export = false;
        
        //params
        $orderby = 'item_id DESC';
        $params = array(
            'title' => $this->title,
            'base_filter' => $base_filter,
            'actions'=> $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => $use_buildin_export,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'orderBy' => $orderby,
        );
        
        $this->finder('purchase_mdl_pick_order_items', $params);
    }
    
    /**
     * _views
     * @return mixed 返回值
     */
    public function _views()
    {
        //filter
        $base_filter = array();
        
        //menu
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'), 'filter'=>$base_filter, 'optional'=>false),
            1 => array('label'=>app::get('base')->_('未处理'), 'filter'=>array('status'=>array('none')), 'optional'=>false),
            2 => array('label'=>app::get('base')->_('处理中'), 'filter'=>array('status'=>array('running')), 'optional'=>false),
            3 => array('label'=>app::get('base')->_('处理失败'), 'filter'=>array('status'=>array('fail')), 'optional'=>false),
        );
        
        foreach($sub_menu as $k => $v)
        {
            if (isset($v['filter'])){
                $v['filter'] = array_merge($base_filter, $v['filter']);
            }
            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['href'] = 'index.php?app='. $_GET['app'] .'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$k;
            
            //第一个TAB菜单没有数据时显示全部
            if($k == 0){
                //count
                $sub_menu[$k]['addon'] = $this->_mdl->count($v['filter']);
                if($sub_menu[$k]['addon'] == 0){
                    unset($sub_menu[$k]);
                }
            }else{
                //count
                $sub_menu[$k]['addon'] = $this->_mdl->viewcount($v['filter']);
            }
        }
        
        return $sub_menu;
    }
    
    /**
     * 批量同步定制订单
     * 
     * @return void
     */
    public function batchDispose()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
        //post
        $ids = $_POST[$this->_primary_id];
        
        //check
        if($_POST['isSelectedAll'] == '_ALL_'){
            die('不能使用全选功能,每次最多选择500条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的订单!');
        }
        
        if(count($ids) > 500){
            die('每次最多只能选择500条!');
        }
        
        //data
        $filter = [$this->_primary_id=>$ids, 'status'=>['none','running','fail']];
        $dataList = $this->_mdl->getList('item_id,order_sn,good_sn,status', $filter, 0, -1);
        if(empty($dataList)){
            die('没有可操作的订单，请检查处理状态!');
        }
        
        $this->pagedata['GroupList'] = json_encode($ids);
        $this->pagedata['request_url'] = $this->url .'&act=ajaxDispose';
        
        //调用desktop公用进度条(第4个参数是增量传offset,否则默认一直为0)
        parent::dialog_batch($this->_appName . '_mdl_pick_order_items', false, 50, 'incr');
    }
    
    /**
     * ajaxDispose
     * @return mixed 返回值
     */
    public function ajaxDispose()
    {
        $jitOrderLib = kernel::single('console_inventory_orders');
        
        //setting
        $retArr = array(
            'itotal' => 0,
            'isucc' => 0,
            'ifail' => 0,
            'err_msg' => array(),
        );
        
        //获取查询条件
        parse_str($_POST['primary_id'], $postdata);
        
        //check
        if(empty($postdata['f'])) {
            echo 'Error: 请选择需要操作的订单!';
            exit;
        }
        
        //filter
        $filter = $postdata['f'];
        $offset = intval($postdata['f']['offset']);
        $limit = intval($postdata['f']['limit']);
        
        //check
        if(empty($filter)){
            echo 'Error: 没有找到查询条件';
            exit;
        }
        
        //data
        $dataList = $this->_mdl->getList('item_id,order_sn,good_sn,status', $filter, $offset, $limit);
        if(empty($dataList)){
            echo 'Error: 没有获取到订单';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        //list
        foreach ($dataList as $dataKey => $dataVal)
        {
            $item_id = $dataVal['item_id'];
            $order_sn = $dataVal['order_sn'];
            
            //check
            if(in_array($dataVal['status'], array('finish', 'needless'))){
                //fail
                $retArr['err_msg'][] = '订单：'. $order_sn .'，操作失败：状态不支持处理('. $dataVal['dispose_status'] .')';
                $retArr['ifail'] += 1;
                
                continue;
            }
            
            //exec
            $paramsFilter = array('item_id'=>$item_id);
            $result = $jitOrderLib->disposePickOrderItems($paramsFilter);
            if($result['rsp'] != 'succ'){
                //fail
                $retArr['err_msg'][] = $result['error_msg'];
                $retArr['ifail'] += 1;
            }else{
                //succ
                $retArr['isucc'] += 1;
            }
        }
        
        echo json_encode($retArr, JSON_UNESCAPED_UNICODE),'ok.';
        exit;
    }
}
?>