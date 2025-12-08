<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_reship{

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 添加退货单
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function reship($reship_id){
        $reshipModel = $this->app->model('reship');
        $reship = $reshipModel->dump($reship_id);
        kernel::single('erpapi_router_request')->set('shop', $reship['shop_id'])->aftersale_addReship($reship);
    }
    
    /**
     * 退货单状态更新
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function update_status($reship_id){
        $reshipModel = $this->app->model('reship');
        $reship = $reshipModel->dump($reship_id);
        kernel::single('erpapi_router_request')->set('shop', $reship['shop_id'])->aftersale_updateReshipStatus($reship);
    }
}