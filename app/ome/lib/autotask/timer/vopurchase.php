<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_timer_vopurchase
{
    /* 当前的执行时间 */
    public static $now;
    
    /* 执行的间隔时间 */
    const intervalTime = 1800;
    
    function __construct()
    {
        self::$now = time();
    }
    
    /**
     * 根据供应商获取po列表
     */
    public function process($params, &$error_msg='')
    {
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        ignore_user_abort(1);

        base_kvstore::instance('console/vop/purchase')->fetch('apply-lastexectime',$lastExecTime);
        if($lastExecTime && ($lastExecTime+self::intervalTime)>self::$now) {
            return false;
        }
        
        base_kvstore::instance('console/vop/purchase')->store('apply-lastexectime', self::$now);
        
        //获取唯品会店铺
        $shopObj    = app::get('ome')->model('shop');
        $shopList   = $shopObj->getList('shop_id, shop_bn, name, config', array('node_type'=>'vop', 'node_id|noequal'=>'', 'tbbusiness_type'=>'jit'));
        if(empty($shopList))
        {
            return false;
        }
        
        foreach ($shopList as $key => $shop_info)
        {
            $config = @unserialize($shop_info['config']);
            if (!$config || $config['download_jit_auto'] != 'yes') {
                continue;
            }

            // 组织数据
            $month_time    = strtotime('-1 month');

            $params = array(
                //'st_sell_st_time' => date('Y-m-d H:i:s', $month_time),
            );

            kernel::single('vop_purchase_order')->getPullList($params, $shop_info['shop_id']);
        }

        return true;
    }
}