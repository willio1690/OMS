<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_yitiao_request_delivery extends erpapi_shop_request_delivery
{

	protected function get_confirm_params($sdf)
    {


        $param = array(
            'company_code'	=>	$sdf['logi_type'],
            'logistics_no'	=>	$sdf['logi_no'],
        );
        $logi = [];
        foreach($sdf['orderinfo']['order_objects'] as $order_object){
            if($order_object['oid']) {
                $tmp = $param;
                $tmp['bn'] = $order_object['bn'];
                $tmp['oid'] = $sdf['orderinfo']['order_bn'];
                $logi[] = $tmp;
            }
        }
        return ['logistics_list'=>json_encode($logi)];
    }
}



?>