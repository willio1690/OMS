<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @description 库存管理类
 * @access public
 * @author xiayuanjun@shopex.cn
 * @ver 0.1
 */
class ome_store_manage
{

    /**
     * 
     * 静态私有变量仓库类型
     * @var array
     */
    private static $__branchType = '';

    /**
     * 
     * 静态私有变量仓库类型白名单定义
     * @var array
     */

    private static $__branchTypes = array(
        'branch' => array('type' => 'branch', 'desc' => "电商仓"),
        'store'  => array('type' => 'store', 'desc' => "门店"),
    );

    /**
     * 
     * 静态私有变量处理模式对象
     * @var array
     */
    private static $__processObj = null;

    /**
     * 
     * 静态私有变量事件节点
     * @var array
     */
    private static $__node = '';

    /**
     * 
     * 静态私有变量事件节点列表
     * @var array
     */
    private static $__nodeTypes = array(
        'addDly'                   => array('method' => 'addDly', 'desc' => "新建发货单"),
        'cancelDly'                => array('method' => 'cancelDly', 'desc' => "取消发货单"),
        'consignDly'               => array('method' => 'consignDly', 'desc' => "发货"),
        'pauseOrd'                 => array('method' => 'pauseOrd', 'desc' => "暂停订单"),
        'renewOrd'                 => array('method' => 'renewOrd', 'desc' => "恢复订单"),
        'createChangeReturn'       => array('method' => 'createChangeReturn', 'desc' => "创建换货申请单"),
        'deleteChangeReturn'       => array('method' => 'deleteChangeReturn', 'desc' => "同意或取消换货申请单"),
        'checkChangeReship'        => array('method' => 'checkChangeReship', 'desc' => "审核换货单"),
        'refuseChangeReship'       => array('method' => 'refuseChangeReship', 'desc' => "收货质检和wap换货单确认拒绝"),
        'confirmReshipReturn'      => array('method' => 'confirmReshipReturn', 'desc' => "收货质检和wap退换货单确认的退入"),
        'confirmReshipChange'      => array('method' => 'confirmReshipChange', 'desc' => "收货质检和wap换货单确认的换出"),
        'reshipReturnRefuseChange' => array('method' => 'reshipReturnRefuseChange', 'desc' => "换货单回传拒绝"),
        'editChangeToReturn'       => array('method' => 'editChangeToReturn', 'desc' => "最终收货确认由换货变为退货"),
        'checkReturned'            => array('method' => 'checkReturned', 'desc' => "审核采购退货"),
        'finishReturned'           => array('method' => 'finishReturned', 'desc' => "最终处理采购退货"),
        'cancelReturned'           => array('method' => 'cancelReturned', 'desc' => "取消采购退货"),
        'checkStockout'            => array('method' => 'checkStockout', 'desc' => "审核调拨出库单"),
        'finishStockout'           => array('method' => 'finishStockout', 'desc' => "最终处理调拨出库单"),
        'saveStockdump'            => array('method' => 'saveStockdump', 'desc' => "保存库内转储单"),
        'finishStockdump'          => array('method' => 'finishStockdump', 'desc' => "最终处理库内转储单"),
        'checkVopstockout'         => array('method' => 'checkVopstockout', 'desc' => "审核唯品会出库单"),
        'finishVopstockout'        => array('method' => 'finishVopstockout', 'desc' => "最终处理唯品会出库单"),
        'artificialFreeze'         => array('method' => 'artificialFreeze', 'desc' => "人工库存预占"),
        'artificialUnfreeze'       => array('method' => 'artificialUnfreeze', 'desc' => "人工库存预占释放"),
        'changeStore'              => array('method' => 'changeStore', 'desc' => '更新库存'),
        'changeStoreBatch'         => array('method' => 'changeStoreBatch', 'desc' => '批量更新库存'),
        'getAvailableStore'        => array('method' => 'getAvailableStore', 'desc' => '获取可用库存'),
        'changeArriveStore'        => array('method' => 'changeArriveStore', 'desc' => '在途库存'),
        'deleteArriveStore'        => array('method' => 'deleteArriveStore', 'desc' => '在途库存释放'),
        'getStoreByBranch'         => array('method' => 'getStoreByBranch', 'desc' => '获取仓库库存'),
        'addAdjust'                => array('method' => 'addAdjust', 'desc' => "新建库存调整单"),
        'confirmAdjust'            => array('method' => 'confirmAdjust', 'desc' => "确认库存调整单"),
        'cancelAdjust'             => array('method' => 'cancelAdjust', 'desc' => "取消库存调整单"),
        'addDifference'            => array('method' => 'addDifference', 'desc' => "新建差异单"),
        'confirmDifference'        => array('method' => 'confirmDifference', 'desc' => "确认差异单"),
        'cancelDifference'         => array('method' => 'cancelDifference', 'desc' => "取消差异单"),
        'confirmMaterialPackage'   => array('method' => 'confirmMaterialPackage', 'desc' => "确认加工单"),
        'finishMaterialPackage'    => array('method' => 'finishMaterialPackage', 'desc' => "完成加工单"),
        'cancelMaterialPackage'    => array('method' => 'cancelMaterialPackage', 'desc' => "取消加工单"),
    );

