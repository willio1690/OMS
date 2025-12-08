<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 同步淘宝宝贝
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_shop_sync
{
    const DOWNLOAD_ALL_LIMIT = 40;//每页下载宝贝数量
    
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 店铺批量下载
     *
     * @return void
     * @author
     **/
    public function downloadList($shop_id, $filter, $page, &$errormsg)
    {
        $shopObj     = app::get('ome')->model('shop');
        $shop        = $shopObj->dump(array('shop_id'=>$shop_id), 'shop_id,shop_bn,name,node_id,shop_type,business_type');
        if(empty($shop))
        {
            $errormsg    = '店铺不存在！';
            return false;
        }

        if (empty($shop['node_id']))
        {
            $errormsg    = '店铺未绑定！';
            return false;
        }

        if($shop['shop_type'] != 'taobao')
        {
            $errormsg    = "暂不支持对店铺【{$shop['name']}】商品的同步!";
            return false;
        }

        #开始下载
        $shopfactory    = tbo2o_shop_service_factory::createFactory($shop['shop_type'], $shop['business_type']);
        if ($shopfactory == false)
        {
            $errormsg    = '店铺类型有误！';
            return false;
        }

        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $data    = $shopfactory->downloadList($filter, $shop_id, $page, self::DOWNLOAD_ALL_LIMIT, $errormsg);
        if($data === false)
        {
            return false;
        }

        if($data)
        {
            $stores         = array();
            $shopItemLib    = kernel::single('tbo2o_shop_items');
            $shopSkuLib     = kernel::single('tbo2o_shop_skus');
            
            $shopSkuLib->batchInsert($data, $shop);
            $shopItemLib->batchInsert($data, $shop);
        }

        return true;
    }

    /**
     * 保存店铺同步状态
     *
     * @return void
     * @author
     **/
    public function setShopSync($shop_id,$value)
    {
        base_kvstore::instance('tbo2o/shop/synchronizing')->store('shop_synchronizing_'.$shop_id, $value, (time()+3600));
    }

    /**
     * 获取同步状态
     *
     * @return void
     * @author
     **/
    public function getShopSync($shop_id)
    {
        $sync    = '';
        base_kvstore::instance('tbo2o/shop/synchronizing')->fetch('shop_synchronizing_'.$shop_id, $sync);
        return ($sync === 'true') ? 'true' : 'false';
    }
}