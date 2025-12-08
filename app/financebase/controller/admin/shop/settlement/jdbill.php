<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东钱包流水导入
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_ctl_admin_shop_settlement_jdbill extends desktop_controller
{
    //账单类型
    static $_import_type = 'jdbill';
    
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        $mdl = app::get('financebase')->model('bill_import');
        
        $base_filter = array('type'=>self::$_import_type);
        
        $this->pagedata['templateType'] = $mdl->getImportType();
        $this->pagedata['type'] = $_POST['type'] ? $_POST['type'] : '';
        $this->pagedata['time_from'] = $_POST['time_from'] ? $_POST['time_from'] : '';
        $this->pagedata['time_to'] = $_POST['time_to'] ? $_POST['time_to'] : '';
        $this->pagedata['file_name'] = isset($_POST['file_name']) ? $_POST['file_name'] : '';
        
        $actions[] = array(
            'label'  => '导入京东钱包流水',
            'href'   => 'index.php?app=financebase&ctl=admin_shop_settlement_jdbill&act=importview&type=jdbill',
            'target' => "dialog::{width:500,height:200,title:'导入京东钱包流水'}",
        );
        
        $_GET['view'] = (int)$_GET['view'];
        $params = array(
            'title' => '京东钱包流水导入',
            'use_buildin_tagedit' => false,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'allow_detail_popup' => false,
            'use_view_tab' => true,
            'orderBy' => 'id desc',
            'base_filter' => $base_filter,
        );
        
        $params['actions'] = $actions;
        
        $this->finder('financebase_mdl_bill_import', $params);
    }

    //导入
    /**
     * importview
     * @return mixed 返回值
     */
    public function importview()
    {
        //显示tab
        $setting = array(
            'jdbill' => array('name'=>'支持导入.csv、.xls、.xlsx文件格式：', 'type'=>self::$_import_type),
        );
        
        $type = isset($_GET['type']) ? $_GET['type'] : self::$_import_type;
        if (!isset($setting[$type])) {
            exit("未找到此节点类型:" . $type);
        }
        
        $this->pagedata['setting'] = $setting[$type];
        
        $this->page('admin/jdbill/importview.html');
    }
    
    /**
     * doImport
     * @return mixed 返回值
     */
    public function doImport()
    {
        @ini_set('memory_limit', '512M');
        
        $url = 'index.php?app=financebase&ctl=admin_shop_settlement_jdbill&act=index';
        
        //file
        if ($_FILES['import_file']['name'] && $_FILES['import_file']['error'] == 0) {
            $file_type = substr($_FILES['import_file']['name'], strrpos($_FILES['import_file']['name'], '.') + 1);
            $file_type = strtolower($file_type);
            
            if (in_array($file_type, array('csv', 'xls', 'xlsx'))) {
                $oProcess = kernel::single('financebase_data_jingzhuntong_' . self::$_import_type);
                
                list($checkRs, $errmsg) = $oProcess->checkFile($_FILES['import_file']['tmp_name'], $file_type);
                
                if (!$checkRs) {
                    $this->splash('error', $url, $errmsg);
                    exit;
                }
                
                $bill_date = date('Y-m-d');
                
                //临时文件生成后往ftp服务器迁移
                $storageLib = kernel::single('taskmgr_interface_storage');
                $move_res = $storageLib->save($_FILES['import_file']['tmp_name'], md5($_FILES['import_file']['name'] . time()) . '.' . $file_type, $remote_url);
                
                if (!$move_res) {
                    $this->splash('error', $url, "文件上传失败");
                    exit;
                } else {
                    $importModel = $oFeeType = app::get('financebase')->model('bill_import');
                    
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $params = array(
                        'file_name' => $_FILES['import_file']['name'],
                        'type' => self::$_import_type,
                        'money' => 0,
                        'error_data' => array(),
                        'not_confirm_num' => 0,
                        'not_reconciliation_num' => 0,
                        'not_matching_num' => 0,
                        'money_unequal_num' => 0,
                        'op_id' => $opInfo['op_id'],
                        'create_time' => time(),
                        'start_time' => strtotime($_POST['start_time']),
                        'end_time' => strtotime($_POST['end_time'] . " 23:59:59"),
                    );
                    
                    $id = $importModel->insert($params);
                    
                    $mdlQueue = app::get('financebase')->model('queue');
                    
                    //queue任务名保存时,会转换成为小写字母(task任务名需要首字母大写)
                    $queue_name = 'cainiaoAssign' . ucfirst(self::$_import_type);
                    
                    $queueData = array();
                    $queueData['queue_mode'] = $queue_name;
                    $queueData['create_time'] = time();
                    $queueData['queue_name'] = sprintf("%s_导入京东钱包流水文件_分派任务", $bill_date);
                    $queueData['queue_data']['bill_date'] = $bill_date;
                    
                    $queueData['queue_data']['task_name'] = basename($_FILES['import_file']['name']);
                    $queueData['queue_data']['file_type'] = $file_type;
                    $queueData['queue_data']['remote_url'] = $remote_url;
                    $queueData['queue_data']['import_id'] = $id;
                    
                    $queue_id = $mdlQueue->insert($queueData);
                    
                    //添加task分片处理任务
                    financebase_func::addTaskQueue(array('queue_id' => $queue_id), strtolower($queue_name));
                    
                    header("content-type:text/html; charset=utf-8");
                    echo "<script>alert(\"上传成功 已加入队列 系统会自动跑完队列\");parent.finderGroup['{$_GET['finder_id']}'].refresh();</script>";
                    exit;
                }
            } else {
                $this->splash('error', $url, "不支持此文件");
                exit;
            }

        } else {
            $this->splash('error', $url, "上传失败");
            exit;
        }
    }
    
    /**
     * 明细显示列表
     */
    public function detailed()
    {
        $mdl = app::get('financebase')->model('bill_import');
        $import_res = $mdl->getRow('*', array('id' => $_GET['id']));
        
        $this->pagedata['import_res'] = $import_res;
        $this->pagedata['view'] = isset($_GET['view']) ? $_GET['view'] : 0;
        $this->pagedata['page'] = isset($_GET['page']) ? $_GET['page'] : 1;
        
        $this->pagedata['confirm_status'] = $_POST['confirm_status'] ? $_POST['confirm_status'] : 'all';
        $this->pagedata['money_from'] = $_POST['money_from'] ? $_POST['money_from'] : '';
        $this->pagedata['money_to'] = $_POST['money_to'] ? $_POST['money_to'] : '';
        $this->pagedata['pay_serial_number'] = isset($_POST['pay_serial_number']) ? $_POST['pay_serial_number'] : '';
        
        $actions[] = array(
            'label' => '返回列表',
            'href'  => "index.php?app=financebase&ctl=admin_shop_settlement_jdbill&act=index&view={$this->pagedata['view']}&page={$this->pagedata['page']}",
        );
        
        $actions[] = array(
            'label' => '导出',
            'target'=>'_blank',
            'class' => 'export',
            'submit' => 'index.php?app=financebase&ctl=admin_shop_settlement_jdbill&act=export&type=' . $import_res['type'],
        );
        
        $view  = $_GET['view'];
        $filter['import_id'] = $_GET['id'];
        
        $_GET['view'] = (int)$_GET['view'];
        
        $params = array(
            'title' => '京东钱包流水详情',
            'use_buildin_tagedit'    => true,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'allow_detail_popup' => false,
            'use_view_tab' => false,
            'base_filter' => $filter,
            'orderBy' => 'id desc',
        );
        
        $params['actions'] = $actions;
        
        $this->finder('financebase_mdl_bill_import_' . $import_res['type'], $params);
    }
    
    /**
     * 获取Title
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getTitle($type)
    {
        $oProcess = kernel::single('financebase_data_jdbill_'. $type);
        $titleList = $oProcess->getTitle();
        $titleList['title'] = implode(',', $titleList);
        $titleList['name'] = '京东钱包流水';
        
        return $titleList;
    }
    
    /**
     * 账单明细列表导出
     */
    public function export()
    {
        $type = isset($_GET['type']) ? $_GET['type'] : self::$_import_type;
        $res = $this->getTitle($type);
        $mdl = app::get('financebase')->model('bill_import_'. $_GET['type']);
        
        $data = $mdl->getList('*',array('id' => isset($_POST['id']) ? $_POST['id'] : ''));
        $saveData = array();
        if (!empty($data)) {
            switch ($type) {
                case self::$_import_type:
                default:
                    foreach ($data as $v) {
                        $saveData[] = array(
                                'expenditure_time'  => date('Y-m-d H:i:s', $v['expenditure_time']),
                                'cost_project'      => $v['cost_project'],
                                'expenditure_money' => $v['expenditure_money'],
                                'pay_serial_number' => $v['pay_serial_number'],
                                'transaction_sn'    => $v['transaction_sn'],
                                'logistics_sn'      => $v['logistics_sn'],
                        );
                    }
                exit;
            }
        }
        
        $lib = kernel::single('financebase_phpexcel');
        $lib->newExportExcel($saveData,$res['name'],'xls',$res['title']);
        exit;
    }
    
    /**
     * 下载错误的文件
     */
    public function downloaderr()
    {
        @ini_set('memory_limit','1024M');
        
        $mdl = app::get('financebase')->model('bill_import');
        $data = $mdl->getRow('error_data,type', array('id' => $_GET['id']));
        
        $types = $mdl->getImportType();
        
        $name = $types[$data['type']]['name'];
        $data = unserialize($data['error_data']);
        
        $lib = kernel::single('financebase_phpexcel');
        $lib->newExportExcel($data,$name,'xls');
        exit;
    }
}
