<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_material
{
    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params)
    {
        

        $bmList = app::get('material')->model('basic_material')->getList('*', $params['filter'], $params['offset'], $params['limit']);


        if (!$bmList) {
            return ['rsp' => 'succ', 'data' => []];
        }

        $count = app::get('material')->model('basic_material')->count($params['filter']);

        // 基础主档
        $bmIds    = array_column($bmList, 'bm_id');
        $bmExs = app::get('material')->model('basic_material_ext')->getList('*', ['bm_id' => $bmIds]);
        $bmExList = array_column($bmExs, null, 'bm_id');

        // 主档条码
        $bmBarcodeList = app::get('material')->model('codebase')->getList('*', [
            'bm_id' => $bmIds,
            'type'  => '1',
        ]);
        $bmBarcodeList = array_column($bmBarcodeList, null, 'bm_id');

        // 商品类型
        $typeList = app::get('ome')->model('goods_type')->getList('*', [
            'type_id' => array_column($bmExs, 'cat_id'),
        ]);
        $typeList = array_column($typeList, null, 'type_id');

      
        $arr_cat_id   = array_unique(array_column($bmList, 'cat_id'));
        $arr_cat_info = app::get('material')->model('basic_material_cat')->getList('*', array('cat_id' => $arr_cat_id));
        $arr_cat_info = array_column($arr_cat_info, null, 'cat_id');


        //商品品牌
        $arr_tmp_brand_id = array_unique(array_column($bmExs, 'brand_id'));
        foreach ($arr_tmp_brand_id as $b_id) {
            if ($b_id > 0) $arr_brand_id[] = $b_id;
        }
        if (!empty($arr_brand_id)) {
            $arr_brand_info = app::get('ome')->model('brand')->getList('*', array('brand_id' => $arr_brand_id));
            $arr_brand_info = array_column($arr_brand_info, null, 'brand_id');
        }


        $propsMdl = app::get('material')->model('basic_material_props');

        $propsList = $propsMdl->getlist('*', ['bm_id' => $bmIds]);

        $arr_props = array();
        foreach($propsList as $v){

            $arr_props[$v['bm_id']][$v['props_col']] = $v['props_value'];

        }
       

        $data = [];
        foreach ($bmList as $key => $value) {
            $bmEx = $bmExList[$value['bm_id']];
            $cat_info      = $arr_cat_info[$basicMaterial['cat_id']];
            $brand_info    = $arr_brand_info[$bmEx['brand_id']];
            $props_info = $arr_props[$bmEx['bm_id']];

            $data[] = [
                'material_bn'           => $value['material_bn'],
                'material_name'         => $value['material_name'],
                'weight'                =>  $bmEx['weight'],
                'cost'                  => $bmEx['cost'],
                'retail_price'          => $bmEx['retail_price'],
                'barcode'               => $bmBarcodeList[$value['bm_id']]['code'],
                'specifications'        => $bmEx['specifications'],
                'catetory_name'         =>  $cat_info['cat_name'],
                'catetory_code'         =>  $cat_info['cat_code'],
                'brand_name'            =>  $brand_info['brand_name'],
                'brand_code'            =>  $brand_info['brand_code'],
                'spu_code'              => $value['material_spu'],
                'unit'                  =>  $bmEx['unit'],
                'color'                  =>  $value['color'],
                'size'                  =>  $value['size'],
                'material_type_name'    => $typeList[$bmEx['cat_id']]['name'],
                'visibled'              => $value['visibled'],
                
                'season'                =>  $props_info['season'],
                'uppermatnm'            =>  $props_info['uppermatnm'],
                'widthnm'               =>  $props_info['widthnm'],
                'gendernm'              =>  $props_info['gendernm'],
                'modelnm'               =>  $props_info['modelnm'],
                'subbrand'              =>  $props_info['subbrand'],
        
            ];
        }

        return ['rsp' => 'succ', 'data' => ['list' => $data, 'count' => $count]];

    }

    /**
     * 获取WarehouseStock
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getWarehouseStock($params)
    {
        // 查询大仓库存
        $branch_id  = $params['branch_id'];
        $product_id = array_column($params['skus'], 'bm_id');

        $bprows = app::get('ome')->model('branch_product')->getList('branch_id,product_id,store,store_freeze,arrive_store', [
            'branch_id'  => $branch_id,
            'product_id' => $product_id,
        ]);

        $branchProductList = [];
        foreach ($bprows as $key => $value) {
            $branchProductList[$value['product_id']]['store'] += $value['store'];
            $branchProductList[$value['product_id']]['store_freeze'] += $value['store_freeze'];
            $branchProductList[$value['product_id']]['arrive_store'] += $value['arrive_store'];
        }

        // $branchProductList = array_column($branchProductList, null, 'product_id');

        $data = [];
        foreach ($params['skus'] as $key => $value) {
            $bp     = $branchProductList[$value['bm_id']];
            $data[] = [
                'bn'           => $value['bn'],
                'stock_status' => ($bp['store'] - $bp['store_freeze']) >= $value['num'] ? 1 : 0,
                'quantity'     => $bp['store'] - $bp['store_freeze'],
                'lock_quantity' => (int)$bp['store_freeze'],
                'arrive_quantity' => (int)$bp['arrive_store'],
            ];
        }

        return ['rsp' => 'succ', 'data' => $data];
    }


    /**
     * 获取WarehouseStockBystore
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getWarehouseStockBystore($params)
    {
        // 查询大仓库存
        $branch_id  = $params['branch_id'];

        $branchList = app::get('ome')->model('branch')->getList('branch_id,branch_bn', [
                'check_permission' => 'false',
               
                'branch_id'        => $branch_id,
        ]);

        $branchs = array_column($branchList, null,'branch_id');
      
        $product_id = array_column($params['skus'], 'bm_id');

        $bprows = app::get('ome')->model('branch_product')->getList('branch_id,product_id,store,store_freeze,arrive_store', [
            'branch_id'  => $branch_id,
            'product_id' => $product_id,
        ]);

        $branchProductList = [];
        foreach ($bprows as $key => $value) {
            $branchProductList[$value['product_id']]['branch_id'] += $value['branch_id'];
            $branchProductList[$value['product_id']]['store'] += $value['store'];
            $branchProductList[$value['product_id']]['store_freeze'] += $value['store_freeze'];
            $branchProductList[$value['product_id']]['arrive_store'] += $value['arrive_store'];
        }

  
        $data = [];
        foreach ($params['skus'] as $key => $value) {
            $bp     = $branchProductList[$value['bm_id']];

            $branch = $branchs[$bp['branch_id']];
            $data[] = [
                'branch_bn'    => $branch['branch_bn'], 
                'bn'           => $value['bn'],
                'barcode'      => $value['barcode'], 
                'stock_status' => ($bp['store'] - $bp['store_freeze']) >= $value['num'] ? 1 : 0,
                'quantity'     => $bp['store'] - $bp['store_freeze'],
                
            ];
        }

        return ['rsp' => 'succ', 'data' => $data];
    }
}
