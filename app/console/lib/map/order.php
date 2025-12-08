<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2020/12/14 17:11:21
 * @describe: 订单获取坐标
 * ============================
 */
class console_map_order extends console_map_abstract {

    protected function _getAddress($id) {
        $mdl = app::get('ome')->model('orders');
        $order = $mdl->db_dump(array('order_id'=>$id), 'ship_area,ship_addr');
        $area = explode(':', $order['ship_area']);
        $area = explode('/', $area[1]);
        if(empty($area)) {
            return array();
        }
        $orderExtend = app::get('ome')->model('order_extend')->db_dump(array('order_id'=>$id), 'location');
        $sdf = array(
            'id' => $id,
            'city' => $area[1],
            'address' => $area[0] . $area[1] . $area[2] . $order['ship_addr'],
            'location' => $orderExtend['location']
        );
        return $sdf;
    }

    protected function _dealResult($data, $sdf){
        if($data['rsp'] == 'succ') {
            $upData = array(
                'order_id' => $sdf['id'],
                'location' => $data['location']
            );
            app::get('ome')->model('order_extend')->db_save($upData);
        }
    }
}