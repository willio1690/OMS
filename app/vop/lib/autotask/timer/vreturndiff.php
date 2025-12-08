<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_autotask_timer_vreturndiff {

    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg='')
    {
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        ignore_user_abort(1);

        base_kvstore::instance(__CLASS__)->fetch('status', $status);

        if ($status == 'running') {
            $error_msg = '同步退供差异单数据正在运行，请勿重复操作！';
            return true;
        }

        base_kvstore::instance(__CLASS__)->store('status', 'running', '1800');

        base_kvstore::instance(__CLASS__)->fetch('lasttime', $lasttime);
        if (!$lasttime) {
            // 默认查7天前的
            $lasttime = strtotime("-7 day");
            
        }

        $now = time();

        base_kvstore::instance(__CLASS__)->store('lasttime', $now);


        $filter = [
            'begin_time' => date('Y-m-d H:i:s', $lasttime),
            'end_time' => date('Y-m-d H:i:s', $now),
        ];

        $vopShopList = app::get('ome')->model('shop')->getList('shop_id,name,config', [
            'node_type'=>'vop', 
            'node_id|noequal'=>'', 
            'tbbusiness_type'=>'jit',
        ]);

        if (!$vopShopList) {
            $error_msg = '没有找到vop店铺，请先同步vop店铺！';
            return true;
        }

        $msgArr = [];
        foreach ($vopShopList as $shop) {
            $config = is_string($shop['config']) ? @unserialize($shop['config']) : $shop['config'];

            if (!is_array($config) || $config['download_vreturndiff_auto'] != 'yes') {
                continue;
            }


            list($result, $msg) = kernel::single('vop_vreturn_diff')->getPullList($filter, $shop['shop_id']);
            
            $msgArr[] = sprintf('店铺[%s]：%s', $shop['name'], $msg);
        }

        $error_msg = implode('；', $msgArr);

        base_kvstore::instance(__CLASS__)->delete('status');

        return true;
    }
}   