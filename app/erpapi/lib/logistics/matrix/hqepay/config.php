<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * User: jintao Date: 2016/7/6
 */
class erpapi_logistics_matrix_hqepay_config extends erpapi_logistics_matrix_config
{
    protected $_to_node_id = '1227722633';
    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params)
    {
        $account      = explode('|||', $this->__channelObj->channel['shop_id']);
        $stoObj       = kernel::single('logisticsmanager_waybill_hqepay');
        $query_params = array(
            'customer_name' => $account[0], #电子面单客户账号
            'customer_pwd'  => $account[1], #电子面单密码
            'pay_type'      => $account[2], #邮费支付方式,1 现金 2 到付 3 第三方支付
            'exp_type'      => $account[4] ? $account[4] : 1, #快递类型

            'month_code'    => $account[3], #月结编号
            'node_type'     => 'hqepay',
            'to_node_id'    => $this->_to_node_id,

        );

        // 查询绑定的快递鸟
        $channel = app::get('channel')->model('channel')->dump([
            'channel_type' => 'kuaidi',
            'node_type'    => 'kdn',
            'filter_sql'   => 'node_id IS NOT NULL AND node_id!=""',
        ]);

        $query_params['node_type']  = 'kdn';
        $query_params['to_node_id'] = $channel['node_id'];

        $pqp          = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
        return $query_params;
    }
}
