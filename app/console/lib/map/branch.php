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
 * @describe: 仓库获取坐标
 * ============================
 */
class console_map_branch extends console_map_abstract {

    protected function _getAddress($id) {
        $mdl = app::get('ome')->model('branch');
        $branch = $mdl->db_dump(array('branch_id'=>$id), 'area,address,location');
        $area = explode(':', $branch['area']);
        $area = explode('/', $area[1]);
        if(empty($area)) {
            return array();
        }
        $sdf = array(
            'id' => $id,
            'city' => $area[1],
            'address' => $area[0] . $area[1] . $area[2] . $branch['address'],
            'location' => $branch['location']
        );
        return $sdf;
    }

    protected function _dealResult($data, $sdf){
        if($data['rsp'] == 'succ') {
            app::get('ome')->model('branch')->update(array('location'=>$data['location']), array('branch_id'=>$sdf['id']));
        }
    }
}