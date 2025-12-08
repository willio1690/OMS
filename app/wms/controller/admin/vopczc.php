<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing
 * @describe 唯品会仓中仓
 */
class wms_ctl_admin_vopczc extends desktop_controller {
    var $workground = "wms_delivery";
    protected function _getBaseFilter() {
        $filter = array (
            'status' => array ( 'ready', 'progress' ),
            'process' => 'FALSE',
            'pause' => 'FALSE',
            'parent_id' => 0,
            'disabled' => 'false',
        );
        //获取操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $oBranch = app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $filter['ext_branch_id'] = $_POST['branch_id'] ? array_intersect(array($_POST['branch_id']), $branch_ids) : $branch_ids;
            } else {
                $filter['ext_branch_id'] = 'false';
            }
        } else {
            $filter['ext_branch_id'] = $_POST['branch_id'] ? $_POST['branch_id'] : null;;
        }
        return $filter;
    }

    /**
     * _views
     * @return mixed 返回值
     */

    public function _views(){
        static $sub_menu;
        if($sub_menu) {
            return $sub_menu;
        }
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('未打印'),'filter'=>array('process_status'=>array('0'),'status'=>array('0') ),'optional'=>false),
            1 => array('label'=>app::get('base')->_('已打印'),'filter'=>array('process_status'=>array('1'),'status'=>array('0')),'optional'=>false),
        );
        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = array_merge($this->_getBaseFilter(), $v['filter']);
            $sub_menu[$k]['addon'] = 'showtab';
            $sub_menu[$k]['href'] = 'index.php?app=' . $_GET['app'] . '&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $k;
        }
        return $sub_menu;
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $_GET['view'] = (int) $_GET['view'];
        $params = array(
            'title'=>'唯品会仓中仓列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab' => true,
            'base_filter' => $this->_getBaseFilter()
        );
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('delivery_finder_top');
            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            $panel->show('ome_mdl_delivery', $params);
        }
        $params['actions'] = array(
            'expre' => array(
                'label' => '打印三单',
                'submit' => 'index.php?app=wms&ctl=admin_vopczc&act=printExpress',
                'onclick'=>"_hmt.push(['_trackEvent', '订单发货操作', '打印快递单', 'b-打印快递单']);",
            ),
            'waybill' => array(
                'label' => '获取运单号',
                'submit' => 'index.php?app=wms&ctl=admin_vopczc&act=getWaybill',
                'target' => 'dialog::{title:\'获取运单号\',width:600,height:300}',
            ),
        );
        if($_GET['view'] == '1') {
            unset($params['actions']['expre']);
        } else {
            unset($params['actions']['waybill']);
        }
        $this->finder('wms_mdl_delivery_vopczc',$params);
    }

    /**
     * printExpress
     * @return mixed 返回值
     */
    public function printExpress() {
        $msg = array();
        $model = app::get('wms')->model('delivery_vopczc');
        if($_POST['isSelectedAll'] == '_ALL_') {
            
            $baseFilter = $this->_getBaseFilter();
            $param = array_merge($baseFilter, $_POST);
            $model->defaultOrder = '';
            $model->filter_use_like = true;
            $selData = $model->getList('delivery_id', $param, 0, -1);
            $arrRequestId = array();
            foreach ($selData as $val) {
                $arrRequestId[] = $val['delivery_id'];
            }
            unset($_POST['isSelectedAll']);
        } else {
            $arrRequestId = $_POST['delivery_id'];
        }
        //防止并发打印重复获取运单号
        $_inner_key = sprintf("print_ids_%s", md5(implode(',',$arrRequestId)));
        $aData = cachecore::fetch($_inner_key);
        if ($aData === false) {
            cachecore::store($_inner_key, 'printed', 5);
        }else{
            $this->splash('error', '',"选中的发货单已在打印三单中，请不要重复打印！！！如没有打印完成，请稍后重试！！！");
        }
        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas(array('filter' => array('delivery_id'=>$arrRequestId)),'vopczc','',true,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->splash('error', '',$msg['error_msg']);
        }
        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->splash('error', '',$msg['warn_msg']);
        }
        $printDly = array();
        $needExport = array();
        $needGetWaybill = array();
        foreach ($print_data['deliverys'] as $delivery) {
            if($delivery['expre_status'] == 'false') {
                $delivery['ident'] = $print_data['identInfo']['items'][$delivery['delivery_id']];//1-81030-0209_1
                $printDly[$delivery['shop_id']][$delivery['delivery_id']] = $delivery;
            }
            if(empty($delivery['logi_no'])) {
                $needGetWaybill[] = $delivery['delivery_id'];
            }
            foreach ($delivery['orders'] as $order) {
                if(!($order['bool_extendstatus'] & ome_order_bool_extendstatus::__EXPORT_ORDER)) {
                    $needExport[] = $order;
                }
            }
        }
        if($needExport) {
           
            kernel::single('ome_service_order')->exportOrder($needExport);
        }
        if($printDly) {
            
            kernel::single('ome_event_trigger_shop_delivery')->printThirdBill($printDly);
        }
        if($print_data['errInfo']) {
            $msg = '';
            foreach ($print_data['errInfo'] as $dlyId => $errMsg) {
                $msg .= $print_data['errBns'][$dlyId] . $errMsg . '<br/>';
            }
            $this->splash('error', '',$msg);
        }
        $this->splash('success', 'index.php?app=wms&ctl=admin_vopczc&act=index','操作完成');
    }

    /**
     * message
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function message($msg) {
        $this->pagedata['err'] = 'true';
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['msg'] = $msg;
        $this->display('admin/delivery/message.html');
    }

    /**
     * 获取Waybill
     * @return mixed 返回结果
     */
    public function getWaybill() {
        $model = app::get('wms')->model('delivery_vopczc');
        $pageData = array(
            'billName' => '唯品会仓中仓发货单获取运单号',
            'request_url' => 'index.php?app=wms&ctl=admin_vopczc&act=dealGetWaybill',
            'maxProcessNum' => 10,
            'close' => true
        );
        $this->selectToPageRequest($model, $pageData, $this->_getBaseFilter());
    }

    /**
     * dealGetWaybill
     * @return mixed 返回值
     */
    public function dealGetWaybill() {
        $deliveryId = explode(';', $_POST['ajaxParams']);
        $retArr = array(
            'total' => count($deliveryId),
            'succ' => 0,
            'fail' => 0,
            'fail_msg' => array()
        );
        $dlyRows = app::get('wms')->model('delivery')->getList('*', array('delivery_id'=>$deliveryId));
        $branchDly = array();
        foreach($dlyRows as $val) {
            if($val['logi_no']) {
                $retArr['succ'] ++;
                continue;
            }
            $branchDly[$val['branch_id']][$val['delivery_id']] = $val;
        }
        $retArr['fail'] = count($deliveryId) - count($dlyRows);
        foreach($branchDly as $branchId => $arrDly) {
           
            $rs = kernel::single('ome_event_trigger_shop_delivery')->getDeliveryInfo($branchId, $arrDly);
            foreach($arrDly as $dlyId => $val) {
                if($rs[$dlyId]) {
                    $retArr['succ'] ++;
                } else {
                    $retArr['fail'] ++;
                    $retArr['fail_msg'][] = array(
                        'obj_bn' => $arrDly[$dlyId]['delivery_bn'],
                        'msg' => '获取运单号失败'
                    );
                }
            }
        }
        echo json_encode($retArr);
    }

    
}