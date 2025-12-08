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
class erpapi_front_response_process_o2o_appropriation
{
    /**
     * 调拨单创建
     *
     * @return void
     * @author
     **/

    public function add($data)
    {
        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');

        kernel::database()->beginTransaction();

        $oper = kernel::single('ome_func')->getDesktopUser();

        $res = kernel::single('console_receipt_allocate')->to_savestore($data['items'], 2, $data['memo'], $oper['op_name'], $msg);

        if (!$res) {

            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => $msg);
        }
        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }

    /**
     * 调拨单详情查询
     *
     * @return void
     * @author
     **/
    public function get($filter)
    {
        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');

        $appro = $approMdl->db_dump($filter);

        if (!$appro) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $iso = app::get('taoguaniostockorder')->model('iso')->db_dump(array('original_id' => $appro_id_list, 'type_id' => '40'), 'iso_id,appropriation_no,check_status,iso_status,original_id,iso_bn');

        $status = '1';
        if ($iso['check_status'] == '1' && $iso['iso_status']) {
            $status = '0';
        } elseif ($iso['iso_status'] == '4') {
            $status = '2'; // 取消
        }

        $data          = array();
        $data['appro'] = array(
            'appropriation_no' => $appro['appropriation_no'],
            'create_time'      => date('Y-m-d H:i:s', $appro['create_time']),
            'operator_name'    => $appro['operator_name'],
            'memo'             => $appro['memo'],
            'status'           => $status,
            'iso_bn'           => $iso['iso_bn'],
        );

        $items = $approItemMdl->getList('*', array('appropriation_id' => $appro['appropriation_id']));

        $from_branch_id = array_column($items, 'from_branch_id');
        $to_branch_id   = array_column($items, 'to_branch_id');

        $branchMdl   = app::get('ome')->model('branch');
        $from_branch = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $from_branch_id, 'check_permission' => 'false'));

        $from_branch = array_column($from_branch, null, 'branch_id');

        $to_branch = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $to_branch_id, 'check_permission' => 'false'));
        $to_branch = array_column($to_branch, null, 'branch_id');

        // 基础
        $bm_id_list = array_column($bm_id_list, 'product_id');
        $brandMdl   = app::get('ome')->model('brand');
        $bmExtMdl   = app::get('material')->model('basic_material_ext');
        $bmMdl      = app::get('material')->model('basic_material');
        $catMdl     = app::get('material')->model('basic_material_cat');

        $bm_list = $bmMdl->getList('cat_id,bm_id', array('bm_id' => $bm_id_list));

        $cat_id_list = array_column($bm_list, 'cat_id');
        $cat_list    = $catMdl->getList('cat_id,cat_name', array('cat_id' => $cat_id_list));
        $cat_list    = array_column($cat_list, null, 'cat_id');

        $bm_ext_list = $bmExtMdl->getList('*', array('bm_id' => $bm_id_list));
        $bm_ext_list = array_column($bm_ext_list, null, 'bm_id');

        $brand_id_list = array_column($bm_ext_list, 'brand_id');
        $brand_list    = $brandMdl->getList('brand_id,brand_name', array('brand_id' => $brand_id_list));
        $brand_list    = array_column($brand_list, null, 'brand_id');

        foreach ($items as $item) {
            $bm_ext = $bm_ext_list[$item['bm_id']];
            $bm     = $bm_list[$item['bm_id']];
            $cat    = $cat_list[$bm['cat_id']];

            $data['appro']['items'][] = array(
                'bn'               => $item['bn'],
                'name'             => $item['product_name'],
                'nums'             => $item['num'],
                'from_branch_bn'   => $from_branch[$item['from_branch_id']]['branch_bn'],
                'from_branch_name' => $from_branch[$item['from_branch_id']]['name'],
                'to_branch_bn'     => $to_branch[$item['to_branch_id']]['branch_bn'],
                'to_branch_name'   => $to_branch[$item['to_branch_id']]['name'],
                'from_branch_num'  => $item['from_branch_num'],
                'to_branch_num'    => $item['to_branch_num'],
                'specifications'   => $bm_ext['specifications'],
                'brand_name'       => $brand_list[$bm_ext['brand_id']]['brand_name'],
                'cat_name'         => $cat['cat_name'],
            );
        }

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 调拨单总计查询
     *
     * @return void
     * @author
     **/
    public function count($filter)
    {
        $approMdl = app::get('taoguanallocate')->model('appropriation');

        $count = $approMdl->count($filter);

        return array('rsp' => 'succ', 'data' => array('count' => $count));
    }

    public function listing($filter)
    {
        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');

        $appro_list = $approMdl->getList('*', $filter);

        if (!$appro_list) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $appro_id_list = array_column($appro_list, 'appropriation_id');
        $items_list    = $approItemMdl->getList('*', array('appropriation_id' => $appro_id_list), $filter['offset'], $filter['limit']);

        $from_branch_id = array_column($items_list, 'from_branch_id');
        $to_branch_id   = array_column($items_list, 'to_branch_id');

        $branchMdl   = app::get('ome')->model('branch');
        $from_branch = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $from_branch_id, 'check_permission' => 'false'));

        $from_branch = array_column($from_branch, null, 'branch_id');

        $to_branch = $branchMdl->getList('branch_id,branch_bn,name', array('branch_id' => $to_branch_id, 'check_permission' => 'false'));
        $to_branch = array_column($to_branch, null, 'branch_id');

        $iso_list = app::get('taoguaniostockorder')->model('iso')->getList('iso_id,appropriation_no,check_status,iso_status,original_id,iso_bn', array('original_id' => $appro_id_list, 'type_id' => '40'));
        $iso_list = array_column($iso_list, null, 'original_id');

        $data = array();
        foreach ($appro_list as $key => $appro) {
            $iso = (array) $iso_list[$appro['appropriation_id']];

            $status = '1';
            if ($iso['check_status'] == '1' && $iso['iso_status']) {
                $status = '0';
            } elseif ($iso['iso_status'] == '4') {
                $status = '2'; // 取消
            }

            $data['appro_list'][] = array(
                'appropriation_no' => $appro['appropriation_no'],
                'status'           => $status,
                'create_time'      => date('Y-m-d H:i:s', $appro['create_time']),
                'operator_name'    => $appro['operator_name'],
                'memo'             => $appro['memo'],
                'iso_bn'           => $iso['iso_bn'],
                'items'            => &$items[$appro['appropriation_id']],
            );
        }

        foreach ($items_list as $value) {
            $items[$value['appropriation_id']][] = array(
                'bn'               => $item['bn'],
                'name'             => $item['product_name'],
                'nums'             => $item['num'],
                'from_branch_bn'   => $from_branch[$item['from_branch_id']]['branch_bn'],
                'from_branch_name' => $from_branch[$item['from_branch_id']]['name'],
                'to_branch_bn'     => $to_branch[$item['to_branch_id']]['branch_bn'],
                'to_branch_name'   => $to_branch[$item['to_branch_id']]['name'],
            );
        }

        return array('rsp' => 'succ', 'data' => $data);
    }
}
