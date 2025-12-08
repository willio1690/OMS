<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author by mxc <maxiachen@shopex.cn> 
 * @describe 环球捕手
 */

class erpapi_shop_matrix_gs_request_delivery extends erpapi_shop_request_delivery{

	protected function get_confirm_params(&$sdf){
        $param = parent::get_confirm_params($sdf);
        $param['logistics_list'] = array(
        	array(
        		'company_code'	=>	$param['company_code'],
        		'company_name'	=>	$param['company_name'],
        		'logistics_no'	=>	$param['logistics_no'],
        	),
        );
        unset($param['company_code']);
        unset($param['company_name']);
        unset($param['logistics_no']);

        if (isset($sdf['delivery_bill_list']) && $sdf['delivery_bill_list']) {
        	$param['logistics_list'] = array_merge($param['logistics_list'],$sdf['delivery_bill_list']);
        }
        $param['logistics_list'] = json_encode($param['logistics_list']);

        return $param;
    }

}