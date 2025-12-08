<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
*
* @author sunjing<sunjing@shopex.cn>
* @version $Id: 2020-2-4
*/
class erpapi_shop_response_plugins_order_weimobvo2o extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $o2oData = array();


        if ($platform->_ordersdf['o2o_info']){
            $o2o_info = $platform->_ordersdf['o2o_info'];
            if ($o2o_info){
                $o2oData=array(
                    'store_code'    =>  $o2o_info['store_code'],
                    'store_name'    =>  $o2o_info['store_name'],
                );
            }
        }

       
        return $o2oData;
    }

    /**
     *
     * @return 
     * @author
     **/
    public function postCreate($order_id,$o2oData)
    {
        $orderObj = app::get('ome')->model('orders');
        
        if ($o2oData){
           
            $extendObj = app::get('ome')->model('order_extend');
            $extend_data =array(
                'o2o_store_bn'  =>  $o2oData['store_code'],
                'o2o_store_name'=>  $o2oData['store_name'],
                'order_id'      =>  $order_id,
            );

            $extendObj->save($extend_data);

            
        }
        


    }

    
}
