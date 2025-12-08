<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2020/7/15 10:50:59
 * @describe 订单类型
 */

class omeauto_auto_type_ordertype extends omeauto_auto_type_abstract implements omeauto_auto_type_interface {
    /**
     * 在显示前为模板做一些数据准备工作
     * 
     * @param object $tpl
     * @return void
     */
    public function _prepareUI(& $tpl) {

        $orderType = kernel::single('ome_order_func')->get_order_type();
        $normalOrderType = kernel::single('ome_order_func')->get_normal_order_type();
        foreach ($orderType as $key => $value) {
            if(!in_array($key, $normalOrderType)) {
                unset($orderType[$key]);
            }
        }
        $tpl->pagedata['order_type'] = $orderType;
    }


    # 检查输入的参数
    public function checkParams($params) {

        if (empty($params['order_type']) && !is_array($params['order_type'])) {
            return "你还没有选择相应的订单类型\n\n请勾选以后再试！！";
        }

        return true;
    }

    /**
     * 生成规则字串
     *
     * @param Array $params
     * @return String
     */
    public function roleToString($params) {
        $rows = kernel::single('ome_order_func')->get_order_type();

        $caption = '';
        foreach ($rows as $k => $row) {
            if(in_array($k, $params['order_type'])) {
                $caption .= ", " . $row;
            }
        }
        $caption = sprintf('订单类型为 %s', preg_replace('/^,/is', '', $caption));

        $role = array('role' => 'ordertype', 'caption' => $caption, 'content' => array('order_type'=>$params['order_type']));

        return json_encode($role);
    }

    /**
     * 检查订单数据是否符合要求
     *
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item) {

        if (!empty($this->content)) {
            foreach ($item->getOrders() as $order) {
                if (!in_array(trim($order['order_type']), $this->content['order_type'])) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

}