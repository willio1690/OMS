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
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_wdwd_request_delivery extends erpapi_shop_request_delivery
{
	protected function get_confirm_params($sdf){
        $param = parent::get_confirm_params($sdf);
        #属于拆单
        if($sdf['is_split'] == 2){
            $param['is_split'] =  1;
        }else{
            $param['is_split'] = 0;
        }
        $item_list = array();
        $object = array();
        $bns = array();
        $ralation_bn = array();
        #货号和oid一一对应
        foreach((array) $sdf['orderinfo']['order_objects'] as $object){
            $ralation_bn[$object['bn']] = $object['oid'];
        }
        foreach ((array) $sdf['delivery_items'] as $item) {
            $item_list[] = array(
                'oid'          => $ralation_bn[$item['bn']],
                'num'          => (int)$item['number']
            );
        }
        $param['item_list'] = json_encode($item_list);
        return $param;
    } 
}