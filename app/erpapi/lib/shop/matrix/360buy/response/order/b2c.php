<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东平台
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_360buy_response_order_b2c extends erpapi_shop_matrix_360buy_response_order
{
    /**
     * 是否接收订单
     *
     * @return bool
     **/

    protected function _canAccept()
    {
        if ($this->_ordersdf['t_type'] == 'fenxiao' || $this->_ordersdf['order_source'] == 'taofenxiao') {
            $this->__apilog['result']['msg'] = '分销订单暂时不接收';
            return false;
        }
        
        //检查京东代销平台
        if($this->__channelObj->channel['business_type'] == 'dx'){
            $this->__apilog['result']['msg'] = '不是京东代销平台的订单,不接收!';
            return false;
        }
        
        //parent
        return parent::_canAccept();
    }
}
