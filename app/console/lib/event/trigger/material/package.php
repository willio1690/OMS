<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/11/25 17:12:12
 * @describe: 类
 * ============================
 */
class console_event_trigger_material_package {

    /**
     * 创建
     * @param mixed $id ID
     * @return mixed 返回值
     */

    public function create($id) {
        $main = app::get('console')->model('material_package')->db_dump(['id'=>$id], '*');
        if($main['status'] != '2') {
            return [false, ['msg'=>'状态不对']];
        }
        $items = app::get('console')->model('material_package_items')->getList('*', ['mp_id'=>$id]);
        $detail = app::get('console')->model('material_package_items_detail')->getList('*', ['mp_id'=>$id]);
        $sdf = [
            'main'=>$main,
            'items'=>$items,
            'detail'=>$detail
        ];
        $store_id = kernel::single('ome_branch')->isStoreBranch($main['branch_id']);
        if($store_id){
            $channel_type = 'store';
            $channel_id = $store_id;
        }else{
            $wms_id = kernel::single('ome_branch')->getWmsIdById($main['branch_id']);
            $sdf['main']['branch_bn'] = kernel::single('ome_branch')->getBranchBnById($main['branch_id']);
            $channel_type = 'wms';
            $channel_id = $wms_id;
        }
        app::get('ome')->model('operation_log')->write_log('material_package@console',$id,"操作同步");
        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->storeprocess_create($sdf);
        if($result['rsp'] == 'fail') {
            $upData = [];
            $upData['sync_status'] = '3';
            $upData['sync_msg'] = $result['msg'];
            app::get('console')->model('material_package')->update($upData, ['id'=>$id]);
            app::get('ome')->model('operation_log')->write_log('material_package@console',$id,"同步失败:".$result['msg']);
        }

        return [($result['rsp'] == 'fail' ? false : true), ['msg'=>$result['msg']]];
    }

    /**
     * cancel
     * @param mixed $main main
     * @return mixed 返回值
     */
    public function cancel($main) {
        $store_id = kernel::single('ome_branch')->isStoreBranch($main['branch_id']);
        if($store_id){
            $channel_type = 'store';
            $channel_id = $store_id;
        }else{
            $wms_id = kernel::single('ome_branch')->getWmsIdById($main['branch_id']);
            $main['branch_bn'] = kernel::single('ome_branch')->getBranchBnById($main['branch_id']);
            $channel_type = 'wms';
            $channel_id = $wms_id;
        }
        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->storeprocess_cancel($main);
        return [($result['rsp'] == 'fail' ? false : true), ['msg'=>$result['msg']]];
    }
}