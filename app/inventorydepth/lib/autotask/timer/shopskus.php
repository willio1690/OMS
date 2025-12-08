<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class inventorydepth_autotask_timer_shopskus
{
    public function process($params, &$error_msg='')
    {
        set_time_limit(0);
        ignore_user_abort(1);

        $now = time();

        // 防并发处理，判断是否正在执行
        base_kvstore::instance('inventorydepth')->fetch(__CLASS__ . '_running',$isRunning);
        if ($isRunning) {
            $error_msg = '正在执行中，请稍后再试';
            return false;
        }

        // 设置正在执行
        base_kvstore::instance('inventorydepth')->store(__CLASS__ . '_running', '1', 1800);

        // 获取上一次执行的时间
        base_kvstore::instance('inventorydepth')->fetch(__CLASS__,$lastExecTime);

        // 如果不存在，取一天前时间
        if(!$lastExecTime) {
            $lastExecTime = $now - 86400;
        }
        
        // 同步JDL商品
        $shopList = app::get('ome')->model('shop')->getList('shop_id,shop_type,business_type', [
            'business_type' => 'jdlvmi',
            'node_type'     => '360buy',
            'delivery_mode' => 'self',
            'filter_sql'    => ' {table}node_id is not null and {table}node_id !="" '
        ]);
        foreach ($shopList as $shop) {
            $this->syncJDLGoods($shop, $lastExecTime, $now);
        }

        // 同步其他渠道商品缓存
        $shopList = app::get('ome')->model('shop')->getList('shop_id,shop_type,business_type', [
            'delivery_mode' => 'self',
            'filter_sql'    => ' {table}node_id is not null and {table}node_id !="" '
        ]);
        foreach ($shopList as $shop) {
            $this->syncOtherGoods($shop, $lastExecTime, $now);
        }

        // 保存更新时间
        base_kvstore::instance('inventorydepth')->store(__CLASS__,$now);

        return true;
    }


    /**
     * 同步其他渠道商品缓存
     * 
     * @return array
     */
    public function syncOtherGoods($shop, $start_modified, $end_modified)
    {
        $page = 1;

        do {


            $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'],$shop['business_type']);
            if ($shopfactory === false) {
                return [false, '工厂生产类失败!'];
            }

            $filter = [];
            $filter['start_time']   = date('Y-m-d H:i:s', $start_modified);
            $filter['end_time']     = date('Y-m-d H:i:s', $end_modified);
            
    
            try {
                $result = kernel::single('inventorydepth_shop')->downloadCacheProductList($shop['shop_id'], $filter, $page, $errormsg);
            } catch (Exception $e) {
                return [false, '同步失败：网络异常'];
            }
    
            if ($result['rsp'] == 'fail') {
                return [false, $errormsg];
            }

            $totalCount = is_array($result['data']) ? $result['data']['count'] : 0;


            $customLimit = $shopfactory->getCustomLimit();
            $customLimit = $customLimit > 0 ? $customLimit : inventorydepth_shop::DOWNLOAD_ALL_LIMIT;

            if ($page >= ceil($totalCount / $customLimit) || $totalCount == 0) {
                return [true, '同步完成'];
            }

            $page++;
        } while (true);

        return [true, '同步完成'];
    }

    /**
     * 同步JDL商品
     *
     * @return array
     **/
    public function syncJDLGoods($shop, $start_modified, $end_modified)
    {
        $page = 1;

        do {

            $shopLib = kernel::single('inventorydepth_shop');

            $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'], $shop['business_type']);
            if ($shopfactory == false) {
                return [false, '工厂生产类失败!'];
            }

            $approve_status = $shopfactory->get_approve_status(0, $exist);
            if ($exist == false) {
                return [false, '标记异常!'];
            }

            $approve_status = [];
            $approve_status['filter']['start_modified'] = date('Y-m-d H:i:s', $start_modified);
            $approve_status['filter']['end_modified']   = date('Y-m-d H:i:s', $end_modified);
            $errormsg = [];
            try {
                $result = $shopLib->downloadList($shop['shop_id'], $approve_status['filter'], $page, $errormsg);
            } catch (Exception $e) {
                return [false, '同步失败：网络异常'];
            }

            $totalResults   = $shopfactory->getTotalResults();

            $customLimit = $shopfactory->getCustomLimit();
            $customLimit  = ($customLimit > 0 ? $customLimit : inventorydepth_shop::DOWNLOAD_ALL_LIMIT);

            if ($page >= ceil($totalResults / $customLimit) || $totalResults == 0) {
                return [true, '同步完成'];
            } 

            $page++;
        } while (true);

        return [true, '同步完成'];
    }
}
