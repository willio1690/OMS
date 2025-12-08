<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会供货价
 * 
 * @author chenping
 * @version 0.1
 */
class purchase_purchase_sku
{
    /**
     * 获取供货价
     *
     * @param string $shop_id 店铺ID
     * @param string $po_no 采购单号
     * @param string $barcodes 商品条码
     * @return array
     **/
    public function getSkuPriceInfo($shop_id, $po_no, $barcodes)
    {
        $order = app::get('purchase')->model('order')->db_dump(['po_bn' => $po_no], 'po_id');

        
        // API查询
		$query_params = [
            'po_no' => $po_no,
            'barcodes' => $barcodes,
        ];

        $result = kernel::single('erpapi_router_request')->set('shop',$shop_id)->purchase_getSkuPriceInfo($query_params);
        
        if($result['rsp'] != 'succ')
        {
            return [false, $result['msg']];
        }

        $data = [];
        foreach ($result['data']['price_list'] as $value){
            $data[] = [
                'po_id' => $order['po_id'],
                'po_bn' => $po_no,
                'barcode' => $value['barcode'],
                'actual_market_price' => $value['actual_market_price'],
                'actual_unit_price' => $value['actual_unit_price'],
                'price' => bcdiv($value['actual_market_price'],0.8,3),//原价
            ];
        }

        if ($data) {
            $mdl = app::get('purchase')->model('order_sku_price');

            $sql = ome_func::get_replace_sql($mdl, $data);

            $result = kernel::database()->exec($sql);

            if (!$result) {
                return [false, kernel::database()->errorinfo()];
            }
        }

        return [true, '成功', $data];
    }
}