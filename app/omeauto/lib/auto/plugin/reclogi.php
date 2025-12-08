<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/2/16 15:11:21
 * @describe: 推荐物流
 * ============================
 */

class omeauto_auto_plugin_reclogi extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::_RECLOGI_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(&$group, &$confirmRoles=null) {
        $arrOrder = $group->getOrders();
        $order = current($arrOrder);
        $corp = $group->getDlyCorp();

        list($rs, $msg) = kernel::single('ome_event_trigger_shop_logistics')->judgeRecommend($order, $corp['type']);
        if($rs) {
            return;
        }
        foreach ((array)$arrOrder as $order) {
            $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
        }
        $group->setStatus(omeauto_auto_group_item::__OPT_ALERT, $this->_getPlugName());
    }

     /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '物流停运';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => 'RED', 'flag' => '停' ,'msg' => '常用快递停运，请使用建议快递');
    }
}