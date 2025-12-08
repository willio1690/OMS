<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 调拨处单理类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:56:04+08:00
 */
class erpapi_front_response_process_o2o_inventory
{
    /**
     * 盘点单创建
     *
     * @return void
     * @author
     **/

    public function add($data)
    {

        kernel::database()->beginTransaction();

        $invMdl = app::get('o2o')->model('inventory');

        $oper = kernel::single('ome_func')->getDesktopUser();

        $inv = array(
            "inventory_bn"   => $invMdl->get_inventory_bn(),
            "inventory_type" => '3',
            "op_id"          => $oper['op_id'],
            "confirm_op_id"  => $oper['op_id'],
            "createtime"     => time(),
            "branch_id"      => $data['branch_id'],
        );
        if (!$invMdl->save($inv)) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => $invMdl->db->errorinfo());
        }

        $bpModel       = app::get('ome')->model('branch_product');
      
        $bm_id_list = array_column($data['items'], 'bm_id');
        $branch_id  = $inv['branch_id'];

        $import_data = array(
            'sdfdata' => array(
                'inv_id'    => $inv['inventory_id'],
                'branch_id' => $inv['branch_id'],
                'products'  => array(),
            ),
        );

        // 盘点创建
        foreach ($data['items'] as $key => $value) {
            $import_data['sdfdata']['products'][] = array(
                'bm_id'              => $value['bm_id'],
                'accounts_num'       => $value['nums'],
                'accounts_share_num' => 0,
            );
        }
        kernel::single('o2o_inventory_import')->run($cursor_id, $import_data);

        // 盘点确认
        $pstore_list = array();
        foreach ($bpModel->getList('*', array('product_id' => $bm_id_list, 'branch_id' => $branch_id)) as $value) {
            $pstore_list[$value['branch_id']][$value['product_id']] = $value;
        }

       
        $confirm_data = array(
            'sdfdata' => array(
                'rs_inventory'  => $inv,
                'confirm_op_id' => $oper['op_id'],
                'products'      => array(),
            ),
        );
        foreach ($data['items'] as $key => $value) {
            $pstore = $pstore_list[$branch_id][$value['bm_id']];

            $confirm_data['sdfdata']['products'][] = array(
                'id'          => (int) $pstore['id'],
                'bm_id'       => $value['bm_id'],
                'store'       => $value['nums'],
                'share_store' => 0,
            );

            
        }
        kernel::single('o2o_inventory_confirm')->run($cursor_id, $confirm_data, $errmsg);

        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }

    /**
     * 盘点列表查询
     *
     * @return void
     * @author
     **/
    public function listing($filter)
    {
        $invMdl     = app::get('o2o')->model('inventory');
        $invItemMdl = app::get('o2o')->model('inventory_items');

        $offset = $filter['offset'];
        $limit  = $filter['limit'];

        unset($filter['offset'], $filter['limit']);

        $inv_list = $invMdl->getList('*', $filter, $offset, $limit, 'inventory_id DESC');
        if (!$inv_list) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $op_id_list        = array(0);
        $inventory_id_list = array(0);
        foreach ($inv_list as $key => $value) {

            $op_id_list[]        = $value['op_id'];
            $op_id_list[]        = $value['confirm_op_id'];
            $inventory_id_list[] = $value['inventory_id'];
        }

        // 盘点明细
        $item_list  = array();
        $bm_id_list = array(0);
        foreach ($invItemMdl->getList('*', array('inventory_id' => $inventory_id_list)) as $key => $value) {
            $item_list[$value['inventory_id']][] = $value;

            $bm_id_list[] = $value['bm_id'];
        }

        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name,cat_id', array('bm_id' => $bm_id_list));
        $bm_list = array_column($bm_list, null, 'bm_id');

        // 基础
        $bm_id_list = array_column($bm_id_list, 'product_id');
        $brandMdl   = app::get('ome')->model('brand');
        $bmExtMdl   = app::get('material')->model('basic_material_ext');
        $bmMdl      = app::get('material')->model('basic_material');
        $catMdl     = app::get('material')->model('basic_material_cat');

        $cat_id_list = array_column($bm_list, 'cat_id');
        $cat_list    = $catMdl->getList('cat_id,cat_name', array('cat_id' => $cat_id_list));
        $cat_list    = array_column($cat_list, null, 'cat_id');

        $bm_ext_list = $bmExtMdl->getList('*', array('bm_id' => $bm_id_list));
        $bm_ext_list = array_column($bm_ext_list, null, 'bm_id');

        $brand_id_list = array_column($bm_ext_list, 'brand_id');
        $brand_list    = $brandMdl->getList('brand_id,brand_name', array('brand_id' => $brand_id_list));
        $brand_list    = array_column($brand_list, null, 'brand_id');

        // 盘点的门店
        $branch_id_list = array_column($inv_list, 'branch_id');
        $branch_list    = app::get('ome')->model('branch')->getList('*', array('branch_id' => $branch_id_list, 'check_permission' => 'false'));
        $branch_list    = array_column($branch_list, null, 'branch_id');

        // 操作人
        $userMdl   = app::get('desktop')->model('users');
        $user_list = $userMdl->getList('user_id,name', array('user_id' => $op_id_list));
        $user_list = array_column($user_list, null, 'user_id');

        $data['invs'] = array();
        foreach ($inv_list as $key => $value) {
            $branch       = $branch_list[$value['branch_id']];
            $oper         = $user_list[$value['op_id']];
            $confirm_oper = $user_list[$value['confirm_op_id']];

            $inv = array(
                'inventory_bn'   => $value['inventory_bn'],
                'inventory_type' => $value['inventory_type'],
                'createtime'     => date('Y-m-d H:i:s', $value['createtime']),
                'confirm_time'   => date('Y-m-d H:i:s', $value['confirm_time']),
                'status'         => $value['status'],
                'store_bn'       => $branch['branch_bn'],
                'store_name'     => $branch['name'],
                'oper'           => $oper['name'],
                'confirm_oper'   => $confirm_oper['name'],
                'accounts_num'   => 0,
                'actual_num'     => 0.,
            );

            foreach ((array) $item_list[$value['inventory_id']] as $k => $v) {
                $bm_ext = $bm_ext_list[$v['bm_id']];
                $bm     = $bm_list[$v['bm_id']];
                $cat    = $cat_list[$bm['cat_id']];
                $brand  = $brand_list[$bm_ext['brand_id']];

                $inv['items'][] = array(
                    'bn'                 => $bm['material_bn'],
                    'name'               => $bm['material_name'],
                    'specifications'     => $bm_ext['specifications'],
                    'brand_name'         => $brand['brand_name'],
                    'cat_name'           => $cat['cat_name'],
                    'accounts_num'       => $v['accounts_num'],
                    'accounts_share_num' => $v['accounts_share_num'],
                    'actual_num'         => $v['actual_num'],
                    'short_over'         => $v['short_over'],
                    'share_short_over'   => $v['share_short_over'],
                    'actual_share_num'   => $v['actual_share_num'],
                );

                $inv['accounts_num'] += $v['accounts_num'];
                $inv['actual_num'] += $v['actual_num'];
            }

            $data['invs'][] = $inv;
        }

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 盘点单详情查询
     *
     * @return void
     * @author
     **/
    public function get($filter)
    {
        $invMdl     = app::get('o2o')->model('inventory');
        $invItemMdl = app::get('o2o')->model('inventory_items');

        $inv = $invMdl->db_dump($filter);

        if (!$inv) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $data        = array();
        $data['inv'] = array(
            'inventory_bn' => $inv['inventory_bn'],
            'createtime'   => date('Y-m-d H:i:s', $inv['createtime']),
            'confirm_time' => date('Y-m-d H:i:s', $inv['confirm_time']),
            'status'       => $inv['status'],
            // 'store_bn'     => $branch['branch_bn'],
            // 'oper'         => $oper['name'],
            // 'confirm_oper' => $confirm_oper['name'],
        );

        $branch = app::get('ome')->model('branch')->db_dump(array('branch_id' => $inv['branch_id'], 'check_permission' => 'false'), 'branch_bn,name');

        $data['inv']['store_bn']   = $branch['branch_bn'];
        $data['inv']['store_name'] = $branch['name'];

        $user_list = app::get('desktop')->model('users')->getList('*', array('user_id' => array($inv['op_id'], $inv['confirm_op_id'])));
        $user_list = array_column($user_list, null, 'user_id');

        $data['inv']['oper']         = $user_list[$inv['op_id']]['name'];
        $data['inv']['confirm_oper'] = $user_list[$inv['confirm_op_id']]['name'];

        $items = $invItemMdl->getList('*', array('inventory_id' => $inv['inventory_id']));

        $bm_id_list = array_column($items, 'bm_id');

        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', array('bm_id' => $bm_id_list));
        $bm_list = array_column($bm_list, null, 'bm_id');

        // 基础
        $brandMdl = app::get('ome')->model('brand');
        $bmExtMdl = app::get('material')->model('basic_material_ext');
        $bmMdl    = app::get('material')->model('basic_material');
        $catMdl   = app::get('material')->model('basic_material_cat');

        $cat_id_list = array_column($bm_list, 'cat_id');
        $cat_list    = $catMdl->getList('cat_id,cat_name', array('cat_id' => $cat_id_list));
        $cat_list    = array_column($cat_list, null, 'cat_id');

        $bm_ext_list = $bmExtMdl->getList('*', array('bm_id' => $bm_id_list));
        $bm_ext_list = array_column($bm_ext_list, null, 'bm_id');

        $brand_id_list = array_column($bm_ext_list, 'brand_id');
        $brand_list    = $brandMdl->getList('brand_id,brand_name', array('brand_id' => $brand_id_list));
        $brand_list    = array_column($brand_list, null, 'brand_id');

        foreach ($items as $v) {
            $bm_ext = $bm_ext_list[$v['bm_id']];
            $bm     = $bm_list[$v['bm_id']];
            $cat    = $cat_list[$bm['cat_id']];
            $brand  = $brand_list[$bm_ext['brand_id']];

            $data['inv']['items'][] = array(
                'bn'                 => $bm['material_bn'],
                'name'               => $bm['material_name'],
                'specifications'     => $bm_ext['specifications'],
                'brand_name'         => $brand['brand_name'],
                'cat_name'           => $cat['cat_name'],
                'accounts_num'       => $v['accounts_num'],
                'accounts_share_num' => $v['accounts_share_num'],
                'actual_num'         => $v['actual_num'],
                'short_over'         => $v['short_over'],
                'share_short_over'   => $v['share_short_over'],
                'actual_share_num'   => $v['actual_share_num'],
            );
        }

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 盘点单总数
     *
     * @return void
     * @author
     **/
    public function count($filter)
    {
        $invMdl = app::get('o2o')->model('inventory');

        $count = $invMdl->count($filter);

        return array('rsp' => 'succ', 'data' => array('count' => $count));
    }

}
