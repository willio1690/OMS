<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/6/27 18:09:55
 * @describe 处理店铺商品相关类
 */
class erpapi_shop_matrix_tmall_request_maochao_product extends erpapi_shop_request_product {

    protected function getUpdateStockApi() {
        $api_name = SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC;
        return $api_name;
    }

    /**
     * format_stocks
     * @param mixed $stockList stockList
     * @return mixed 返回值
     */

    public function format_stocks($stockList) {
        $firstStock = current($stockList);
        $shop_id = $this->__channelObj->channel['shop_id'];
        if(empty($firstStock['branch_bn'])) {
            $memo  = '未使用分仓独立回写，不能回写';
            $optLogModel = app::get('inventorydepth')->model('operation_log');
            $optLogModel->write_log('shop', $shop_id, 'stockup',$memo);
            return false;
        }
        $bns = array();
        foreach ($stockList as $key => $val)
        {
            $product_bn = trim($val['bn']);
            $bns[$product_bn] = $product_bn;
        }
        
        //按店铺+货号查询
        $skuObj = app::get('inventorydepth')->model('shop_skus');
        $tempList = $skuObj->getList('shop_sku_id,shop_product_bn,shop_iid', array('shop_id'=>$shop_id, 'shop_product_bn'=>$bns));
        if(empty($tempList)){
            return false;
        }
        $shopBnList = [];
        foreach ($tempList as $key => $value) {
            $shopBnList[$value['shop_product_bn']][] = $value;
        }
        $detail_operation_list = [];
        $list_quantity = [];
        foreach ($stockList as $key => $value) {
            $list_quantity[$value['bn']] = [
                'bn' => $value['bn'],
                'quantity' => $value['quantity'],
            ];
            foreach ($shopBnList[$value['bn']] as $k => $val) {
                $list_quantity[$value['bn']]['sc_item_id'] = $val['shop_iid'];
                $tmp = [
                    "item" => [
                        "outer_id" => $value['bn'],
                        "sc_item_id" => $val['shop_iid']
                    ],
                    "inventory_line_list" => [[
                        "inventory_line" => [
                            "quantity"=> $value['quantity']
                        ]
                    ]],
                    "additional_info" => [
                        "attribute" => [
                            "inv_operate_mode" => "FULLAMOUNT",
                            "supplier_id" => $val['shop_sku_id']
                        ]
                    ],
                    "location"=> [
                        "store_code"=> $value['branch_bn']
                    ],
                    "detail_order"=> [
                        "operation_detail_order_id"=> count($detail_operation_list)
                    ]
                ];
                $detail_operation_list[] = $tmp;
            }
        }
        if(empty($list_quantity)) {
            return false;
        }
        $inventory_main_operation = [[
            'main_order'=>[
                'user_id' =>  $this->__channelObj->channel['addon']['tb_user_id'],
                'operation_order_id' => $this->uniqid()
            ],
            'detail_operation_list' => $detail_operation_list,
        ]];
        $return = [
            'tmall_type'=>'direct_marketing',
            'inventory_main_operation' =>json_encode($inventory_main_operation),
            'list_quantity' => json_encode(array_values($list_quantity))
        ];
        return $return;
    }

    protected function _getUpdateStockParams($stocks) {
        return $stocks;
    }

    #实时下载店铺商品
    /**
     * skuAllGet
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function skuAllGet($sdf)
    {
        $timeout = 20;
        $param = array(
            'page_no' => $sdf['page'],
            'page_size' => $sdf['page_size'],
            'begin_time' => $sdf['start_time'],
            'end_time' => $sdf['end_time'],
            'supplier_id' => $sdf['supplier_id'],
        );
        $title = "获取店铺(" . $this->__channelObj->channel['name'] .')商品';
        $result = $this->__caller->call(SHOP_GET_SUPPLIER_PRODUCTS,$param,array(),$title,$timeout, $param['supplier_id']);
        if ($result['res_ltype'] > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->__caller->call(SHOP_GET_SUPPLIER_PRODUCTS,$param,array(),$title,$timeout, $param['supplier_id']);
                if ($result['res_ltype'] == 0) {
                    break;
                }
            }
        }
        if($result['data']) {
            $data = json_decode($result['data'], true);
            $result['data'] = [];
            if(empty($data['data']['data']['page_data']['page_data'])) {
                return $result;
            }
            foreach ($data['data']['data']['page_data']['page_data'] as $value) {
                $result['data'][] = [
                    'iid' => $value['sc_item_id'],
                    'title' => $value['sc_item_name'],
                    'outer_id' => $value['outer_id'],
                    'sku' => [
                        'outer_id' => $value['outer_id'],
                        'sku_id' => $value['supplier_id'],
                        'barcode' => $value['barcode'],
                    ]
                ];
            }

        }
        return $result;
    }
}