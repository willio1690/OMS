<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 华强宝处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_hqepay_request_hqepay extends erpapi_hqepay_request_abstract
{
    const _TO_NODE_ID = '1227722633';

    #订阅华强宝(订单分发的信息(具体订单到分给了的网点，业务员的信息))
    /**
     * pub
     * @param mixed $sdf sdf
     * @param mixed $queue queue
     * @return mixed 返回值
     */

    public function pub($sdf, $queue = false)
    {

        $args = func_get_args();
        array_pop($args);
        $_in_mq = $this->__caller->caller_into_mq('hqepay_pub', 'hqepay', $this->__channelObj->channel['hqepay_id'], $args, $queue);
        if ($_in_mq) {
            return $this->succ('成功放入队列');
        }

        $params               = $sdf;
        //$params['node_type']  = 'hqepay';
        //$params['to_node_id'] = self::_TO_NODE_ID;
        $title                = sprintf('添加物流订阅[%s]', $sdf['logistic_code']);

        $callback = array(
            'class'  => get_class($this),
            'method' => 'callback',
            'params' => array(
                'obj_bn' => $sdf['delivery_bn'],
            ),
        );


        return $this->__caller->call(SHOP_LOGISTICS_SUBSCRIBE, $params, $callback, $title, 10, $sdf['delivery_bn']);
    }

    /**
     * 物流查询
     *
     * @return void
     * @author
     **/
    public function query($sdf)
    {
        $params = array(
            //'to_node_id'    => self::_TO_NODE_ID,
            'tid'           => $sdf['order_bn'],
            'company_code'  => $sdf['logi_code'],
            'company_name'  => $sdf['company_name'],
            'logistic_code' => $sdf['logi_no'],
        );
        if ($sdf['customer_name']) $params['customer_name'] = $sdf['customer_name'];

        $title = sprintf('查询物流信息[%s]', $params['logistic_code']);

        return $this->__caller->call(LOGISTICS_TRACE_DETAIL_GET, $params, array(), $title, 5, $params['logistic_code']);
    }

}
