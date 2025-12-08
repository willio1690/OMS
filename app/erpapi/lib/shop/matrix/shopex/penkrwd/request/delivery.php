<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_penkrwd_request_delivery extends erpapi_shop_matrix_shopex_request_delivery
{
	    /**
     * 添加发货单
     *
     * @return void
     * @author 
     **/
    public function add($sdf){}

    /**
     * 更新发货单流水状态(无需请求)
     *
     * @return void
     * @author 
     **/
    public function deliveryprocess_update($sdf){}

    /**
     * 更新物流公司(无需请求)
     *
     * @return void
     * @author 
     **/
    public function logistics_update($sdf){}

    /**
     * 发货确认(无需请求)
     *
     * @return void
     * @author 
     **/
    public function confirm($sdf,$queue=false){

        if($sdf['type'] == 'reject') return $this->succ('原样寄回，不向平台发送请求');

        return parent::confirm($sdf,$queue);
    }
    
    /**
     * 获取发货接口(默认线下发货)
     *
     * @return void
     * @author 
     **/
    protected function get_delivery_apiname($sdf)
    {
        return SHOP_TRADE_SHIPPING_ADD;
    }

    /**
     * 添加发货单参数
     *
     * @return void
     * @author 
     **/
    protected function get_confirm_params($sdf)
    {
        $param = parent::get_add_params($sdf);

        $param['t_begin'] = $param['t_end'] = $param['modify'] = date('Y-m-d H:i:s',$sdf['last_modified']);

        return $param;
    }
}