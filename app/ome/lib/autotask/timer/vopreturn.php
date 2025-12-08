<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_timer_vopreturn
{
    /* 当前的执行时间 */
    public static $now;
    
    /* 执行的间隔时间 */
    const intervalTime = 3600;
    
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
        $vopbill_set = app::get('ome')->getConf('ome.vopbill.set');

        if($vopbill_set!='on'){
            return true;
        }
    
        base_kvstore::instance(__CLASS__)->fetch('status', $status);
        if ($status == 'running') {
            $error_msg = '同步退供差异单数据正在运行，请勿重复操作！';
            return true;
        }
    
        base_kvstore::instance(__CLASS__)->store('status', 'running', '3600');

        // 获取最后一次的结束时间
        base_kvstore::instance('console/vop/return')->fetch('apply-enddate', $endDate);
        !$endDate && $endDate = self::$now - self::intervalTime;

        $data = array(
            'start_date'    =>  date('Y-m-d H:i:s', $endDate),
            'end_date'      =>  date('Y-m-d H:i:s', self::$now),
        );
        kernel::single('ome_event_trigger_shop_vopjit')->getReturnInfo($data);
        
        base_kvstore::instance('console/vop/return')->store('apply-enddate', self::$now);
        base_kvstore::instance(__CLASS__)->delete('status');
        return true;
    }

}