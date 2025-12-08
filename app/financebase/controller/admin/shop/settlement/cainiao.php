<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 菜鸟账单导入
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_ctl_admin_shop_settlement_cainiao extends desktop_controller
{
    //平台类型
    static $_platform = 'cainiao';
    
    protected function _getBaseFilter()
    {
        $view  = $_GET['view'];
        $filter = array();
        
        //指定平台
        $mdl = app::get('financebase')->model('bill_import');
        $typeList = $mdl->getImportType(null, self::$_platform);
        if($typeList){
            $types = array();
            foreach ($typeList as $key => $val)
            {
                $types[] = $val['key'];
            }
            $filter['type'] = $types;
        }
        
        //filter
        if ($_GET['act'] == 'index') {
            if ($view == 1) {
                //未确认
                $filter['not_confirm_num|than'] = 0;
            }else if ($view == 2) {
                //未对账
                $filter['not_reconciliation_num|than'] = 0;
            } else if ($view == 3) {
                //金额不对
                $filter['money_unequal_num|than'] = 0;
            }
        }
        
        return $filter;
    }

    function _views()
    {
        static $sub_menu;
        if ($sub_menu) {
            return $sub_menu;
        }

        $mdl = app::get('financebase')->model('bill_import');
        
        //指定平台
        $typeList = $mdl->getImportType(null, self::$_platform);
        $types = array();
        if($typeList){
            foreach ($typeList as $key => $val)
            {
                $types[] = $val['key'];
            }
        }
        
        //href
        $href = 'index.php?app=' . $_GET['app'] . '&ctl=' . $_GET['ctl'] . '&act='.$_GET['act'];
        if ($_GET['act'] == 'detailed') {
            $importFilter['id'] = $_GET['id'];
            $href .= '&id='.$_GET['id'];
        }
        $res = $mdl->getRow("sum(not_confirm_num) as not_confirm_num, sum(not_reconciliation_num) as not_reconciliation_num, sum(money_unequal_num) as money_unequal_num", $importFilter);

        //$filter = $this->_getBaseFilter();
        $sub_menu = array(
            0 => array('label' => app::get('base')->_('全部已导入'), 'filter' => array('type'=>$types), 'optional' => false),
            1 => array('label' => app::get('base')->_('已导入未确认'), 'filter' => array('type'=>$types, 'not_confirm_num|than'=>0), 'optional' => false),
            2 => array('label' => app::get('base')->_('已确认未对账'), 'filter' => array('type'=>$types, 'not_reconciliation_num|than'=>0), 'optional' => false),
            3 => array('label' => app::get('base')->_('已对账金额不等'), 'filter' => array('type'=>$types, 'money_unequal_num|than'=>0), 'optional' => false),
        );

        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = array_merge($this->_getBaseFilter(), $v['filter']);
            $sub_menu[$k]['addon'] = $mdl->count($v['filter']);
            $sub_menu[$k]['href'] = $href  . '&view=' . $k;
        }

        return $sub_menu;
    }

    // 流水单
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        $mdl = app::get('financebase')->model('bill_import');
        $this->pagedata['templateType'] = $mdl->getImportType();
        $this->pagedata['type'] = $_POST['type'] ? $_POST['type'] : '';
        $this->pagedata['time_from'] = $_POST['time_from'] ? $_POST['time_from'] : '';
        $this->pagedata['time_to'] = $_POST['time_to'] ? $_POST['time_to'] : '';
        $this->pagedata['file_name'] = isset($_POST['file_name']) ? $_POST['file_name'] : '';


        $actions[] = array(
            'label'  => '按单号导入',
            'href'   => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=importview&type=order',
            'target' => "dialog::{width:500,height:200,title:'按单号导入'}",
        );
        $actions[] = array(
            'label'  => '按SKU明细导入',
            'href'   => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=importview&type=sku',
            'target' => "dialog::{width:500,height:200,title:'按SKU明细导入'}",
        );
        $actions[] = array(
            'label'  => '按销售周期导入',
            'href'   => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=importview&type=sale',
            'target' => "dialog::{width:500,height:200,title:'按销售周期导入'}",
        );
        $actions[] = array(
            'label'  => '红冲导入',
            'href'   => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=redimportview',
            'target' => "dialog::{width:500,height:300,title:'红冲导入'}",
        );
        
        $_GET['view'] = (int)$_GET['view'];
        $params = array(
            'title'                  => '菜鸟账单导入',
            'use_buildin_tagedit'    => false,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'allow_detail_popup'     => false,
            'use_view_tab'           => true,
            'orderBy' => 'id desc',
            'base_filter' => $this->_getBaseFilter(),
        );
        
        $params['actions'] = $actions;

        $this->finder('financebase_mdl_bill_import', $params);
    }

    // 导出设置页
    /**
     * importview
     * @return mixed 返回值
     */
    public function importview()
    {

        // 显示tab
        $setting = array(
            'order' => array('name' => '按单号导入', 'type' => 'order'),
            'sku'   => array('name' => '按SKU明细导入', 'type' => 'sku'),
            'sale'  => array('name' => '按销售周期导入', 'type' => 'sale'),
        );

        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if (!isset($setting[$type])) {
            exit("未找到此节点类型:" . $type);
        }

        $this->pagedata['setting'] = $setting[$type];

        $this->page('admin/cainiao/importview.html');
    }

    /**
     * redimportview
     * @return mixed 返回值
     */
    public function redimportview() {
        $this->display('admin/cainiao/redimportview.html');
    }

    /**
     * template
     * @return mixed 返回值
     */
    public function template()
    {
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $res = $this->getTitle($type);
        $lib = kernel::single('financebase_phpexcel');
        $lib->newExportExcel(array(),$res['name'],'xls',$res['title']);
        exit;
    }



    /**
     * doImport
     * @return mixed 返回值
     */
    public function doImport()
    {

        @ini_set('memory_limit', '512M');
        $url = 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=index';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if (!$type) {
            $this->splash('error', $url, "未找到此节点类型");
            exit;
        }

        if ($type == 'sale') {
            if (empty($_POST['start_time']) || empty($_POST['end_time'])) {
                $this->splash('error', $url, "请选择销售周期");
                exit;
            }

            $startRes = finance_io_bill_verify::isDate($_POST['start_time']);
            $endRes = finance_io_bill_verify::isDate($_POST['end_time']);
            if ($startRes['status'] == 'fail' || $endRes['status'] == 'fail') {
                $this->splash('error', $url, "销售周期不是有效的时间格式");
                exit;
            }

            if (strtotime($_POST['start_time']) > strtotime($_POST['end_time'])) {
                $this->splash('error', $url, "销售周期结束时间不能大于开始时间");
                exit;
            }
        }
