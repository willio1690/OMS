<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 福袋组合管理
 *
 * @author wangbiao@shopex.cn
 * @version 2024.09.10
 */
class material_ctl_admin_fukubukuro_combine extends desktop_controller
{
    var $title = '福袋组合';
    var $workground = 'goods_manager';
    
    private $_mdl = null; //model类
    private $_combineLib = null; //Lib类
    
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
        
        $this->_mdl = app::get('material')->model('fukubukuro_combine');
        
        $this->_combineLib = kernel::single('material_fukubukuro_combine');
        
        //primary_id
        $this->_primary_id = 'combine_id';
        
        //primary_bn
        $this->_primary_bn = 'combine_bn';
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
        $buttonList['add'] = array('label'=>'新建福袋组合', 'href'=>$this->url.'&act=addCombine');
        $buttonList['import'] = array('label'=>'导入', 'href'=>$this->url.'&act=execlImportDailog&p[0]=fukubukuro_combine', 'target'=>'dialog::{width:500,height:300,title:\'导入\'}');
        $buttonList['import_update'] = array(
            'label' => '更新导入',
            'href' => $this->url.'&act=importUpdateExcel&p[0]=fukubukuro_combine',
            'target' => 'dialog::{width:500,height:300,title:\'更新导入\'}'
        );
        
        //view
        $_GET['view'] = (empty($_GET['view']) ? '0' : $_GET['view']);
        switch ($_GET['view'])
        {
            case '0':
                //新建
                if($user->has_permission('material_fukubukuro_add')){
                    $actions[] = $buttonList['add'];
                }
                
                break;
        }
        
        //导入权限
        if(kernel::single('desktop_user')->has_permission('material_fukubukuro_import')) {
            //导入福袋组合
            $actions[] = $buttonList['import'];
            
            //更新导入福袋组合
            $actions[] = $buttonList['import_update'];
        }
        
        //导出权限
        $use_buildin_export = false;
        if(kernel::single('desktop_user')->has_permission('material_fukubukuro_export')) {
            $use_buildin_export = true;
        }
        
        //params
        $orderby = 'create_time DESC';
        $params = array(
            'title' => $this->title,
            'base_filter' => $base_filter,
            'actions'=> $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_import' => false,
            'use_buildin_export' => $use_buildin_export,
            'use_buildin_filter' => true,
            'orderBy' => $orderby,
        );
        
