<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 出入库单处理类
 *
 * @author <chenping@shopex.cn>
 * @time 2020-11-18T19:56:04+08:00
 */
class erpapi_front_response_process_o2o_iso
{
    /**
     * 出入库单确认
     *
     * @return void
     * @author
     **/

    public function check($filter)
    {
        kernel::database()->beginTransaction();

        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        $iso    = $isoMdl->db_dump($filter['iso_id']);

        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);

        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');

        // 需要判断可用库存是否足够
        $iso_items = $isoItemMdl->getlist('bn,nums,product_id', array('iso_id' => $iso['iso_id']));

        $affect_rows = $isoMdl->update(array('check_status' => '2'), array('iso_id' => $iso['iso_id']));
        if (is_bool($affect_rows)) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => '确认失败：单据异常');
        }

        if ($io == '0') {
            $storeManageLib = kernel::single('ome_store_manage');
            $storeManageLib->loadBranch(array('branch_id' => $iso['branch_id']));

            $params = array(
                'node_type' => 'checkStockout',
                'params'    => array(
                    'iso_id'    => $iso['iso_id'],
                    'branch_id' => $iso['branch_id'],
                    'items'     => $iso_items,
                ),
            );

            $processResult = $storeManageLib->processBranchStore($params, $err_msg);
            if (!$processResult) {
                kernel::database()->rollBack();

                return array('rsp' => 'fail', 'msg' => '确认失败：' . $err_msg);
            }

        } else {

            if ($iso['type_id'] == '4') {
                $libBranchProduct = kernel::single('ome_branch_product');
                foreach ($iso_items as $item) {
                    $libBranchProduct->change_arrive_store($iso['branch_id'], $item['product_id'], $item['nums'], '+');
                }
            }
        }

        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }

    /**
     * 出入库单取消
     *
     * @return void
     * @author
     **/
    public function cancel($filter)
    {
        kernel::database()->beginTransaction();
        $isoMdl = app::get('taoguaniostockorder')->model('iso');

        $iso = $isoMdl->db_dump($filter['iso_id']);

        $affect_rows = $isoMdl->update(array('iso_status' => '4'), array('iso_id' => $iso['iso_id'], 'iso_status' => '1'));

        if (is_bool($affect_rows)) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => '取消失败：单据异常');
        }

        // 如果是已审核，取消冻结库存
        $stockObj = kernel::single('console_receipt_stock');

        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($iso['check_status'] == '2' && $io == '0') {

            if ($stockObj->checkExist($iso['iso_bn'])) {
                $stockObj->clear_stockout_store_freeze($iso, 'FINISH');
            }

        }

        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }

    /**
     * 出入库单详情查询
     *
     * @return void
     * @author
     **/
    public function get($filter)
    {
        $isoMdl     = app::get('taoguaniostockorder')->model('iso');
        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');

        $iso = $isoMdl->db_dump($filter);

        if (!$iso) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $branch_list = app::get('ome')->model('branch')->getList('branch_bn,name,branch_id', array(
            'branch_id'        => array($iso['branch_id'], $iso['extrabranch_id']),
            'check_permission' => 'false',
        ));
        $branch_list = array_column($branch_list, null, 'branch_id');

        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($io == 1) {
            $from_branch = $branch_list[$iso['extrabranch_id']];
            $to_branch   = $branch_list[$iso['branch_id']];
        } else {
            $from_branch = $branch_list[$iso['branch_id']];
            $to_branch   = $branch_list[$iso['extrabranch_id']];
        }

        $items = $isoItemMdl->getList('*', array('iso_id' => $iso['iso_id']));

        $data = array(
            'iso' => array(
                'name'             => $iso['name'],
                'iso_bn'           => $iso['iso_bn'],
                'supplier_name'    => $iso['supplier_name'],
                'from_store_name'  => $from_branch['name'],
                'to_store_name'    => $to_branch['name'],
                'last_modified'    => $iso['complete_time'] ? date('Y-m-d H:i:s', $value['complete_time']) : date('Y-m-d H:i:s', $iso['create_time']),
                'product_cost'     => $iso['product_cost'],
                'iso_price'        => $iso['iso_price'],
                'cost_tax'         => $iso['cost_tax'],
                'oper'             => $iso['oper'],
                'create_time'      => date('Y-m-d H:i:s', $iso['create_time']),
                'complete_time'    => $iso['complete_time'] ? date('Y-m-d H:i:s', $iso['complete_time']) : '',
                'operator'         => $iso['operator'],
                'memo'             => $iso['memo'],
                'iso_status'       => $iso['iso_status'],
                'check_status'     => $iso['check_status'],
                'appropriation_no' => $iso['appropriation_no'],
                'total_nums'       => 0,
                'process_status'   => $iso['process_status'],
            ),
        );

        $bm_id = array_column($items, 'product_id');

        $bmMdl    = app::get('material')->model('basic_material');
        $bmExMdl  = app::get('material')->model('basic_material_ext');
        $catMdl   = app::get('material')->model('basic_material_cat');
        $brandMdl = app::get('ome')->model('brand');

        $bm_list = $bmMdl->getList('bm_id,cat_id', array('bm_id' => $bm_id));
        $bm_list = array_column($bm_list, null, 'bm_id');

        $cat_id   = array_column($bm_list, 'cat_id');
        $cat_list = $catMdl->getList('cat_id,cat_name', array('cat_id' => $cat_id));
        $cat_list = array_column($cat_list, null, 'cat_id');

        $bm_ext_list = $bmExMdl->getList('bm_id,brand_id,specifications', array('bm_id' => $bm_id));
        $bm_ext_list = array_column($bm_ext_list, null, 'bm_id');

        $brand_id   = array_column($bm_ext_list, 'brand_id');
        $brand_list = $brandMdl->getList('brand_id,brand_name', array('brand_id' => $brand_id));
        $brand_list = array_column($brand_list, null, 'brand_id');

        foreach ($items as $item) {
            $bm     = $bm_list[$item['product_id']];
            $cat    = $cat_list[$bm['cat_id']];
            $bm_ext = $bm_ext_list[$item['product_id']];
            $brand  = $brand_list[$bm_ext['brand_id']];

            $data['iso']['items'][] = array(
                'name'           => $item['product_name'],
                'bn'             => $item['bn'],
                'unit'           => $item['unit'],
                'price'          => $item['price'],
                'nums'           => $item['nums'],
                'normal_num'     => $item['normal_num'],
                'defective_num'  => $item['defective_num'],
                'specifications' => $bm_ext['specifications'],
                'brand_name'     => $brand['brand_name'],
                'cat_name'       => $cat['cat_name'],
            );

            $data['iso']['total_nums'] += $item['nums'];
        }

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 出入库单列表查询
     *
     * @return void
     * @author
     **/
    public function listing($filter)
    {
        $isoMdl     = app::get('taoguaniostockorder')->model('iso');
        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');
        $branchMdl  = app::get('ome')->model('branch');

        $offset = $filter['offset'];
        $limit  = $filter['limit'];

        unset($filter['offset'], $filter['limit']);

        $iso_list = $isoMdl->getList('*', $filter, $offset, $limit, 'create_time DESC');

        if (!$iso_list) {
            return array('rsp' => 'succ', 'data' => array());
        }

        $branch_id      = array_column($iso_list, 'branch_id');
        $extrabranch_id = array_column($iso_list, 'extrabranch_id');

        $branch_list = $branchMdl->getList('branch_id,name', array('branch_id' => array_merge((array) $branch_id, (array) $extrabranch_id), 'check_permission' => 'false'));
        $branch_list = array_column($branch_list, null, 'branch_id');

        $iso_id = array();

        $data = array('isos' => array());
        foreach ($iso_list as $key => $value) {
            $io = kernel::single('ome_iostock')->getIoByType($value['type_id']);
            if ($io == 1) {
                $from_branch = $branch_list[$value['extrabranch_id']];
                $to_branch   = $branch_list[$value['branch_id']];
            } else {
                $from_branch = $branch_list[$value['branch_id']];
                $to_branch   = $branch_list[$value['extrabranch_id']];
            }

            // $value['from_store_name'] = $from_branch['name'];
            // $value['to_store_name']   = $to_branch['name'];
            // $value['last_modified']   = $value['complete_time'] ? date('Y-m-d H:i:s', $value['complete_time']) : date('Y-m-d H:i:s', $value['create_time']);

            $data['isos'][$key] = array(
                'iso_bn'          => $value['iso_bn'],
                'name'            => $value['name'],
                'type_id'         => $value['type_id'],
                'check_status'    => $value['check_status'],
                'iso_status'      => $value['iso_status'],
                'process_status'  => $value['process_status'],
                'oper'            => $value['oper'],
                'operator'        => $value['operator'],
                'from_store_name' => $from_branch['name'],
                'to_store_name'   => $to_branch['name'],
                'last_modified'   => $value['complete_time'] ? date('Y-m-d H:i:s', $value['complete_time']) : date('Y-m-d H:i:s', $value['create_time']),
                'nums'            => &$item_count[$value['iso_id']]['nums'],
                'normal_num'      => &$item_count[$value['iso_id']]['normal_num'],
                'defective_num'   => &$item_count[$value['iso_id']]['defective_num'],
                'items'           => &$item_list[$value['iso_id']],
            );

            $iso_id[] = $value['iso_id'];
        }

        foreach ($isoItemMdl->getList('*', array('iso_id' => $iso_id)) as $value) {
            $item_list[$value['iso_id']] = $value;

            $item_count[$value['iso_id']]['nums'] += $value['nums'];
            $item_count[$value['iso_id']]['normal_num'] += $value['normal_num'];
            $item_count[$value['iso_id']]['defective_num'] += $value['defective_num'];
        }

        return array('rsp' => 'succ', 'data' => $data);
    }

    /**
     * 出入库单列表查询
     *
     * @return void
     * @author
     **/
    public function count($filter)
    {
        $isoMdl = app::get('taoguaniostockorder')->model('iso');

        $count = $isoMdl->count($filter);

        return array('rsp' => 'succ', 'data' => array('count' => $count));
    }

    /**
     * 出入库单确认
     *
     * @return void
     * @author
     **/
    public function ready($iso)
    {
        $isoMdl     = app::get('taoguaniostockorder')->model('iso');
        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');

        if ($iso['logi_no']) {
            return array('rsp' => 'succ', 'data' => array());
        }

        kernel::database()->beginTransaction();

        // 调拨出库，叫物流
        if ($iso['corp']['tmpl_type'] == 'electron' && $iso['type_id'] == '40') {
            // 明细
            $item_list = $isoItemMdl->getList('*', array('iso_id' => $iso['iso_id']));

            $branchMdl = app::get('ome')->model('branch');
            // 发货地址
            $from_branch                                    = $branchMdl->db_dump(array('branch_id' => $iso['branch_id'], 'check_permission' => 'false'));
            list(, $from_area)                              = explode(':', $from_branch['area']);
            list($from_provice, $from_city, $from_district) = explode('/', $from_area);

            // 收货地址
            $to_branch                                = $branchMdl->db_dump(array('branch_id' => $iso['extrabranch_id'], 'check_permission' => 'false'));
            list(, $to_area)                          = explode(':', $to_branch['area']);
            list($to_provice, $to_city, $to_district) = explode('/', $to_area);

            $sdf = array(
                'primary_bn' => $iso['iso_bn'],
                'delivery'   => array(
                    'delivery_id'   => $iso['iso_id'],
                    'delivery_bn'   => $iso['iso_bn'],
                    'ship_province' => $to_provice,
                    'ship_city'     => $to_city,
                    'ship_district' => $to_district,
                    'ship_addr'     => $to_branch['address'],
                    'ship_name'     => $to_branch['uname'],
                    'ship_mobile'   => $to_branch['mobile'],
                    'ship_tel'      => $to_branch['phone'],
                    'create_time'   => time(),
                ),
                'shop'       => array(
                    'shop_name'      => $from_branch['name'],
                    'province'       => $from_provice,
                    'city'           => $from_city,
                    'area'           => $from_district,
                    'address_detail' => $from_branch['address'],
                    'default_sender' => $from_branch['uname'],
                    'mobile'         => $from_branch['mobile'],
                    'tel'            => $from_branch['phone'],
                    'zip'            => $from_branch['zip'],
                ),
            );

            foreach ($item_list as $key => $item) {
                $sdf['delivery_item'][$key] = array(
                    'product_name' => $item['product_name'],
                    'number'       => $item['nums'],
                );
            }
            $rsp = kernel::single('erpapi_router_request')->set('logistics', $iso['corp']['channel_id'])->electron_directRequest($sdf);

            if (is_string($rsp)) {
                kernel::database()->rollBack();

                return array('rsp' => 'fail', 'msg' => $rsp, 'data' => array());
            }

            if (!$rsp[0]['succ']) {
                kernel::database()->rollBack();

                return array('rsp' => 'fail', 'msg' => '呼叫物流失败', 'data' => array());
            }

            if ($rsp[0]['succ'] && $iso['iso_id'] == $rsp[0]['delivery_id']) {
                $isoMdl->update(array('logi_no' => $rsp[0]['logi_no']), array('iso_id' => $iso['iso_id']));
            }
        }

        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }

    /**
     * 出入库单确认
     *
     * @return void
     * @author
     **/
    public function confirm($iso)
    {
        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($io == '0') {
            // 叫物流
            $result = $this->ready($iso);
            if ($result['rsp'] == 'fail') {
                return $result;
            }

            $items = array();
            foreach ($iso['items'] as $value) {
                $items[] = array(
                    'bn'  => $value['bn'],
                    'num' => $value['normal_num'],
                );
            }

            $iso['items'] = $items;
        }

        $data = array(
            'io_bn'        => $iso['iso_bn'],
            'io_status'    => $iso['io_status'],
            'operate_time' => date('Y-m-d H:i:s'),
            'items'        => $iso['items'],
        );

        if ($iso['type_id'] == '4' || $iso['type_id'] == '40') {
            $data['io_type'] = 'ALLCOATE';
        } else {
            $data['io_type'] = 'OTHER';
        }

        if ($io == '1') {
            return kernel::single('console_event_receive_iostock')->stockin_result($data);
        } else {
            return kernel::single('console_event_receive_iostock')->stockout_result($data);
        }
    }

}
