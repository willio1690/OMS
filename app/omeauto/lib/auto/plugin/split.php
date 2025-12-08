<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2020/7/13 14:09:16
 * @describe 直接拆单
 */
class omeauto_auto_plugin_split extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface
{

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::_SPLIT_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @param array $confirmRoles autoconfirm中的config
     * @return void
     */
    public function process(&$group, &$confirmRoles=null)
    {
        $branchId = $group->getBranchId();

        // 指定仓发货
        if ($branchId[0] == 'sys_appoint') {
            $group->setProcessSplit();
            $startTime = microtime(true);
            list($rs, $msg, $code) = kernel::single('omeauto_split_router', 'branchappoint')->splitOrder($group, '');
            if ($code == 'no branch') {
                foreach ($group->getOrders() as $order) {
                    $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                }

                $this->writeFailLog($group->getOrders(), $msg, $startTime);

                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
                return;
            }
        }
    
        //检测仓库是否管控库存
        //检测$branchId 是否只有一个仓库 是并且为库存管控
        $branchId = $group->getBranchId();
        if (is_array($branchId) && count($branchId) == 1) {
            $isCtrlStore = kernel::single('ome_branch')->getBranchCtrlStore(reset($branchId));
            if ($isCtrlStore === false) {
                $group->setConfirmBranch(true);
                return;
            }
        }
        
        // 门店仓不跑拆单
        if ($group->isStoreBranch()) {
            return;
        }

        //是否启动拆单
        $orderSplitLib = kernel::single('ome_order_split');
        $split_seting  = $orderSplitLib->get_delivery_seting();
        $split_model   = $split_seting ? 2 : 0; //自由拆单方式split_model=2
        if (!$split_model) {
            return;
        }

        // 货到付款和门店自提/猫超的不拆单
        foreach ($group->getOrders() as $order) {
            if ($order['is_cod'] == 'true') {
                return;
            }
            if ($order['shipping'] == 'STORE_SELF_FETCH') {
                return;
            }
            if ($order['shop_type'] == "taobao" && $order['order_source'] == 'maochao') {
                return;
            }
            if ($order['shop_type'] == "zkh") {
                return;
            }
        }

        $split = app::get('omeauto')->model('order_split')->dump(array('sid' => intval($confirmRoles['split_id'])));
        if ($split && $split['split_type']) {
            $splitType   = $split['split_type'];
            $splitConfig = $split['split_config'];

            // 手工审单/自动审单标识
            $splitConfig['confirm_source'] = $confirmRoles['source'];

            $group->setProcessSplit();
            
            //开始拆单
            $startTime = microtime(true);
            list($rs, $msg) = kernel::single('omeauto_split_router', $splitType)->splitOrder($group, $splitConfig);
            if (!$rs) {
                foreach ($group->getOrders() as $order) {
                    $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                }
                $this->writeFailLog($group->getOrders(), $msg, $startTime);
                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            }
        }
    }

    protected function writeFailLog($arrOrder, $msg, $startTime = null)
    {
        $orderBn = array();
        foreach ($arrOrder as $v) {
            $orderBn[] = $v['order_bn'];
        }
        $apilogModel        = app::get('ome')->model('api_log');
        $log_id             = $apilogModel->gen_id();
        $result             = array();
        $result['msg']      = $msg;
        $result['order_bn'] = $orderBn;
        
        $currentTime = microtime(true);
        $spendTime = $startTime ? ($currentTime - $startTime) : 0;
        
        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => '拆单结果',
            'status'        => 'fail',
            'worker'        => 'order.split',
            'params'        => json_encode(array('order.split', array('arrorder' => $arrOrder))),
            'transfer'      => '',
            'response'      => json_encode($result),
            'msg'           => $msg,
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => $orderBn[0],
            'createtime'    => $currentTime,
            'last_modified' => $currentTime,
            'msg_id'        => '',
            'spendtime'     => $spendTime,
            'url'           => '',
        );
        $apilogModel->insert($logsdf);
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle()
    {
        return '拆单检查';
    }

    /**
     * 获取提示信息
     *
     * @param array $order 订单内容
     * @return array
     */
    public function getAlertMsg(&$order)
    {
        return array('color' => '#44607B', 'flag' => '拆', 'msg' => '无法拆单');
    }

}
