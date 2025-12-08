<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 冻结明细查询
 *
 * @access public
 * @author maxiaochen<maxiaochen@shopex.cn>
 * @version 1.0 detail.php 2025-03-19
 */
class ome_ctl_admin_freeze_detail extends desktop_controller
{

    var $workground = "adminpanel";

    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        $this->page('admin/freeze/detail.html');
    }

    function get_data()
    {
        // 获取所有仓
        $branchList = app::get('ome')->model('branch')->getList('branch_id, branch_bn, name');
        $branchList = array_column($branchList, null, 'branch_id');
        $branchIdArr = array_column($branchList, 'branch_id');

        // 获取商品信息
        $bm_bn = trim($_POST['bm_bn']);
        $materialMdl = app::get('material')->model('basic_material');
        $bmList = $materialMdl->getList('bm_id, material_bn', ['material_bn' => $bm_bn]);
        $script_bm_id_list = array_column($bmList, 'bm_id');
        $bm_time_where = 'bm_id in ("'.implode('","', $script_bm_id_list).'")';
        $bmSql  = "SELECT bm_id, material_bn as bm_bn, material_name FROM sdb_material_basic_material WHERE " . $bm_time_where;
        $db = kernel::database();
        $bmIdArr = $db->select($bmSql);

        $bmbnArr = array_column($bmIdArr, null, 'bm_id');
        $bmIdArr = array_column($bmIdArr, 'bm_id');

        // 获取material_basic_material_stock《商品库存表》的库存和冻结
        $bmStoreSql  = "SELECT bm_id, store, store_freeze, last_modified FROM sdb_material_basic_material_stock WHERE bm_id in ('" . implode("','", $bmIdArr) . "')";
        $bmStoreList = $db->select($bmStoreSql);
        $bmStoreList = array_column($bmStoreList, null, 'bm_id');

        $bmRedisList = [];
        // 获取redis《物料》的库存和预占数据
        foreach ($bmIdArr as $bmId) {
            $param = [
                'bm_id' => $bmId,
            ];
            $redisInfo = material_basic_material_stock::storeFromRedis($param);
            if ($redisInfo[0]) {
                $bmRedisList[$bmId] = $redisInfo[2];
            }
        }
        
        // 获取sdb_ome_branch_product《仓库商品表》的数据
        $branchProductListAll = [];
        $branchProductSql = "SELECT product_id as bm_id, store, store_freeze, branch_id
            FROM sdb_ome_branch_product
            WHERE branch_id IN ('" . implode("','", $branchIdArr) . "') AND product_id in ('" . implode("','", $bmIdArr) . "')";
        $_branchProductList = $db->select($branchProductSql);
        foreach ($_branchProductList as $bk => $bv) {
            $branchProductListAll[$bv['bm_id']][$bv['branch_id']] = $bv;
        }
        unset($_branchProductList);

        // 获取basic_material_stock_freeze《预占流水表》的总预占数据（仓预占+订单预占）
        $bmSerialList = [];
        $serialSql = "SELECT bm_id, sum(num) as store_freeze
            FROM sdb_material_basic_material_stock_freeze
            WHERE bm_id in ('" . implode("','", $bmIdArr) . "')
            GROUP BY bm_id";
        $bmSerialList = $db->select($serialSql);
        $bmSerialList && $bmSerialList = array_column($bmSerialList, null, 'bm_id');

        // 获取basic_material_stock_freeze《预占流水表》的仓预占数据
        $stockFreezeListAll = [];
        $stockFreezeSql = "SELECT bm_id, sum(num) as num, branch_id
            FROM sdb_material_basic_material_stock_freeze
            WHERE (obj_type = " . material_basic_material_stock_freeze::__BRANCH . " OR (obj_type=" . material_basic_material_stock_freeze::__ORDER . " and bill_type=" . material_basic_material_stock_freeze::__ORDER_YOU . ")) AND branch_id IN ('" . implode("','", $branchIdArr) . "') AND bm_id in ('" . implode("','", $bmIdArr) . "')
            GROUP BY branch_id, bm_id";
        $_stockFreezeList = $db->select($stockFreezeSql);
        foreach ($_stockFreezeList as $sk => $sv) {
            $stockFreezeListAll[$sv['bm_id']][$sv['branch_id']] = $sv;
        }
        unset($_stockFreezeList);

        $redisList = [];
        foreach ($branchList as $branchInfo) {

            $branchId = $branchInfo['branch_id'];

            // 获取redis《仓》的库存和预占数据
            foreach ($bmIdArr as $bmId) {
                $param = [
                    'branch_id'  => $branchId,
                    'product_id' => $bmId,
                ];
                $redisInfo = ome_branch_product::storeFromRedis($param);
                if ($redisInfo[0]) {
                    $redisList[$bmId][$branchId] = $redisInfo[2];
                }
            }
        }
        $return = [];
        foreach ($bmbnArr as $bm_id => $bm_info) {
            $bmStoreList[$bm_id]['last_modified'] = date('Y-m-d H:i:s', $bmStoreList[$bm_id]['last_modified']);
            $return[$bm_info['bm_bn']] = [
                'product_info' => $bm_info ?? [],
                'branch_info' => $branchList ?? [],
                'stock_freeze' => $stockFreezeListAll[$bm_id] ?? [], // 冻结流水的仓冻结
                'branch_product' => $branchProductListAll[$bm_id] ?? [], // 仓库存表的库存和冻结
                'redis_product' => $bmRedisList[$bm_id] ?? [], // redis商品库存和冻结
                'redis_branch' => $redisList[$bm_id] ?? [], // redis仓库存和冻结
                'stock_freeze_all' => $bmSerialList[$bm_id] ?? [], // 冻结流水的总冻结
                'material_stock' => $bmStoreList[$bm_id] ?? [], // 商品的库存和冻结
            ];
        }
        echo json_encode($return[$bm_bn] ?? []);exit;
    }

}
