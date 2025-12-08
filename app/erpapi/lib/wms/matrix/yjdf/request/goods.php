<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/10/9 17:00:48
 * @describe: 类
 * ============================
 */
class erpapi_wms_matrix_yjdf_request_goods extends erpapi_wms_request_delivery
{

    /**
     * goods_syncGet
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function goods_syncGet($sdf)
    {
        if (isset($sdf['channel_id'])) {
            $warehouseCode = $sdf['channel_id'];
        } else {
            $warehouseCode = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch']['branch_bn']);
        }

        if ($sdf['scroll_id']) {
            $params = array(
                'scroll_id'      => $sdf['scroll_id'],
                'warehouse_code' => $warehouseCode,
            );
        } else {
            $params = array(
                'page_size'      => 10,
                'warehouse_code' => $warehouseCode,
            );
            if ($sdf['start_time']) {
                $params['start_modified'] = $sdf['start_time'] . ' 00:00:00';
            }
            
            if($sdf['start_ymdhis']) {
                $params['start_modified'] = $sdf['start_ymdhis'];
            }
            
            if($sdf['end_time']) {
                $params['end_modified'] = $sdf['end_time'] . ' 23:59:59';
            }
            
            if($sdf['end_ymdhis']) {
                $params['end_modified'] = $sdf['end_ymdhis'];
            }
        }
        $title = '同步商品，' .$params['start_modified'] . ' - '. $params['end_modified'];
        $rs = $this->__caller->call(WMS_ITEM_GET, $params, array(), $title, 10, $warehouseCode);
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], 1);
            $tmpData = array();
            $tmpData['scrollId'] = $data['scrollId'];
            $tmpData['total']    = $data['total'];
            foreach ($data['entries'] as $v) {
                $tmpData['items'][] = array(
                    'outer_sku'     => $v['skuId'],
                    'inner_sku'     => $v['skuId'],
                    'material_name' => $v['skuName'],
//                    'sku_status'    => $v['skuStatus'], //上下架状态(1: 上架 0:下架)上下架状态以详情的为主
                    'channel_id'    => $warehouseCode,
                );
            }
            $rs['data'] = $tmpData;
        }
        return $rs;
    }

    /**
     * goods_syncPrice
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function goods_syncPrice($sdf)
    {
        if (isset($sdf['channel_id'])) {
            $warehouseCode = $sdf['channel_id'];
        } else {
            $warehouseCode = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch']['branch_bn']);
        }

        $skuId = array();
        foreach ($sdf['data'] as $v) {
            $skuId[$v['outer_sku']] = $v['outer_sku'];
        }
        $params = array(
            'skus'           => json_encode(array_values($skuId)),
            'warehouse_code' => $warehouseCode,
        );
        $title = '同步商品价格' . $params['skus'];
        $rs = $this->__caller->call(WMS_ITEM_PRICE_GET, $params, array(), $title, 10, $warehouseCode);
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], 1);
            $tmpData = array();
            foreach ($data['skuPriceList'] as $v) {
                $tmpData['items'][] = array(
                    'outer_sku'    => $v['skuId'],
                    'price'        => $v['skuPrice'],
                    'errorMessage' => $v['errorMessage'],
                    'rsp'          => $v['isSuccess'] ? 'succ' : 'fail',
                );
            }
            $rs['data'] = $tmpData;
        }
        return $rs;
    }

    /**
     * 商品详情
     * @param $sdf
     * @return mixed
     */
    public function goods_syncDetail($sdf)
    {
        if (isset($sdf['channel_id'])) {
            $warehouseCode = $sdf['channel_id'];
        } else {
            $warehouseCode = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch']['branch_bn']);
        }
        $skuId = array();
        foreach ($sdf['data'] as $v) {
            $skuId[$v['outer_sku']] = $v['outer_sku'];
        }
        $params = array(
            'skus'           => json_encode(array_values($skuId)),
            'warehouse_code' => $warehouseCode,
        );
        $title = '同步商品详情' . $params['skus'];
        $rs = $this->__caller->call(WMS_ITEM_DETAIL_GET, $params, array(), $title, 10, $warehouseCode);
        
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], 1);
            $data = $data['data'];

            $tmpData = array();
            foreach ($data as $key =>$v) {
                $images = '';
                if (isset($data[$key]['imageInfos']) && !empty($data[$key]['imageInfos'])) {
                    $images = implode('|',array_column($data[$key]['imageInfos'],'path'));
                }
                $materialBaseInfo = $data[$key]['skuBaseInfo'];
                $materialCat = $data[$key]['extAtts'];
                $tmpData['items'][] = array(
                    'outer_sku'   => $v['skuId'],
                    'base_info'   => $materialBaseInfo,
                    'images'      => $images,
                    'materialCat' => $materialCat,
                );
            }
            $rs['data'] = $tmpData;
        }

        return $rs;
    }

    /**
     * 同步库存
     * 
     * @return void
     * @author
     */
    public function goods_syncStore($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '查询库存[' . implode(',', array_column($sdf['skus'], 'material_bn')) . ']';

        $params = $this->_format_syncstore_params($sdf);

        $branch_bn = $sdf['addr']['branch_bn'];

        $callback_params = array(
            'skus'      => $sdf['skus'],
            'branch_id' => $sdf['addr']['branch_id'],
            'branch_bn' => $branch_bn,
            'node_id'   => $this->__channelObj->wms['node_id'],
            'wms_id'    => $this->__channelObj->wms['channel_id'],
        );

        $result = $this->__caller->call(WMS_ITEM_INVENTORY_QUERY, $params, $callback, $title, 10, $branch_bn);

        return $this->synStore_callback($result, $callback_params);
    }

    /**
     * summary
     * 
     * @return void
     * @author
     */
    protected function _format_syncstore_params($sdf)
    {
        $sku_list = [];
        foreach ($sdf['skus'] as $sku) {
            $sku_list[] = [
                'skuId'    => $sku['material_bn'],
                'quantity' => $sku['warn_num'],
            ];
        }

        $address = [
            'provinceId'  => (int) $sdf['addr']['provinceId'],
            'cityId'      => (int) $sdf['addr']['cityId'],
            'townId'      => (int) $sdf['addr']['townId'],
            'countyId'    => (int) $sdf['addr']['countyId'],
            'fullAddress' => $sdf['addr']['province'] . $sdf['addr']['city'] . $sdf['addr']['street'] . $sdf['addr']['town'] . $sdf['addr']['address'],
        ];

        $params = [
            'address'        => json_encode($address),
            'sku_list'       => json_encode($sku_list),
            'warehouse_code' => $sdf['channel_id'],
        ];

        return $params;
    }

    /**
     * summary
     * 
     * @return void
     * @author
     */
    public function synStore_callback($response, $callback_params)
    {
        if ($response['rsp'] != 'succ') {
            return $this->callback($response, $callback_params);
        }

        $data = @json_decode($response['data'], true);

        if (!$data) {
            return $this->callback($response, $callback_params);
        }

        $skuId = [];
        foreach ($data['data'] as $d) {
            if ($d['areaStockState'] == 0) {
                $skuId[] = $d['skuQuantity']['skuId'];
            }
        }

        if (!$skuId) {
            return $this->callback($response, $callback_params);
        }

        // 查询库存
        $product_id = array_column($callback_params['skus'], 'bm_id');
        $branch_id  = $callback_params['branch_id'];

        $bpMdl = app::get('ome')->model('branch_product');
        $list  = $bpMdl->getList('*', array('branch_id' => $branch_id, 'product_id' => $product_id, 'store|than' => 0));

        if (!$list) {
            return $this->callback($response, $callback_params);
        }

        $branch_product = [];
        foreach ($list as $l) {
            $branch_product[$l['branch_id']][$l['product_id']] = $l['store'];
        }

        $inventory = array(
            'inventory_bn' => uniqid('kepler_'),
            'warehouse'    => $callback_params['branch_bn'],
            'memo'         => '同步开普勒库存',
            'autoconfirm'  => 'Y',
        );

        $items = array();
        foreach ($callback_params['skus'] as $sku) {
            if (!in_array($sku['material_bn'], $skuId)) {
                continue;
            }

            $item               = array();
            $item['product_bn'] = $sku['material_bn'];
            $item['item_id']    = $sku['material_bn'];
            $item['mode']       = '1';
            $item['normal_num'] = -$branch_product[$branch_id][$sku['bm_id']];

            $items[] = $item;
        }

        if (!$items) {
            return $this->callback($response, $callback_params);
        }

        $inventory['item'] = json_encode($items);
        kernel::single('erpapi_router_response')->set_node_id($callback_params['node_id'])->set_api_name('wms.inventory.add')->dispatch($inventory);

        return $this->callback($response, $callback_params);
    }
    
    /**
     * 查询京东云交易指定商品的库存
     * 
     * @param array $sdf
     * @return array 
     */
    public function goods_selectStore($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] .'查询库存[' . implode(',', array_column($sdf['skus'], 'material_bn')) . ']';
        
        $res = ['rsp' => 'succ', 'data' => [0=>[], 1=>[]]];
        
        foreach ($sdf['addrs'] as $addr)
        {
            if (!is_numeric($addr['warn_num']) || $addr['warn_num'] < 0){
                continue;
            }
            
            $warn_num = $addr['warn_num'] > 50 ? 50 : $addr['warn_num'];
            
            $params = $this->_format_syncstore_params([
                'skus'          => $sdf['skus'],
                'addr'          => $addr,
                'warn_num'      => $warn_num,
                'channel_id'    => $sdf['channel_id'],
            ]);
            
            $result = $this->__caller->call(WMS_ITEM_INVENTORY_QUERY, $params, NULL, $title, 10, $addr['branch_bn']);
            if ($result['rsp'] == 'succ' && $data = @json_decode($result['data'], true)) {
                foreach ($data['data'] as $d)
                {
                    if ($d['areaStockState'] == 0) {
                        $res['data'][0][$d['skuQuantity']['skuId']] = $d['skuQuantity']['skuId'];
                    } elseif ($d['areaStockState'] == 1) {
                        $res['data'][1][$d['skuQuantity']['skuId']] = $d['skuQuantity']['skuId'];
                    }
                }
            }
        }
        
        return $res;
    }
}
