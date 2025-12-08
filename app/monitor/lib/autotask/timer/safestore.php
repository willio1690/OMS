<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/18
 * @Describe: 系统预警库存报警通知
 */
class monitor_autotask_timer_safestore
{
    
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
        
        // 判断执行时间
        base_kvstore::instance('monitor')->fetch('process_safestore', $lastExecTime);
        
        // 脚本已经执行过
        if ($lastExecTime && $lastExecTime > (time() - 300)) {
            $error_msg = '5分钟内不能重复执行';
            return false;
        }
        base_kvstore::instance('monitor')->store('process_safestore', time());
        
        $branchProductMdl = app::get('ome')->model('branch_product');
        
        //第一次查所有的
        $filter = [];
        if ($lastExecTime) {
            $filter['last_modified|than'] = $lastExecTime;
        }
        $filter['filter_sql'] = 'safe_store >= store';
        $offset = 0;
        $limit  = 500;
        $data = [];
        do {
            $productList = $branchProductMdl->getList('*',$filter,$offset, $limit);
            if (empty($productList)) {
                break;
            }
            $productIds = array_column($productList,'product_id');
            $branchIds = array_column($productList,'branch_id');
            $material = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name',['bm_id'=>$productIds]);
            $material = array_column($material,null,'bm_id');
            $branch = app::get('ome')->model('branch')->getList('branch_id,name',['branch_id'=>$branchIds]);
            $branch = array_column($branch,null,'branch_id');
            foreach ($productList as $val) {
                if($val['safe_store'] > 0 && $val['store'] <= $val['safe_store']) {
                    $materialInfo  = $material[$val['product_id']];
                    $branchInfo  = $branch[$val['branch_id']];
                    $data[] = [
                        'branch_name' => $branchInfo['name'],
                        'bn'          => $materialInfo['material_bn'],
                        'goods_name'  => $materialInfo['material_name'],
                        'store'       => $val['store'],
                        'safe_store'  => $val['safe_store'],
                    ];
                }
            }
            $offset += $limit;
        } while (true);
        
        if ($data) {
            $params = ['list'  => $data];
            kernel::single('monitor_event_notify')->addNotify('under_safty_inventory', $params);
        }
        
        return true;
        
    }
}
