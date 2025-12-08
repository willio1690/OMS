<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_ctl_admin_account_settlement extends desktop_controller
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
        
        $this->_mdl = app::get('ediws')->model('account_settlement');
        
        //primary_id
        $this->_primary_id = 'sid';
        
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
        if(in_array($_GET['view'], array(0))){
            $actions[] = array(
                'label' => '拉取京东结算单',
                'href' => $this->url .'&act=batchPullc&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:550,height:350,title:'拉取京东结算单'}",
            );
        }elseif(in_array($_GET['view'], array('1', '3'))){
            $actions[] = array(
                'label' => '下载文件',
                'submit' => $this->url .'&act=batchDownloadFile&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:600,height:200,title:'批量对勾选的单据进行下载文件'}",
            );
        }elseif(in_array($_GET['view'], array('2', '5'))){
            $actions[] = array(
                'label' => '解压文件',
                'submit' => $this->url .'&act=batchUnzipFile&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:600,height:200,title:'批量对勾选的单据进行解压文件'}",
            );
        }elseif(in_array($_GET['view'], array('4', '7'))){
            $actions[] = array(
                'label' => '解析文件',
                'submit' => $this->url .'&act=batchAnalysisFile&view='. $_GET['view'] .'&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:600,height:200,title:'批量对勾选的单据进行解析文件'}",
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
        
        $this->finder('ediws_mdl_account_settlement', $params);
    }
    
    /**
     * _views
     * @return mixed 返回值
     */
    public function _views()
    {
        $base_filter = array();
        $sub_menu = array(
            array('label'=>'全部', 'optional'=>false),
            array('label'=>'未下载', 'filter'=>array('file_status'=>'none'), 'optional'=>false),
            array('label'=>'已下载', 'filter'=>array('file_status'=>'download'), 'optional'=>false),
            array('label'=>'下载失败', 'filter'=>array('file_status'=>'download_fail'), 'optional'=>false),
            array('label'=>'已解压', 'filter'=>array('file_status'=>'unzip'), 'optional'=>false),
            array('label'=>'解压失败', 'filter'=>array('file_status'=>'unzip_fail'), 'optional'=>false),
            array('label'=>'已解析', 'filter'=>array('file_status'=>'analysis'), 'optional'=>false),
            array('label'=>'解析失败', 'filter'=>array('file_status'=>'analysis_fail'), 'optional'=>false),
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
        $start_time = date('Y-m', time()) .'-01';
        $this->pagedata['start_time'] = $start_time;
        
        //结束日期(默认为昨天)
        $end_time = date('Y-m-d', strtotime('-1 day'));
        $this->pagedata['end_time'] = $end_time;
        
        //help帮助信息
        $this->pagedata['help_msg'] = '*温馨提醒：只能拉取同一个月内的结算单。';
        
        //供应商编码
        $this->pagedata['selectListName'] = '供应商编码';
        
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
            'total' => 0,
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
        
        //params
        $params = array(
            'shop_id' => $_POST['shop_id'], //供应商编码
            'start_time' => $_POST['startTime'], //开始日期(年-月-日 时:分:秒)
            'end_time' => $_POST['endTime'], //结束日期(年-月-日 时:分:秒)
            'page' => $page, //请求页码
        );
        
        //request
        $rs = kernel::single('ediws_api')->accountSettlementList($params);
        if ($rs['rsp'] == 'succ') {
            $retArr['itotal'] += $rs['current_num']; //本次拉取记录数
            $retArr['isucc'] += $rs['current_succ_num']; //处理成功记录数
            $retArr['ifail'] += $rs['current_fail_num']; //处理失败记录数
            $retArr['total'] = $rs['total_num']; //数据总记录数
            $retArr['next_page'] = $rs['next_page']; //下一页页码(如果为0则无需拉取)
        } else {
            $error_msg = $rs['error_msg'];
            $retArr['err_msg'] = array($error_msg);
        }
        
        echo json_encode($retArr);
        exit;
    }
    
    /**
     * 批量下载文件
     */
    public function batchDownloadFile()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
        $ids = $_POST[$this->_primary_id];
        
        if($_POST['isSelectedAll'] == '_ALL_'){
            die('不能使用全选功能,每次最多选择100条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的记录!');
        }
        
        //京东接口一次最多只支持100条
        if(count($ids) > 100){
            die('每次最多只能选择100条!');
        }
        
        $this->pagedata['GroupList'] = json_encode($ids);
        
        $this->pagedata['request_url'] = $this->url .'&act=ajaxDownloadFile&view='.$_REQUEST['view'].'&finder_id='.$_REQUEST['finder_id'];
        
        //调用desktop公用进度条(第4个参数是增量传incr,否则默认一直为0)
        parent::dialog_batch('ediws_mdl_account_settlement', false, 1, 'incr');
    }
    
    /**
     * ajax下载文件
     */
    public function ajaxDownloadFile()
    {
        $accountLib = kernel::single('ediws_account');
        
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
        $dataList = $this->_mdl->getList('*', $filter, $offset, $limit);
        if(empty($dataList)){
            echo 'Error: 没有获取到数据';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        //list
        foreach ($dataList as $key => $row)
        {
            //check
            if(in_array($row['file_status'], array('download'))){
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'文件已下载,不允许重复操作';
                
                continue;
            }
            
            if(!in_array($row['file_status'], array('none', 'download_fail'))){
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'文件不允许下载,请求检查';
        
                continue;
            }
            
            //手工操作标记
            $row['operation'] = 'manual';
            
            //download
            $result = $accountLib->downloadSettlementFiles($row);
            if($result['rsp'] == 'succ'){
                //succ
                $retArr['isucc'] += 1;
            }else{
                $error_msg = $result['error_msg'];
                
                //fail
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'下载失败：'. $error_msg;
            }
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }
    
    /**
     * 批量解压文件
     */
    public function batchUnzipFile()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
        $ids = $_POST[$this->_primary_id];
        
        if($_POST['isSelectedAll'] == '_ALL_'){
            die('不能使用全选功能,每次最多选择100条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的记录!');
        }
        
        //京东接口一次最多只支持100条
        if(count($ids) > 100){
            die('每次最多只能选择100条!');
        }
        
        $this->pagedata['GroupList'] = json_encode($ids);
        
        $this->pagedata['request_url'] = $this->url .'&act=ajaxUnzipFile&view='.$_REQUEST['view'].'&finder_id='.$_REQUEST['finder_id'];
        
        //调用desktop公用进度条(第4个参数是增量传incr,否则默认一直为0)
        parent::dialog_batch('ediws_mdl_account_settlement', false, 1, 'incr');
    }
    
    /**
     * ajax解压文件
     */
    public function ajaxUnzipFile()
    {
        $accountLib = kernel::single('ediws_account');
        
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
            echo 'Error: 请先选择解压的单据';
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
        $dataList = $this->_mdl->getList('*', $filter, $offset, $limit);
        if(empty($dataList)){
            echo 'Error: 没有获取到数据';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        //list
        foreach ($dataList as $key => $row)
        {
            //check
            if(in_array($row['file_status'], array('unzip'))){
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'文件已解压,不允许重复操作';
                
                continue;
            }
            
            if(!in_array($row['file_status'], array('download', 'unzip_fail'))){
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'文件未下载,请求检查';
                
                continue;
            }
            
            //手工操作标记
            $row['operation'] = 'manual';
            
            //download
            $result = $accountLib->unzipSettlementFiles($row);
            if($result['rsp'] == 'succ'){
                //succ
                $retArr['isucc'] += 1;
            }else{
                $error_msg = $result['error_msg'];
                
                //fail
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'解压失败：'. $error_msg;
            }
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }
    
    /**
     * 批量解析文件
     */
    public function batchAnalysisFile()
    {
        @ini_set('memory_limit','512M');
        set_time_limit(0);
        
        $ids = $_POST[$this->_primary_id];
        
        if($_POST['isSelectedAll'] == '_ALL_'){
            die('不能使用全选功能,每次最多选择100条!');
        }
        
        if(empty($ids)){
            die('请选择需要操作的记录!');
        }
        
        //京东接口一次最多只支持100条
        if(count($ids) > 100){
            die('每次最多只能选择100条!');
        }
        
        $this->pagedata['GroupList'] = json_encode($ids);
        
        $this->pagedata['request_url'] = $this->url .'&act=ajaxAnalysisFile&view='.$_REQUEST['view'].'&finder_id='.$_REQUEST['finder_id'];
        
        //调用desktop公用进度条(第4个参数是增量传incr,否则默认一直为0)
        parent::dialog_batch('ediws_mdl_account_settlement', false, 1, 'incr');
    }
    
    /**
     * ajax解析文件
     */
    public function ajaxAnalysisFile()
    {
        $accountLib = kernel::single('ediws_account');
        
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
            echo 'Error: 请先选择解析的单据';
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
        $dataList = $this->_mdl->getList('*', $filter, $offset, $limit);
        if(empty($dataList)){
            echo 'Error: 没有获取到数据';
            exit;
        }
        
        //count
        $retArr['itotal'] = count($dataList);
        
        //list
        foreach ($dataList as $key => $row)
        {
            //check
            if(in_array($row['file_status'], array('analysis'))){
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'文件已解析,不允许重复操作';
                
                continue;
            }
            
            if(!in_array($row['file_status'], array('unzip', 'analysis_fail'))){
                //error
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'文件不允许解析,请求检查';
                
                continue;
            }
            
            //手工操作标记
            $row['operation'] = 'manual';
            
            //download
            $result = $accountLib->analysisSettlementFiles($row);
            if($result['rsp'] == 'succ'){
                //succ
                $retArr['isucc'] += 1;
            }else{
                $error_msg = $result['error_msg'];
                
                //fail
                $retArr['ifail'] += 1;
                $retArr['err_msg'][] = '结算单号：'. $row['shqid'] .'解析失败：'. $error_msg;
            }
        }
        
        echo json_encode($retArr),'ok.';
        exit;
    }
    
    /**
     * 下载Zip文件
     * 
     * @param int $id
     * @return file
     */
    public function downloadZip($id)
    {
        $analysisLib = kernel::single('ediws_file_analysis_csv');
        
        //文件信息
        $rowInfo = $this->_mdl->dump(array($this->_primary_id=>$id), '*');
        if(empty($rowInfo)){
            die('数据不存在,请检查');
        }elseif(in_array($rowInfo['file_status'], array('none', 'download_fail'))){
            die('单据状态不支持下载文件');
        }elseif(empty($rowInfo['localUrl'])){
            die('文件URL为空,无法下载');
        }
        
        //本地公共目录
        $local_path = $analysisLib->local_path;
        if(empty($local_path)){
            die('本地公共目录为空,无法下载');
        }
        
        //本地文件路径
        $zipname = $local_path . $rowInfo['localUrl'];
        
        //这是要打包的文件地址数组
        $files = array("mypath/test1.txt","mypath/test2.pdf");
        
        $zip = new ZipArchive();
        $res = $zip->open($zipname, ZipArchive::CREATE);
        if ($res === TRUE) {
            foreach ($files as $file) {
                //这里直接用原文件的名字进行打包，也可以直接命名，需要注意如果文件名字一样会导致后面文件覆盖前面的文件，所以建议重新命名
                $new_filename = substr($file, strrpos($file, '/') + 1);
                $zip->addFile($file, $new_filename);
            }
        }
        
        //关闭文件
        $zip->close();
        
        //这里是下载zip文件
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length: " . filesize($zipname));
        header("Content-Disposition: attachment; filename=\"" . basename($zipname) . "\"");
        readfile($zipname);
        exit;
    }
    
    /**
     * 下载CSV文件
     * 
     * @param int $id
     * @return file
     */
    public function downloadCsv($id)
    {
        $analysisLib = kernel::single('ediws_file_analysis_csv');
        
        //文件信息
        $rowInfo = $this->_mdl->dump(array($this->_primary_id=>$id), '*');
        if(empty($rowInfo)){
            die('数据不存在,请检查');
        }elseif(in_array($rowInfo['file_status'], array('none', 'download_fail'))){
            die('单据状态不支持下载文件');
        }elseif(empty($rowInfo['localUrl'])){
            die('文件URL为空,无法下载');
        }
        
        //本地公共目录
        $local_path = $analysisLib->local_path;
        if(empty($local_path)){
            die('本地公共目录为空,无法下载');
        }
        
        //本地文件路径
        $csvFile = $local_path . $rowInfo['unzipUrl'];
        
        //download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($csvFile));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($csvFile));
        
        readfile($csvFile);
        
        fclose($csvFile);
        exit;
    }
}