//p($_FILES);

        if ($_FILES['import_file']['name'] && $_FILES['import_file']['error'] == 0) {
            $file_type = substr($_FILES['import_file']['name'], strrpos($_FILES['import_file']['name'], '.') + 1);
            $file_type = strtolower($file_type);
            if (in_array($file_type, array('csv', 'xls', 'xlsx'))) {

                $oProcess = kernel::single('financebase_data_cainiao_' . $type);

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

                    $importModel = $oFeeType = app::get('financebase')->model("bill_import");
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $params = array(
                        'file_name'              => $_FILES['import_file']['name'],
                        'type'                   => $type,
                        'money'                  => 0,
                        'error_data'             => array(),
                        'not_confirm_num'        => 0,
                        'not_reconciliation_num' => 0,
                        'not_matching_num'       => 0,
                        'money_unequal_num'      => 0,
                        'op_id'                  => $opInfo['op_id'],
                        'create_time'            => time(),
                        'start_time'             => strtotime($_POST['start_time']),
                        'end_time'               => strtotime($_POST['end_time'] . " 23:59:59"),
                    );
                    $id = $importModel->insert($params);

                    $mdlQueue = app::get('financebase')->model('queue');
                    $queue_name = 'cainiaoAssign' . ucfirst($type);
                    $queueData = array();
                    $queueData['queue_mode'] = $queue_name;
                    $queueData['create_time'] = time();
                    $queueData['queue_name'] = sprintf("%s_导入文件_分派任务", $bill_date);
                    $queueData['queue_data']['bill_date'] = $bill_date;
//                    $queueData['queue_data']['shop_type'] = $type;
                    $queueData['queue_data']['task_name'] = basename($_FILES['import_file']['name']);
                    $queueData['queue_data']['file_type'] = $file_type;
                    $queueData['queue_data']['remote_url'] = $remote_url;
                    $queueData['queue_data']['import_id'] = $id;

                    $queue_id = $mdlQueue->insert($queueData);

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
     * detailed
     * @return mixed 返回值
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
            'href'  => "index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=index&view={$this->pagedata['view']}&page={$this->pagedata['page']}",
        );

        $actions[] = array(
            'label'  => '批量确认',
            'submit' => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=doBatchChangeStatus&type=' . $import_res['type'] . '&finder_id=' . $_GET['finder_id'],
        );

        $actions[] = array(
            'label'  => '批量取消',
            'confirm' => '数据取消将无法恢复，需要重新导入',
            'submit' => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=doBatchCancel&type=' . $import_res['type'] . '&finder_id=' . $_GET['finder_id'],
        );

        $actions[] = array(
            'label' => '导出',
            'target'=>'_blank',
            'class' => 'export',
            'submit' => 'index.php?app=financebase&ctl=admin_shop_settlement_cainiao&act=export&type=' . $import_res['type'],
        );

        $actions[] = array(
            'label' => '总费: ' . $import_res['money'],
        );


        $view  = $_GET['view'];
        $filter['import_id'] = $_GET['id'];
        if ($view == 1) {
            //未确认
            $filter['confirm_status'] = '0';
        }
        else if ($view == 2) {
            //未对账
            $filter['confirm_status'] = '1';
            $filter['confirm_account'] = '0';
        } else if ($view == 3) {
            //金额不对
            $filter['confirm_account'] = '1';
        }

        $_GET['view'] = (int)$_GET['view'];
        $params = array(
            'title'                  => '菜鸟账单详情',
            'use_buildin_tagedit'    => true,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'allow_detail_popup'     => false,
            'use_view_tab'           => true,
            'base_filter'            => $filter,
            'orderBy' => 'id desc',
//            'finder_cols'         =>'id,type,file_name,money,not_confirm_num,not_reconciliation_num,not_matching_num,money_unequal_num,op_id',
        );

        $params['actions'] = $actions;

        $this->finder('financebase_mdl_bill_import_' . $import_res['type'], $params);
    }

    /**
     * export
     * @return mixed 返回值
     */
    public function export(){
//        p($_GET);
//        p($_POST,1);
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $res = $this->getTitle($type);
        $mdl = app::get('financebase')->model('bill_import_' . $_GET['type']);

        $data = $mdl->getList('*',array('id' => isset($_POST['id']) ? $_POST['id'] : ''));
        $saveData = array();
        if (!empty($data)) {
            switch ($type) {
                case 'order':
                    foreach ($data as $v) {
                        $saveData[] = array(
                            'expenditure_time'  => date('Y-m-d H:i:s',$v['expenditure_time']),
                            'cost_project'      => $v['cost_project'],
                            'expenditure_money' => $v['expenditure_money'],
                            'pay_serial_number' => $v['pay_serial_number'],
                            'transaction_sn'    => $v['transaction_sn'],
                            'logistics_sn'      => $v['logistics_sn'],
                        );
                    }
                    break;
                case 'sku':
                    foreach ($data as $v) {
                        $saveData[] = array(
                            'pay_serial_number'    => $v['pay_serial_number'],
                            'expenditure_time'     => date('Y-m-d H:i:s',$v['expenditure_time']),
                            'expenditure_money'    => $v['expenditure_money'],
                            'increment_service_sn' => $v['increment_service_sn'],
                            'relation_sn'          => $v['relation_sn'],

                            'cost_project'         => $v['cost_project'],
                            'service_provider'     => $v['service_provider'],
                            'submit_time'          => date('Y-m-d H:i:s',$v['submit_time']),
                            'status'               => $v['status'],
                            'sku'                  => $v['sku'],
                            'product_name'         => $v['product_name'],
                            'plan_operation_num'   => $v['plan_operation_num'],
                            'actual_operation_num' => $v['actual_operation_num'],
                        );
                    }
                    break;
                case 'sale':

                    foreach ($data as $v) {
                        $saveData[] = array(
                            'pay_serial_number' => $v['pay_serial_number'],
                            'expenditure_time'  => date('Y-m-d H:i:s',$v['expenditure_time']),
                            'expenditure_money' => $v['expenditure_money'],
                            'cost_project'      => $v['cost_project'],
                        );
                    }
                    break;
                default:
                    echo '导出错误';
                    exit;
            }
        }
        $res = $this->getTitle($type);
        $lib = kernel::single('financebase_phpexcel');
        $lib->newExportExcel($saveData,$res['name'],'xls',$res['title']);
        exit;
    }

    /**
     * downloaderr
     * @return mixed 返回值
     */
    public function downloaderr()
    {
        @ini_set('memory_limit','1024M');
        $mdl = app::get('financebase')->model('bill_import');
        $data = $mdl->getRow('error_data,type', array('id' => $_GET['id']));
        $type = $mdl->getImportType();
        $name = $type[$data['type']]['name'];
        $data = unserialize($data['error_data']);
        $lib = kernel::single('financebase_phpexcel');
        $lib->newExportExcel($data,$name,'xls');
        exit;
    }

    /**
     * doBatchCancel
     * @return mixed 返回值
     */
    public function doBatchCancel() {
        if(empty($_POST['id'])) {
            $this->splash('error', 'javascript:finderGroup["' . $_GET['finder_id'] . '"].refresh();', '取消失败');
        }
        foreach ($_POST['id'] as $v) {
            $_GET['id'] = $v;
            $this->doCancel(true);
        }
        $this->splash('success', 'javascript:finderGroup["' . $_GET['finder_id'] . '"].refresh();', '取消完成');
    }

    /**
     * doCancel
     * @param mixed $isReturn isReturn
     * @return mixed 返回值
     */
    public function doCancel($isReturn = false)
    {
        try {
            if (!isset($_GET['id']) && empty($_GET['id'])) {
                $msg = '取消失败,缺ID';
                if(!$isReturn) {
                    echo $msg;exit;
                } else {
                    return $msg;
                }
            }
            $db = kernel::database();
            $mdl = app::get('financebase')->model('bill_import_' . $_GET['type']);
            $res = $mdl->getRow('*', array('id' => $_GET['id']));
            if(!$res['id']) {
                $msg = '取消失败,找不到该明细';
                if(!$isReturn) {
                    echo $msg;exit;
                } else {
                    return $msg;
                }
            }
            $transaction_status = $db->beginTransaction();
            $flag = $mdl->update(['confirm_status'=>'2'],array('id' => $res['id'],'confirm_status'=>'0'));
            if (is_bool($flag)) {
                $db->rollback();
                $msg = '取消失败,该明细已经被处理了';
                if(!$isReturn) {
                    echo $msg;exit;
                } else {
                    return $msg;
                }
            }

            //计算总费用
            $this->countMoney($res,$mdl,$_GET['type']);

            //更新支付宝对账状态
            /*$flag = $this->modifyBillStatus($res, $mdl,  $_GET['type']);
            if (!$flag) {
                $db->rollback();
                echo '更新支付宝实付明细失败';
                exit;
            }*/

            $flag = $this->modifyNum($mdl,$res);
            if (!$flag) {
                $db->rollback();
                $msg = '取消失败,数据更新失败';
                if(!$isReturn) {
                    echo $msg;exit;
                } else {
                    return $msg;
                }
            }
            $db->commit($transaction_status);
            $msg = '取消成功';
            if(!$isReturn) {
                echo $msg;exit;
            } else {
                return $msg;
            }
        } catch (\Exception $e) {
            $msg = '取消失败,发生异常';
            if(!$isReturn) {
                echo $msg;exit;
            } else {
                return $msg;
            }
        }

    }

    //计算总费用
    /**
     * countMoney
     * @param mixed $res res
     * @param mixed $mdl mdl
     * @param mixed $type type
     * @return mixed 返回值
     */
    public function countMoney($res, $mdl,  $type)
    {
        $summaryMdl = app::get('financebase')->model("bill_import_summary");
        $money = 0;
        switch ($type){
            case 'order':
            case 'sale':
                $money = $res['expenditure_money'];
                break;
            case 'sku':
                //存在, 则不删减总费用
                $data = $mdl->getRow('*',array('increment_service_sn' => $res['increment_service_sn'],'import_id' => $res['import_id'], 'expenditure_money' => $res['expenditure_money']));
                if ($data) {
                    return;
                }
                $money = $res['expenditure_money'];
                break;
        }

        if ($money !== 0) {
            kernel::database()->query("update sdb_financebase_bill_import_summary set expenditure_money=expenditure_money-{$money} where id = {$res['summary_id']}");
            kernel::database()->query("update sdb_financebase_bill_import set money=money-{$money} where id = {$res['import_id']}");
        }
    }

    /**
     * modifyBillStatus
     * @param mixed $res res
     * @param mixed $typeMdl typeMdl
     * @param mixed $type type
     * @return mixed 返回值
     */
    public function modifyBillStatus($res, $typeMdl, $type)
    {
        $mdl = app::get('financebase')->model('cainiao');
        $row = $mdl->getBill($res['pay_serial_number']);
        if (!$row) {
            return true;
        }

        $flag = $mdl->update(array(
            'confirm_status' => '0',
            'confirm_fail_msg' => '未匹配到',
            'split_status' => '4',
            'split_msg' => '未匹配到菜鸟账单'
        ), array('id' => $row['id']));

        if (!$flag) {
            return false;
        }


        //删除拆分结果明细
        $mdl = app::get('financebase')->model('expenses_split');
        $data = $mdl->getList('id',array('bill_id' => $row['id']));
        if ($data) {
            $flag = $mdl->delete('bill_id', $row['id']);
            if (!$flag) {
                return false;
            }
        }

        //查询是否还有相同的账单明细, 有:则更新成未对账
        $data = $typeMdl->getList('*',array('import_id' => $res['import_id'],'confirm_account'=>'2','pay_serial_number' => array($row['trade_no'], $row['out_trade_no'])));
        if ($data) {
            $flag = $typeMdl->update(array('confirm_account'=>'0'),array('confirm_account'=>'2','import_id' => $res['import_id'],'pay_serial_number' => array($row['trade_no'], $row['out_trade_no'])));
            if (!$flag) {
                return false;
            }
        }



        return true;
    }

    /**
     * modifyNum
     * @param mixed $mdl mdl
     * @param mixed $res res
     * @return mixed 返回值
     */
    public function modifyNum($mdl,$res)
    {
        //未确认数量
        $not_confirm_num = $mdl->count(array('confirm_status' => '0', 'import_id' => $res['import_id']));
//        //未对账数量
        $not_reconciliation_num = $mdl->count(array('confirm_account' => '0','confirm_status' => '1', 'import_id' => $res['import_id']));
//        //金额不对
//        $money_unequal_num = $mdl->count(array('confirm_account' => '1', 'import_id' => $res['import_id']));

        $importMdl = app::get('financebase')->model('bill_import');
        $data = array(
            'not_confirm_num' => $not_confirm_num,
            'not_reconciliation_num' => $not_reconciliation_num,
            'money_unequal_num' => $money_unequal_num,
//            'money_unequal_num' => $money_unequal_num,
        );
        $flag = $importMdl->update($data, array('id' => $res['import_id']));
        if (!$flag) {
            return false;
        }
        return true;
    }

    /**
     * doChangeStatus
     * @return mixed 返回值
     */
    public function doChangeStatus()
    {
        try {
            if (!isset($_GET['id']) && empty($_GET['id'])) {
                echo '确认失败';
                exit;
            }


            $mdl = app::get('financebase')->model('bill_import_' . $_GET['type']);
            $res = $mdl->getRow('*', array('id' => $_GET['id']));
            if (!$res) {
                echo '确认失败';
                exit;
            }
            $opInfo = kernel::single('ome_func')->getDesktopUser();
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();
            $flag = $mdl->update(array('confirm_status' => 1, 'confirm_time' => time(), 'op_id' => $opInfo['op_id']), array('id' => $_GET['id']));
            if (!$flag) {
                $db->rollback();
                echo '确认失败';
                exit;
            }

            $flag = $this->modifyNum($mdl,$res);
            if (!$flag) {
                $db->rollback();
                echo '确认失败';
                exit;
            }

            $db->commit($transaction_status);
            echo '确认成功';
            exit;
        } catch (\Exception $e) {
            echo '确认失败';
            exit;
        }

    }

    /**
     * 批量更新
     * @return bool
     */
    public function doBatchChangeStatus()
    {

        $this->begin('javascript:finderGroup["' . $_GET['finder_id'] . '"].refresh();');

        if (!isset($_POST['id']) && empty($_POST['id'])) {
            $this->end(false, '确认失败', 'javascript:parent.finderGroup["' . $_GET['finder_id'] . '"].refresh();');
            exit;
        }

        $db = kernel::database();
        $transaction_status = $db->beginTransaction();

        $mdl = app::get('financebase')->model('bill_import_' . $_GET['type']);
        $res = $mdl->getRow('*', array('id' => $_POST['id'][0]));


        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $flag = $mdl->update(array('confirm_status' => 1, 'confirm_time' => time(), 'op_id' => $opInfo['op_id']), array('id' => $_POST['id']));
        if (!$flag) {
            $db->rollback();
            $this->end(false, '确认失败', 'javascript:finderGroup["' . $_GET['finder_id'] . '"].refresh();');
            exit;
        }


        $flag = $this->modifyNum($mdl,$res);
        if (!$flag) {
            $db->rollback();
            $this->end(false, '确认失败', 'javascript:finderGroup["' . $_GET['finder_id'] . '"].refresh();');
            exit;
        }

        $db->commit($transaction_status);

        $this->end(true, '确认成功', 'javascript:parent.finderGroup["' . $_GET['finder_id'] . '"].refresh();');
    }

    /**
     * test
     * @return mixed 返回值
     */
    public function test()
    {
        $this->oFunc = kernel::single('financebase_func');
        $this->oQueue = app::get('financebase')->model('queue');

        $filter['queue_id'] = $_GET['id'];
//        $filter['status'] = 'ready';

        // 获取检测任务
        $task_info = $this->oQueue->getList('queue_id,queue_name,queue_mode,queue_data,queue_no', $filter, 0, 1);
        $task_info = $task_info[0];
        $task_info['queue_data'] = unserialize($task_info['queue_data']);


        $class_name = sprintf("financebase_autotask_task_type_" . $task_info['queue_mode']);

        if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)) {

            if (method_exists($instance, 'process')) {
//                $this->oQueue->update(array('status'=>'process','modify_time'=>time()),array('queue_id'=>$task_info['queue_id']));
                $rs = $instance->process($task_info, $msg);
//                if($rs)
//                if($rs)
//                {
//                    $this->oQueue->update(array('status'=>'succ','modify_time'=>time()),array('queue_id'=>$task_info['queue_id']));
//                }else{
//                    $this->oQueue->update(array('status'=>'error','modify_time'=>time(),'error_msg'=>$msg),array('queue_id'=>$task_info['queue_id']));
//                }
            } else {
//                $this->oFunc->writelog('对账单导入任务-处理方法不存在','settlement','任务ID:'.$task_info['queue_id']);
//                $this->oQueue->update(array('status'=>'error','modify_time'=>time(),'error_msg'=>array('处理方法不存在')),array('queue_id'=>$task_info['queue_id']));
            }
        }
    }


    /**
     * 获取Title
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getTitle($type){
        switch ($type) {
            case 'order':
                $title = array(
                    '支付时间', '费用项', '支付金额',
                    '支付流水号', '交易订单号', '运单号',
                );
                $name = '单号';
                return compact('title','name');
                break;
            case 'sku':
                $title = array(
                    '支付流水号', '支付时间', '支付金额', '增值服务单号', '关联单据号',
                    '增值服务类型', '服务商', '提交时间', '状态', '商品编码', '商品名称', '计划操作数量', '实际操作数量',
                );
                $name = 'SKU明细';
                return compact('title','name');
                break;
            case 'sale':
                $title = array(
                    '支付流水号', '支出时间', '支出金额', '费用项',
                );
                $name = '销售周期';
                return compact('title','name');
                break;
            default:
                return false;
                break;
        }
        return false;
    }

}
