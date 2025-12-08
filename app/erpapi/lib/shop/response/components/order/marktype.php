<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 订单备注旗标
*
* @author chenping<chenping@shopex.cn>
* @version $Id: marktype.php 2013-3-12 17:23Z
*/
class erpapi_shop_response_components_order_marktype extends erpapi_shop_response_components_order_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {
        if ($this->_platform->_ordersdf['mark_type']) {
            $this->_platform->_newOrder['mark_type'] = $this->_platform->_ordersdf['mark_type'];
        }
    }
    
    /**
     * 更新订单旗标
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        if ($this->_platform->_ordersdf['mark_type'] && $this->_platform->_ordersdf['mark_type'] != $this->_platform->_tgOrder['mark_type']) {
            $this->_platform->_newOrder['mark_type'] = $this->_platform->_ordersdf['mark_type'];
        }
    }
}