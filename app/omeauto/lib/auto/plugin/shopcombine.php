<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 检查是否需要检查同一店铺同一用户购买订单
 *
 * @author sunjing@shopex.cn
 * @version 0.1
 */
class omeauto_auto_plugin_shopcombine extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__COMBINE_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles=null) {
        $allow = true;

        //[获取配置]合并订单条数限制
        $combine_select = app::get('ome')->getConf('ome.combine.select');
        $combine_merge_limit = app::get('ome')->getConf('ome.combine.merge.limit');
        $combine_merge_limit = intval($combine_merge_limit);
        if($combine_select !== '0'){
            $combine_merge_limit = 0; //未开启自动合并订单
        }

        if($this->_checkStatus($confirmRoles)){
            $orders = $group->getOrders();
            if (!empty($orders) && is_array($orders)) {
                $key = key($orders);
                
                #系统自动审单的订单,返回true
                if ($orders[$key]['is_sys_auto_combine'] === true){
                    return true;
                }

                //合并订单条数限制
                if($combine_merge_limit > 0) {
                    if(count($orders) == $combine_merge_limit) {
                        return true;
                    }
                }

                if(empty($orders[$key]['ship_addr'])) {
                    return true;
                }
                
                $member_id = $orders[$key]['member_id'];
                $shop_id = $orders[$key]['shop_id'];
                $data = array();
                $data['ship_name'] = $orders[$key]['ship_name'];
                $data['ship_mobile'] = $orders[$key]['ship_mobile'];
                $data['ship_area'] = $orders[$key]['ship_area'];
                $data['ship_addr'] = $orders[$key]['ship_addr'];
                $data['is_cod'] = $orders[$key]['is_cod'];
                $data['shop_type'] = $orders[$key]['shop_type'];
                $data['shop_id'] = $shop_id;
                $data['member_id'] = $member_id;
                if (kernel::single('ome_order_bool_type')->isCPUP($orders[$key]['order_bool_type'])) {
                    $data['shipping_name'] = $orders[$key]['shipping'];
                    $data['store_code']    = current($orders[$key]['objects'])['store_code'];
                    $data['cpup_service']  = $orders[$key]['cpup_service'];
                }
                $count = kernel::single('omeauto_auto_combine')->getCombineShopMemberCount($data);
                unset($data);
                
                if ($count>count($group->getOriginalOrders())) {
                    $allow = false;
                    
                    foreach ($orders as $order) {
                        $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                    }
                }
            }
            if(!$allow){

                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            }
        }
    }

    /**
     * 检查是否启用检查
     */
    private function _checkStatus($configRoles) {
        return true;
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {
       return '可合并订单';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {
        return array('color' => 'RED', 'flag' => '疑', 'msg' => '疑与其它订单可合并');
    }

    
}
