<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * pos发货单对接shopex pos
 *
 * https://docs.pekon.com/docCenter/home?docId=8b76bfb5
 *
 * @author sunjing@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_openapi_pekon_request_stock extends erpapi_store_request_stock
{
    /**
     * stock_get
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function stock_get($sdf)
    {
        $title = sprintf('【%s】库存查询', $this->__channelObj->store['name']);

        // if (!$sdf['material_bn']) {
        //     return $this->error('缺少基础物料编码');
        // }

        $warehouseCode = POS_DEFAULT_BRANCH;
        if (false !== strpos($sdf['branch_bn'], '_')) {
            $warehouseCode = array_pop(explode('_', $sdf['branch_bn']));
        }

        $store_bn = $this->__channelObj->store['store_bn'];
        $params = [
            'salesOrgCode'  => $this->__channelObj->store['store_bn'],
            'warehouseCode' => $warehouseCode,
            'pageNumber'    => $sdf['page_no'] ?: 1,
            'pageSize'      => $sdf['page_size'] ?: 100,
        ];

        if ($sdf['material_bn']) {
            $params['skuCodeArr'] = array_unique($sdf['material_bn']);
        }

        return $this->call('queryInventory', $params, null, $title, 30, $store_bn);
    }
}