        $this->finder('material_mdl_fukubukuro_combine', $params);
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
            1 => array('label'=>app::get('base')->_('全部'), 'filter'=>$base_filter, 'optional'=>false),
            2 => array('label'=>app::get('base')->_('已删除'), 'filter'=>array('is_delete'=>'true'), 'optional'=>false),
        );
        
        foreach($sub_menu as $k => $v)
        {
            if (isset($v['filter'])){
                $v['filter'] = array_merge($base_filter, $v['filter']);
            }
            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['href'] = 'index.php?app='. $_GET['app'] .'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$k;
            
            //count
            $sub_menu[$k]['addon'] = $this->_mdl->viewcount($v['filter']);
        }
        
        return $sub_menu;
    }
    
    /**
     * 新建福袋组合
     * 
     * @param void
     * @return void
     */
    public function addCombine()
    {
        $this->page('admin/fukubukuro/add_combine.html');
    }
    
    /**
     * 编辑福袋组合
     * 
     * @param void
     * @return void
     */
    public function editCombine($combine_id)
    {
        //获取信息
        $masterInfo = $this->_mdl->dump(array($this->_primary_id=>$combine_id), '*');
        
        //items
        $error_msg = '';
        $itemList = $this->_combineLib->formatCombineItems($combine_id, $error_msg);
        
        $this->pagedata['data'] = $masterInfo;
        $this->pagedata['items'] = $itemList;
        
        $this->page('admin/fukubukuro/add_combine.html');
    }
    
    /**
     * 保存福袋组合
     * 
     * @param void
     * @return void
     */
    public function saveCombine()
    {
        $this->begin($this->url.'&act=index');
        
        //兼容
        if(isset($_POST['rates'])){
            foreach ($_POST['rates'] as $rateKey => $rateVal)
            {
                if($rateVal == "0"){
                    $_POST['rates'][$rateKey] = '随机';
                }
            }
        }
        
        //setting
        $error_msg = '';
        
        //数据有效性检查
        $isCheck = $this->_combineLib->checkParams($_POST, $error_msg);
        if(!$isCheck) {
            $this->end(false, '福袋编码：'. $_POST['combine_bn'] .'数据校验失败：'. $error_msg);
        }
        
        //格式化数据
        $isFormat = $this->_combineLib->formatData($_POST, $error_msg);
        if(!$isFormat) {
            $this->end(false, '福袋编码：'. $_POST['combine_bn'] .'格式化数据失败：'. $error_msg);
        }
        
        //保存数据
        $isSave = $this->_combineLib->saveData($_POST, $error_msg);
        if (!$isSave) {
            $this->end(false, '保存数据失败：'. $error_msg);
        }
        
        $this->end(true, '操作成功');
    }
    
    /**
     * 基础物料组合信息异步加载方法
     * 
     * @param Int $combine_id
     * @return String
     */
    public function getCombineItems($combine_id)
    {
        if (empty($combine_id)) {
            $combine_id = $_POST['p[0]'];
        }
        
        //items
        $error_msg = '';
        $items = $this->_combineLib->formatCombineItems($combine_id, $error_msg);
        if($items){
            foreach ($items as $itemKey => $itemVal)
            {
                //选中比例(%)
                if($itemVal['ratio'] == -1){
                    $items[$itemKey]['ratio'] = '随机';
                }
            }
        }
        
        echo json_encode($items);
    }
    
    /**
     * 查看快照
     */
    public function show_history($log_id)
    {
        $logObj = app::get('ome')->model('operation_log');
        
        //日志
        $logInfo = $logObj->dump($log_id, '*');
        if($logInfo){
            $memoInfo = unserialize($logInfo['memo']);
            
            //items
            $error_msg = '';
            $memoInfo['items'] = $this->_combineLib->formatCombineItems($memoInfo['combine_id'], $error_msg);
        }
        
        $this->pagedata['data'] = $memoInfo;
        
        $this->singlepage('admin/fukubukuro/history_log.html');
    }
    
    /**
     * 弹窗获取福袋列表
     * 
     * @return void
     */
    public function findFukubukuro()
    {
        $this->view_source = 'dialog';
        
        //filter
        $base_filter = array('is_delete'=>'false');
        
        //params
        $orderby = 'create_time DESC';
        $params = array(
            'title' => $this->title,
            'base_filter' => $base_filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'orderBy' => $orderby,
        );
        $this->finder('material_mdl_fukubukuro_combine', $params);
    }
    
    /**
     * 弹窗选择福袋后，返回格式化的数据
     * 
     * @return void
     */
    public function getFukubukuro()
    {
        $combine_id = $_POST['combine_id'];
        $combine_bn = trim($_GET['combine_bn']);
        $combine_name = trim($_GET['combine_name']);
        $filter = array();
        
        //format
        if (is_array($combine_id)) {
            if ($combine_id[0] == "_ALL_") {
                //--
            } else {
                $filter['combine_id'] = $combine_id;
            }
        }
        
        if ($combine_bn) {
            $filter = array(
                'combine_bn' => $combine_bn,
            );
        }
        
        if ($combine_name) {
            $filter = array(
                'combine_name' => $combine_name,
            );
        }
        
        //获取选择的福袋组合数据
        //@todo：最多只允许选择100条数据;
        $dataList = $this->_mdl->getList('*', $filter, 0, 100);
        
        //format
        foreach ($dataList as $key => $val)
        {
            //最低价~最高价
            if($val['lowest_price'] == $val['highest_price']){
                $dataList[$key]['combine_price'] = $val['lowest_price'];
            }else{
                $dataList[$key]['combine_price'] = $val['lowest_price'] .' ~ '. $val['highest_price'];
            }
            
            //最低价
            $dataList[$key]['lowest_price'] = $val['lowest_price'];
        }
        
        echo json_encode($dataList);
    }
    
    /**
     * 导出模板
     *
     * @return void
     */
//    public function exportTemplate()
//    {
//        $titleList = $this->_mdl->getTemplateColumn();
//        $data = [];
//
//        //模板案例一
//        $data[0] = array('lucky001', '福袋组合一', '1', '5', 'material_001', '随机');
//        $data[1] = array('lucky001', '福袋组合一', '1', '5', 'material_002', '随机');
//
//        //模板案例二
//        $data[0] = array('lucky002', '福袋组合二', '2', '1', 'material_003', '45');
//        $data[1] = array('lucky002', '福袋组合二', '2', '1', 'material_004', '55');
//
//        //export
//        $lib = kernel::single('omecsv_phpexcel');
//        $lib->newExportExcel($data, '福袋组合导入模板-' . date('Ymd'), 'xls', $titleList);
//    }
}