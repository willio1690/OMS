<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_offline_stock extends inventorydepth_logic_abstract
{

    public function getStock($product_bn, $branch_id, $shop_id)
    {
        # 读取商品要执行的规则
        $iocObj = kernel::single('inventorydepth_offline_calculation');
        $quantity = $iocObj->get_actual_stock($product_bn, $shop_id, $branch_id);
        if ($quantity === false) {return false;}
        $stock = array(
            'store_code' => $iocObj->get_branch_bn($branch_id),
            'bn'       => $product_bn,
            'quantity' => $quantity,
            'regulation' => $iocObj->get_actual_stock_make($product_bn, $shop_id, $branch_id)
        );

        return $stock;
    }

    public function dealWithRegu($pbn, $shop_id, $branch_id, $type)
    {
        $regu = $this->getRegu($shop_id);
        $this->regulationShow = '';
        foreach ($regu as $r) {
            if (empty($r['regulation'])) {
                continue;
            }
            # 判断是否满足规则
            $params = array(
                'shop_product_bn' => $pbn,
                'shop_id'         => $shop_id,
                'branch_id'       => $branch_id
            );
            
            if ($r['regulation']['content']['stockupdate'] != 1) {
                return false;
            }

            $quantity = kernel::single('inventorydepth_stock',[app::get('inventorydepth'), 3])->formulaRun($r['regulation']['content']['result'], $params, $msg, $type);

            if ($quantity === false) {continue;}

            $this->regulationShow = $msg;

            break;
        }

        return is_null($quantity) ? false : $quantity;
    }

    public function getPkgStock($product_bn, $branch_id, $shop_id)
    {
        # 读取商品要执行的规则
        $quantity = $this->dealWithRegu($product_bn, $shop_id, $branch_id, 'pkg_');
        if ($quantity === false) {return false;}
        $stock = array(
            'store_code' => kernel::single('inventorydepth_offline_calculation')->get_branch_bn($branch_id),
            'bn'       => $product_bn,
            'quantity' => $quantity,
            'regulation' => $this->regulationShow
        );

        return $stock;
    }

    /**
     * @description 获取指定店铺的所有规则
     * @access public
     * @param void
     * @return void
     */
    public function getRegu($shop_id)
    {
        static $regu;

        if ($regu[$shop_id]) {
            return $regu[$shop_id];
        }

        $filter = array(
            'using'            => 'true',
            'al_exec'          => 'false',
            'condition'        => 'stock',
            'type'             => ['3'],
            'filter_sql'       => "(shop_id='_ALL_' || FIND_IN_SET('{$shop_id}',shop_id) )",
        );
        $rows = app::get('inventorydepth')->model('regulation_apply')->getList('*', $filter, 0, -1, 'type desc,priority desc');

        foreach ($rows as $key => $value) {
            $rows[$key]['shop_id']     = explode(',', $value['shop_id']);
            $rows[$key]['apply_goods'] = explode(',', $value['apply_goods']);
            $rows[$key]['regulation']  = &$regulation[$value['regulation_id']];
        }

        if ($regulation) {
            $rr = app::get('inventorydepth')->model('regulation')->getList('*', array('regulation_id' => array_keys($regulation), 'using' => 'true'));
            foreach ($rr as $r) {
                $regulation[$r['regulation_id']] = $r;
            }
        }

        $regu[$shop_id] = $rows;

        return $regu[$shop_id];
    }

}