    /**
     * 
     * 静态私有变量是否管控库存
     * @var array
     */
    private static $__isCtrlStore = '';

    /**
     * 
     * 静态私有变量应用级参数
     * @var array
     */
    private static $_appParams = array();

    /**
     * isCtrlBranchStore
     * @return mixed 返回值
     */
    public function isCtrlBranchStore()
    {
        return self::$__isCtrlStore;
    }

    /**
     * isStoreBranch
     * @return mixed 返回值
     */
    public function isStoreBranch()
    {
        return (self::$__branchType == 'store') ? true : false;
    }

    /**
     * 处理BranchStore
     * @param mixed $params 参数
     * @param mixed $err_msg err_msg
     * @return mixed 返回值
     */
    public function processBranchStore($params, &$err_msg)
    {
    
        //识别是否管控库存
        if(self::$__isCtrlStore === false){
            return true;
        }
        
        //必要参数检查
        if (!$this->checkParams($params)) {
            return false;
        }

        //识别处理事件节点
        if (!$this->identifyNode($params)) {
            return false;
        }

        // 判断对象是否加载成功
        if (!is_object(self::$__processObj)){
            return false;
        }


        //执行模式下的库存处理
        $method = self::$__node;
        return self::$__processObj->$method(self::$_appParams, $err_msg);
    }

    /**
     * 加载库存的处理模式
     * 
     * @param array $params
     * @return boolean true/false
     */
    public function loadBranch($params)
    {

        //识别仓库类型
        if (!$this->identifyBranchType($params)) {
            return false;
        }

        //识别库存管控
        $this->identifyCtrlStore($params);

        $class_name = sprintf('ome_store_manage_%s', self::$__branchType);
        try {
            if (class_exists($class_name)) {
                self::$__processObj = kernel::single($class_name, self::$__isCtrlStore);
                return self::$__processObj;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 识别仓库类型
     * 
     * @param array $params
     * @return boolean true/false
     */
    private function identifyBranchType($params)
    {

        if (isset($params['branch_type']) && isset(self::$__branchTypes[$params['branch_type']])) {
            self::$__branchType = self::$__branchTypes[$params['branch_type']]['type'];
        } elseif (isset($params['branch_id'])) {
            $branchObj  = app::get('ome')->model('branch');
            $branchInfo = $branchObj->db->selectrow("SELECT branch_id,b_type FROM sdb_ome_branch WHERE branch_id='" . $params['branch_id'] . "'");
            if ($branchInfo) {
                if ($branchInfo['b_type'] == 1) {
                    //电商仓
                    self::$__branchType = 'branch';
                } elseif ($branchInfo['b_type'] == 2) {
                    //门店线下仓
                    self::$__branchType = 'branch';
                }
            }
        }

        //识别不了当前处理仓库类型
        if (!self::$__branchType) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 识别仓库是否管控库存
     * 
     * @param array $params
     * @return boolean true/false
     */
    private function identifyCtrlStore($params)
    {

        self::$__isCtrlStore = true;
    
        //检测仓库是否管控库存
        $isCtrlStore = kernel::single('ome_branch')->getBranchCtrlStore($params['branch_id']);
        if($isCtrlStore === false){
            self::$__isCtrlStore = false;
        }
        /*
        //如果全局不管控供货关系即门店不管控库存
        $supply_relation = app::get('o2o')->getConf('o2o.ctrl.supply.relation');
        if ($supply_relation == 'true') {
            //判断当前模式是否管控库存，电商仓默认就是管控库存
            if (self::$__branchType == 'store') {
                $storeObj  = app::get('o2o')->model('store');
                $storeInfo = $storeObj->db->selectrow("SELECT is_ctrl_store FROM sdb_o2o_store WHERE branch_id='" . $params['branch_id'] . "'");
                if ($storeInfo) {
                    //如果门店指定不管控库存
                    if ($storeInfo['is_ctrl_store'] == 2) {
                        self::$__isCtrlStore = false;
                    }
                }
            }
        } else {
            self::$__isCtrlStore = false;
        }*/
    }

    /**
     * 识别库存处理的事件节点
     * 
     * @param array $params
     * @return boolean true/false
     */
    private function identifyNode($params)
    {

        if (isset($params['node_type']) && isset(self::$__nodeTypes[$params['node_type']])) {
            self::$__node = self::$__nodeTypes[$params['node_type']]['method'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查传入参数
     * 
     * @param array $params
     * @return boolean true/false
     */
    private function checkParams($params)
    {

        self::$_appParams = $params['params'];

        return true;
    }

}
