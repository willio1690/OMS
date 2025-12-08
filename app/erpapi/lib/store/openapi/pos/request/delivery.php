<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * pos发货单对接shopex pos
 * 
 * @author sunjing@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_openapi_pos_request_delivery extends erpapi_store_request_delivery
{

    /**
     * 发货单创建
     * 因目前shopex 发货业务 用wap端所以继承了原wap业务
     * @return void
     * @author
     **/
    public function delivery_create($sdf)
    {
        $delivery_bn = $sdf['outer_delivery_bn'];

        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }

        #如果 delivery = SHIPED 则自发货
        if($sdf['delivery'] == 'SHIPED'){
            $filter = [
                'store_bn' => $sdf['branch_bn']
            ];
            $storeMdl = app::get('o2o')->model('store');
            $store = $storeMdl->dump($filter, 'store_id');

            $data['status'] = 'delivery';
            $data['delivery_bn'] = $delivery_bn;
            return kernel::single('erpapi_router_response')->set_channel_id($store['store_id'])->set_api_name('store.delivery.status_update')->dispatch($data);
        }

        $title = $this->__channelObj->store['channel_name'] . '发货单添加';

        $params = $this->_format_delivery_create_params($sdf);


        if (!$params) {
            return $this->error('参数为空,终止同步');
        }

       
        return $this->__caller->call(WMS_SALEORDER_CREATE, $params, null, $title, 30, $delivery_bn);
    }
    
    

}
