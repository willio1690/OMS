<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
class tgstockcost_autotask_timer_stockcost
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

        if (app::get('tgstockcost')->is_installed()) {
            $this->set($params);
        }

        return true;
    }

    private function set($params)
    {
        // 判断执行时间
        base_kvstore::instance('omeanalysts')->fetch('stockcost-nextexectime', $lastExecTime);

        // 脚本已经执行过
        if ($lastExecTime && date('Y-m-d') == date('Y-m-d', $lastExecTime)) {
            return false;
        }

        base_kvstore::instance('omeanalysts')->store('stockcost-nextexectime', time());

        $stock_date = $params['stock_date'] ? $params['stock_date'] : date('Y-m-d', strtotime('yesterday'));

        // 大仓库存
        $this->insertBranchStock($stock_date);

        // 门店库存
        $this->insertStoreStock($stock_date);

        // 销售
        $sale_type_id = ome_iostock::LIBRARY_SOLD;
        $this->insertIOStock($stock_date, (array) $sale_type_id, ['nums' => 'sale_stock', 'cost' => 'sale_cost']);

        // 销退
        $reship_type_id = ome_iostock::RETURN_STORAGE;
        $this->insertIOStock($stock_date, (array) $reship_type_id, ['nums' => 'reship_stock', 'cost' => 'reship_cost']);

        // 出库
        $out_type_id = kernel::single('ome_iostock')->getTypeId('0');
        if (false !== $index = array_search($sale_type_id, $out_type_id)) {
            unset($out_type_id[$index]);
        }
        $this->insertIOStock($stock_date, $out_type_id, ['nums' => 'out_stock', 'cost' => 'out_cost']);

        // 入库
        $in_type_id = kernel::single('ome_iostock')->getTypeId('1');
        if (false !== array_search($reship_type_id, $in_type_id)) {
            unset($in_type_id[$index]);
        }
        $this->insertIOStock($stock_date, $in_type_id, ['nums' => 'in_stock', 'cost' => 'in_cost']);

        return true;
    }

    /**
     * 大仓库存
     *
     * @return void
     * @author
     **/
    private function insertBranchStock($stock_date)
    {
        $sql = <<<SQL
            INSERT INTO sdb_ome_dailystock(stock_date,branch_id,product_id,stock_num,arrive_stock,freeze_stock,unit_cost,inventory_cost,product_bn,branch_bn,storage_code)
            SELECT "{$stock_date}",bp.branch_id,bp.product_id,bp.store,bp.arrive_store,bp.store_freeze,bp.unit_cost,bp.inventory_cost,bm.material_bn,b.branch_bn,b.storage_code
            FROM sdb_ome_branch_product bp 
            LEFT JOIN sdb_material_basic_material bm ON(bp.product_id=bm.bm_id)
            LEFT JOIN sdb_ome_branch b ON(bp.branch_id=b.branch_id)
SQL;
        kernel::database()->exec($sql);
    }

    /**
     * 门店库存
     *
     * @return void
     * @author
     **/
    private function insertStoreStock($stock_date)
    {
        $sql = <<<SQL
            INSERT INTO sdb_ome_dailystock(stock_date,branch_id,product_id,stock_num,arrive_stock,freeze_stock,unit_cost,inventory_cost,product_bn,branch_bn,storage_code)
            SELECT "{$stock_date}",ps.branch_id,ps.bm_id,ps.store,ps.arrive_store,ps.arrive_store,0,0,bm.material_bn,b.branch_bn,b.storage_code
            FROM sdb_o2o_product_store ps
            LEFT JOIN sdb_material_basic_material bm ON(ps.bm_id=bm.bm_id)
            LEFT JOIN sdb_ome_branch b ON(ps.branch_id=b.branch_id)
SQL;
        //sdb_o2o_product_store 已经不使用， 统一使用sdb_ome_branch_product
        //kernel::database()->exec($sql);
    }

    /**
     * 出入库
     *
     * @return void
     * @author
     **/
    private function insertIOStock($stock_date, $type_id, $col = [])
    {
        $bmMdl     = app::get('material')->model('basic_material');
        $dsMdl     = app::get('ome')->model('dailystock');
        $branchMdl = app::get('ome')->model('branch');

        $st_time = strtotime($stock_date);
        $et_time = $st_time + 86400;

        $type_id = implode(',', $type_id);

        $sql = <<<SQL
        SELECT branch_id,bn,SUM(nums) nums, SUM(inventory_cost) inventory_cost
        FROM sdb_ome_iostock
        WHERE create_time>={$st_time}
            AND create_time < {$et_time}
            AND type_id IN({$type_id})
        GROUP BY branch_id,bn
SQL;

        $offset = 0;
        $limit  = 2000;
        do {
            $rows = kernel::database()->selectlimit($sql, $limit, $offset);

            if (!$rows) {
                break;
            }

            $bmList = $bmMdl->getList('bm_id,material_bn', [
                'material_bn' => array_unique(array_column($rows, 'bn')),
            ]);
            $bmList = array_column($bmList, null, 'material_bn');

            $branchList = $branchMdl->getList('branch_id,branch_bn', [
                'branch_id' => array_unique(array_column($rows, 'branch_id')),
            ]);
            $branchList = array_column($branchList, null, 'branch_id');

            foreach ($rows as $key => $value) {
                $bm     = $bmList[$value['bn']];
                $branch = $branchList[$value['branch_id']];

                $dsMdl->update([
                    $col['nums'] => $value['nums'],
                    $col['cost'] => $value['inventory_cost'],
                    'product_bn' => $value['bn'],
                    'branch_bn'  => $branch['branch_bn'],
                ], [
                    'branch_id'  => $value['branch_id'],
                    'product_id' => $bm['bm_id'],
                    'stock_date' => $stock_date,
                ]);
            }

            $offset += $limit;
        } while (true);
    }
}
