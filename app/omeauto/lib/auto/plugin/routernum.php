<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/7/4
 * @describe
 */
class omeauto_auto_plugin_routernum extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__ROUTER_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return array
     */
    public function process(& $group, &$confirmRoles=null) {
        $allow = true;
        #添加默认值，避免缺少路由次数陷入死循环
        $confirmRoles['router_num'] = $confirmRoles['router_num'] > 0 ? (int)$confirmRoles['router_num'] : 9;
        foreach ($group->getOrders() as  $order) {
            $extendModel = app::get('ome')->model('order_extend');
            $extendOrder = $extendModel->db_dump(array('order_id'=>$order['order_id']), 'router_num');
            if($extendOrder['router_num'] >= $confirmRoles['router_num']){
                $allow = false;
                $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                break;
            }
        }

        if(!$allow){
            $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
        }

    }


    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {
        return '路由次数检查';
    }

    /**
     * 获取提示信息
     *
     * @param array $order 订单内容
     * @return array
     */
    public function getAlertMsg(& $order) {
        return array('color' => '#BD794E', 'flag' => '路', 'msg' => '路由次数超过限制');
    }

}
