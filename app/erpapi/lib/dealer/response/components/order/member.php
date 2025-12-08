<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 订单会员
*
* @author chenping<chenping@shopex.cn>
* @version $Id: tax.php 2013-3-12 17:23Z
*/
class erpapi_dealer_response_components_order_member extends erpapi_dealer_response_components_order_abstract
{
    /**
     * 添加订单会员
     *
     * @return void
     **/

    public function convert()
    {
        $member_info = $this->_platform->_ordersdf['member_info'];
        $shop_id = $this->_platform->__channelObj->channel['shop_id'];
        
        unset($member_info['member_id']);
        
        if ($member_info) {
            $member_info['shop_type'] = $this->_platform->__channelObj->channel['shop_type'];
            $member_info['consignee'] = $this->_platform->_ordersdf['consignee'];
            $member_id = kernel::single('ome_member_func')->save($member_info,$shop_id);
            if ($member_id) {
                $this->_platform->_newOrder['member_id'] = $member_id;
            }
        }
    }
    
    /**
     * 更新订单会员
     *
     * @return void
     **/
    public function update()
    {
        $member_info = $this->_platform->_ordersdf['member_info'];
        $shop_id = $this->_platform->__channelObj->channel['shop_id'];
        
        //unset
        unset($member_info['member_id']);
        
        //member
        if ($member_info) {
            $member_id = kernel::single('ome_member_func')->save($member_info, $shop_id, $this->_platform->_tgOrder['member_id']);
            if ($member_id != $this->_platform->_tgOrder['member_id']) {
                $this->_platform->_newOrder['member_id'] = $member_id;
            }
        }
    }
}