<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author liuzecheng<liuzecheng@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_mengdian_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_delivery_apiname($sdf)
    {
        if($sdf['is_virtual']) {
            //虚拟发货
            return SHOP_LOGISTICS_DUMMY_SEND;
        }
        return SHOP_LOGISTICS_OFFLINE_SEND;
    }
    /**
     * 发货请求参数
     *
     * @return void
     * @author
     **/

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        if($sdf['is_virtual']){
            unset($param['company_code']);
            unset($param['company_name']);
            unset($param['logistics_no']);
        }
        $order_id = $sdf['orderinfo']['order_id'];
        $db = kernel::database();
        $shop_id = $db->select('select shop_id from  sdb_ome_orders where order_id='.$order_id);
        $shop_id = $shop_id[0]['shop_id'];
        $shop = $db->select('select shop_id,area,mobile,tel,default_sender from sdb_ome_shop where shop_id="'.$shop_id.'"');
        $temp_area = explode(':',$shop[0]['area']);
        $sender_address = str_replace('/',' ',$temp_area[1]);

        $param['sender_address'] = $sender_address ? $sender_address : '';
        $param['sender_name'] = $shop[0]['default_sender'];
        $param['sender_tel'] = $shop[0]['mobile'] ? $shop[0]['mobile'] : $shop[0]['tel'];
        return $param;
    }


}