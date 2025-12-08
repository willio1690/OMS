<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会平台商品处理
 */
class inventorydepth_service_shop_meituan4bulkpurchasing extends inventorydepth_service_shop_common
{

    //定义每页拉取数据(平台限制每页最多200条)
    public $customLimit = 50;

    public function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 下载全部商品(包括普通现货、专供现货)
     *
     * @param array $filter
     * @param string $shop_id
     * @param int $offset
     * @param int $limit
     * @param string $limit
     * @return array
     */
    public function downloadList($filter, $shop_id, $offset = 0, $limit = 30, &$errormsg = null)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');

        //开始执行
        $result = $shopService->items_all_get($filter, $shop_id, $offset, $limit);
        if ($result === false) {
            $errormsg = $shopService->get_err_msg();
            return false;
        }

        //矩阵数据为空
        if (empty($result['goods_list'])) {
            $this->totalResults = 0;
            return array();
        }

        $this->totalResults = $result['total'];

        //格式化
        $data = $spuList = [];
        foreach ($result['goods_list'] as $k => $v) {

            if (!$spuList[$v['goods_id']]) {
                $spuList[$v['goods_id']] = [
                    'outer_id'       => $v['goods_id'], //spu商品编号
                    'title'          => $v['title'],
                    'approve_status' => $v['sell_status'] == '0' ? 'onsale' : 'instock', //上下架
                ];
            }
            foreach($v['sku_list'] as $vv) {
                $spuList[$v['goods_id']]['skus'][$vv['sku_code']] = [
                    'sku_id'   => $vv['sku_id'],
                    'outer_id' => $vv['sku_code'], //sku货号
                    'sku_wid'  => $vv['sku_code'],
                    'title'    => $v['title'], //货品名称
                ];
            }
        }

        foreach ($spuList as $spuBn => $spuInfo) {

            $skuList = [];
            foreach ($spuInfo['skus'] as $key => $val) {
                $skuList['sku'][] = $val;
            }
            $data[] = array(
                'outer_id'       => $spuBn, //spu商品编号
                'iid'            => $spuBn,
                'title'          => $spuInfo['title'],
                'approve_status' => $spuInfo['approve_status'],
                'simple'         => 'false',
                'skus'           => $skuList,
            );
        }
        //销毁
        unset($result, $skuList);

        return $data;
    }

    /**
     * 通过IID批量下载商品
     *
     * @param array $iids
     * @param string $shop_id
     * @param string $errormsg
     * @return array
     */
    public function downloadByIIds($iids, $shop_id, &$errormsg = null)
    {
        $errormsg = '不支持通过IID批量下载';

        return false;
    }

    /**
     * 通过IID下载单个商品
     *
     * @param array $iid
     * @param string $shop_id
     * @param string $errormsg
     * @return array
     */
    public function downloadByIId($iid, $shop_id, &$errormsg = null)
    {
        $errormsg = '不支持通过IID下载 单个';

        return false;
    }
}
