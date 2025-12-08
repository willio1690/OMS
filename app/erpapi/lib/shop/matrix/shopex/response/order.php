<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单接口处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_response_order extends erpapi_shop_response_order
{
    /**
     * 可接收未付款订单
     *
     * @var string
     **/
    protected $_accept_unpayed_order = true;

    /**
     * 创建订单的插件
     *
     * @return void
     * @author 
     **/

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        // 如果是0元订单，注销支付单插件
        if (bccomp('0.000', $this->_ordersdf['total_amount'],3) == 0) {
            $key = array_search('payment', $plugins);
            if ($key !== false) {
                unset($plugins[$key]);
            }
        }

        if (false === array_search('orderextend', $plugins)) {
            $plugins[] = 'orderextend';
        }

        return $plugins;
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'promotion';
        $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        $plugins[] = 'cod';

        return $plugins;
    }

    /**
     * 更新接收,以前端状态为主
     *
     * @return void
     * @author 
     **/
    protected function _canUpdate()
    {
        if (!in_array($this->_ordersdf['status'], array('active','finish','close','dead'))) {
            $this->__apilog['result']['msg'] = '不明订单状态不接收';
            return false;
        }

        if ($this->_ordersdf['status'] == 'close') {
            $this->__apilog['result']['msg'] = '关闭订单不接收';
            return false;
        }

        if ($this->_tgOrder['status'] == 'dead') {
            $this->__apilog['result']['msg'] = 'ERP取消订单，不做更新';
            return false;
        }

        if ($this->_update_accept_dead_order === false && $this->_ordersdf['status'] == 'dead') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }

        if ($this->_ordersdf['ship_status'] == '0' &&  $this->_tgOrder['ship_status'] != '0') {
            $this->__apilog['result']['msg'] = 'ERP订单已发货，不做更新';
            return false;
        }

        return true;
    }

    protected function _analysis()
    {
        parent::_analysis();

        // 判断是否有退款
        if ($this->_ordersdf['payed'] > $this->_ordersdf['total_amount']) {
            $this->_ordersdf['pay_status'] = '6';
            $this->_ordersdf['pause']      = 'true';
        }

        foreach ($this->_ordersdf['order_objects'] as &$object) {

            // 预约发货设置hold时间并且为YYYY-MM-DD HH:MM:SS格式
            if ($object['estimate_con_time'] && $ecshopx_strtotime = strtotime($object['estimate_con_time'])) {
                $object['estimate_con_time'] = $ecshopx_strtotime;
            }
            
            foreach ($object['order_items'] as &$item) {
                if ($item['estimate_con_time'] && $ecshopx_strtotime = strtotime($item['estimate_con_time'])) {
                    $item['estimate_con_time'] = $ecshopx_strtotime;
                    $object['estimate_con_time'] = $ecshopx_strtotime;
                }
            }
        }
    }
}