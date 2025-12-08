<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeauto_auto_plugin_checksplitgift extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__CHECKSPLITGIFT_CODE;

    private $__MUST_CHECK_GIFT = ['kuaishou','taobao'];

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles=null) {
        $allow = false;
        if($this->_checkStatus($confirmRoles, $group)){
            foreach ($group->getOrders() as  $order) {
                foreach ($order['objects'] as $object) {
                    foreach ($object['items'] as $item) {
                        if($item['item_type'] != 'gift') {
                            $allow = true;
                        }
                    }
                }
            }
    
            //检测objects层标识，是否有顺手购标识
            foreach ($group->getOrders() as $order) {
                foreach ($order['objects'] as $object) {
                    if (!kernel::single('ome_order_bool_objecttype')->isActivityPurchase($object['object_bool_type'])) {
                        $allow = true;
                    }
                }
            }
        
            if(!$allow){
                foreach ($group->getOrders() as $order) {
                    $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                }
                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            }
        }
    }
    /**
     * 获取配置信息
     *
     * @param void
     * @return array
     */
    private function _checkStatus($configRoles, $group) {
        // 拆单后不允许单独发礼品的，返回true
        foreach ($group->getOrders() as  $order) {
            if (in_array($order['shop_type'], $this->__MUST_CHECK_GIFT) && $order['process_status'] == 'unconfirmed') {
                foreach ($order['objects'] as $objects) {
                    if ($objects['obj_type'] == 'gift' && $objects['main_oid']) {
                        return true;
                    }
                    
                    //顺手购
                    if (kernel::single('ome_order_bool_objecttype')->isActivityPurchase($objects['object_bool_type'])) {
                        return true;
                    }
                }
            }
        }    
        $osg = app::get('ome')->getConf('ome.order.split.gift');
        if ($osg == '1') {
           return true;
        }else{
            return false;
        }
    }   


    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {
        return '仅赠品不生成发货单';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {
        return array('color' => 'RED', 'flag' => '仅', 'msg' => '仅赠品不生成发货单');
    }

}
