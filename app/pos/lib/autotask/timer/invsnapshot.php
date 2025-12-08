<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 门店每日库存快照
 */
class pos_autotask_timer_invsnapshot
{

    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '1024M');

        $invMdl = app::get('pos')->model('inventory_snapshot');

        if ($params['invs_id']) {
            $inventory = $invMdl->dump($params['invs_id']);

            if ($inventory) {
                $this->_syncQty($inventory);
            }

            return true;
        }

        $stock_date = date('Y-m-d', strtotime('yesterday'));

        $server = app::get('o2o')->model('server')->dump(['node_type' => 'pekon']);

        if (!$server) {
            return true;
        }

        // 只查trade btq库存
        $storeList = app::get('o2o')->model('store')->getList('store_id,store_bn', [
            'server_id' => $server['server_id'],
            'store_sort' => ['BTQ','Trade'],
        ]);

        if (!$storeList) {
            return true;
        }
        $storeList = array_column($storeList, null, 'store_id');

        $branchList = app::get('ome')->model('branch')->getList('branch_id,branch_bn,store_id,storage_code', [
            'store_id'         => array_column($storeList, 'store_id'),
            'check_permission' => 'false',
        ]);

        if (!$branchList) {
            return true;
        }


        foreach ($branchList as $key => $value) {
            $store = $storeList[$value['store_id']];

            $inventory = $invMdl->dump([
                'stock_date' => $stock_date,
                'store_id'   => $store['store_id'],
                'branch_id'  => $value['branch_id'],
            ]);

            if (!$inventory) {
                $inventory = [
                    'stock_date' => $stock_date,
                    'status'     => '1',
                    'store_bn'   => $store['store_bn'],
                    'store_id'   => $store['store_id'],
                    'branch_bn'  => $value['branch_bn'],
                    'branch_id'  => $value['branch_id'],
                    'storage_code' => $value['storage_code'],
                ];

                $invMdl->insert($inventory);
            }

            // 加入队列
            kernel::single('taskmgr_interface_connecter')->push([
                'data' => [
                    'task_type'    => 'pos_sync_inv',
                    'invs_id'      => $inventory['id'],
                ],
                'url'  => kernel::openapi_url('openapi.autotask', 'service'),
            ]);

            // $this->_syncQty($inventory);
        }

        return true;
    }

    /**
     * 同步库存
     *
     * @return void
     * @author
     **/
    private function _syncQty($inventory)
    {
        if ($inventory['status'] == '2') {
            return;
        }

        $invMdl     = app::get('pos')->model('inventory_snapshot');
        $invItemMdl = app::get('pos')->model('inventory_snapshot_items');

        $get_count = (int) $inventory['get_count'];
        $pageNo    = floor($get_count / 100) + 1;

        $pos_stock = (int) $inventory['pos_stock'];
        do {
            $sdf['page_no']   = $pageNo;
            $sdf['page_size'] = '100';
            $sdf['branch_bn'] = $inventory['branch_bn'];

            $result = kernel::single('erpapi_router_request')->set('store', $inventory['store_id'])->stock_get($sdf);

            if ($result['rsp'] == 'fail') {
                $invMdl->update(['status' => '3', 'errmsg' => $result['msg']], [
                    'id' => $inventory['id'],
                ]);

                kernel::single('monitor_event_notify')->addNotify('pos_stock_sync', [
                    'store_bn'  => $inventory['store_bn'],
                    'branch_bn' => $inventory['branch_bn'],
                    'page_no'   => $pageNo,
                    'errmsg'    => $result['msg'],
                ]);

                return;
            }

            if (!$result['data']['inventoryItems'] || $result['data']['totalCount'] == '0') {
                $invMdl->update(['status' => '2', 'errmsg' => ''], [
                    'id' => $inventory['id'],
                ]);

                break;
            }

            if ($pageNo == 1) {
                $invMdl->update(['total_count' => $result['data']['totalCount']], [
                    'id' => $inventory['id'],
                ]);
            }

            $invItems = [];
            foreach ($result['data']['inventoryItems'] as $key => $value) {
                $invItems[] = [
                    'invs_id'        => $inventory['id'],
                    'stock_date'     => $inventory['stock_date'],
                    'store_bn'       => $inventory['store_bn'],
                    'store_id'       => $inventory['store_id'],
                    'branch_bn'      => $inventory['branch_bn'],
                    'branch_id'      => $inventory['branch_id'],
                    'item_code'      => $value['productSkuCode'],
                    'item_id'        => $value['productSkuId'],
                    'quantity'       => (int) $value['usableQuantity'] + (int) $value['lockedQuantity'],
                    'lock_quantity'  => (int) $value['lockedQuantity'],
                    'expire_date'    => $value['expirationDate'],
                    'inventory_type' => $value['skuAddType'],
                ];
            }

            $affectRs = kernel::database()->exec(ome_func::get_insert_sql($invItemMdl, $invItems));

            if ($affectRs) {
                $get_count += count($invItems);
                $pos_stock += array_sum(array_column($invItems, 'quantity'));
                $invMdl->update(['get_count' => $get_count, 'pos_stock' => $pos_stock, 'errmsg'=>''], [
                    'id' => $inventory['id'],
                ]);
            }

            $pageNo++;
        } while (true);

        // 生成日盘
        kernel::single('taskmgr_interface_connecter')->push([
            'data' => [
                'task_type'    => 'dailyinventory',
                'channel_type' => 'store',
                'invs_id'      => $inventory['id'],
            ],
            'url'  => kernel::openapi_url('openapi.autotask', 'service'),
        ]);

        return;
    }

}
