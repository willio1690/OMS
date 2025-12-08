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
class erpapi_shop_matrix_youzan_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_confirm_params($sdf) {
        $param = parent::get_confirm_params($sdf);
        
        // 拆单子单回写
        if ($sdf['is_split'] == 1) {
            $param['is_split'] = $sdf['is_split'];
            $param['oids']  = implode(',',$sdf['oid_list']);
            
            if ($sdf['switch']){
                $param['switch']   = $sdf['switch'];
            }
            
            if ($sdf['is_single_item_send']) {
                $param['is_single_item_send'] = $sdf['is_single_item_send'];
            }
            
            $packages_list = array();
            foreach ($sdf['packages'] as $key => $value) {
                $packages = [
                    'oid'=>$key,
                    'total_num'=>array_sum(array_column($value, 'num')),
                    'packages' => json_encode($value),
                ];
                $packageData = array_merge($packages,$param);
                $packages_list[] = $packageData;
            }
            
            if ($packages_list) $param['packages_list'] = $packages_list;
        }
        
        if ($sdf['is_virtual'] || preg_match('/^VLN\d+F$/', $sdf['logi_no'])) { //虚拟发货
            $param['is_no_express'] = '1';
        }
        
        return $param;
    }
}