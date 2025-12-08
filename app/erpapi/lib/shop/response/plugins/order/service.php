<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 延保服务插件
*
* @author sunjing<sunjing@shopex.cn>
* @version $Id: promotion.php 2013-3-12 17:23Z
*/
class erpapi_shop_response_plugins_order_service extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $servicesdf = array();

        if ($platform->_ordersdf['service_order_objects']['service_order']) {
            foreach ((array) $platform->_ordersdf['service_order_objects']['service_order'] as $key => $value) {


                $servicesdf[] = array(
                    'order_id'      =>  $platform->_tgOrder['order_id'],
                    'item_oid'      =>  $value['item_oid'] ,
                    'refund_id'     =>  $value['refund_id'],
                    'sale_price'    =>  $value['sale_price'],
                    'oid'           =>  $value['oid'],
                    'tmser_spu_code'=>  $value['tmser_spu_code'],
                    'num'           =>  $value['num'],
                    'total_fee'     =>  $value['total_fee'],
                    'type_alias'    =>  $value['type_alias'],
                    'title'         =>  $value['title'],
                    'service_id'    =>  $value['service_id'],
                    'type'          =>  $value['type'],
                );
            }
        }



        return $servicesdf;
    }

    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$servicesdf)
    {

        $serviceObj = app::get('ome')->model('order_service');
        $service_price = 0;
        foreach ($servicesdf as $key=>$value){
            $service_price+=$value['total_fee'];
            $servicesdf[$key]['order_id'] = $order_id;
        }
        $sql = ome_func::get_insert_sql($serviceObj,$servicesdf);

        kernel::database()->exec($sql);

        if($service_price>0){
            kernel::database()->exec("UPDATE sdb_ome_orders SET service_price=".$service_price." WHERE order_id=".$order_id);
        }
    }


}