<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_material extends erpapi_store_response_abstract
{

    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params)
    {
        $this->__apilog['title']       = $this->__channelObj->store['server_bn'] . '商品主档查询';
        $this->__apilog['original_bn'] = $this->__channelObj->store['server_bn'];

        if (!isset($params['start_time']) || !strtotime($params['start_time'])) {
            $this->__apilog['result']['msg'] = '开始时间格式不正确';

            return false;
        }

        if (!isset($params['end_time']) || !strtotime($params['end_time'])) {
            $this->__apilog['result']['msg'] = '结束时间格式不正确';

            return false;
        }

        if ($params['page_size'] <= 0 || $params['page_size'] > 100) {
            $this->__apilog['result']['msg'] = '每页数量必须大于0小于等于100';
            return false;
        }

        if ($params['page_no'] <= 0) {
            $this->__apilog['result']['msg'] = '页码必须大于0';
            return false;
        }

        $filter = [
            'last_modified|between' => [
                strtotime($params['start_time']),
                strtotime($params['end_time']),
            ],
        ];

        $limit  = $params['page_size'];
        $offset = ($params['page_no'] - 1) * $limit;

        return ['filter' => $filter, 'limit' => $limit, 'offset' => $offset];
    }

    /**
     * 查询大仓库存
     *
     * @return void
     * @author
     **/
    public function getWarehouseStock($params)
    {
        $this->__apilog['title']       = 'DMS大仓库存查询';
        $this->__apilog['original_bn'] = $params['store_bn'];

        if (!$params['store_bn']) {
            $this->__apilog['result']['msg'] = 'store_bn不能为空';

            return false;
        }

        $store = app::get('o2o')->model('store')->dump([
            'store_bn' => $params['store_bn'],
        ]);

        if (!$store) {
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }

        $skus = @json_decode($params['skus'], 1);
        if (!$skus) {
            $this->__apilog['result']['msg'] = sprintf('[%s]主档列表不能为空', $params['store_bn']);
            return false;
        }

        $bmList = app::get('material')->model('basic_material')->getList('bm_id,material_bn', [
            'material_bn' => array_column($skus, 'bn'),
        ]);
        if (!$bmList) {
            $this->__apilog['result']['msg'] = '未查到相关商品';
            return false;
        }
        $bmList = array_column($bmList, null, 'material_bn');

        foreach ($skus as $key => $value) {
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = '主档编码不能为空';
                return false;
            }

            if (!is_numeric($value['num']) || $value['num'] <= 0) {
                //$this->__apilog['result']['msg'] = sprintf('[%s]数量非法', $value['bn']);
                //return false;
            }

            if (!$bmList[$value['bn']]) {
                $this->__apilog['result']['msg'] = sprintf('[%s]主档编码不存在', $value['bn']);
                return false;
            }

            $skus[$key]['bm_id'] = $bmList[$value['bn']]['bm_id'];
        }

        if ($params['scene'] == 'sale') {
            $branchList = app::get('ome')->model('branch')->getList('branch_id', [
                'check_permission' => 'false',
                'type'             => 'main',
                'is_deliv_branch'  => 'true',
                // 'owner'            => '2',
                'b_type'           => '1',
                'branch_bn'        => ['C1','C2','CG1'],
            ]);

            if (!$branchList) {
                $this->__apilog['result']['msg'] = sprintf('[%s]未配置第三方发货仓', $params['store_bn']);
                return false;
            }
            $channel['branch_id'] = array_column($branchList, 'branch_id');

        } else {
            // 判断渠道发货仓
            $flow = app::get('o2o')->model('branch_flow')->dump([
                'to_store_bn' => $store['store_bn'],
            ]);
            if (!$flow) {
                $this->__apilog['result']['msg'] = sprintf('[%s]未匹配到发货仓', $params['store_bn']);
                return false;
            }

            $channel = app::get('o2o')->model('channel')->dump($flow['channel_id']);
            if (!$channel['branch_id']) {
                $this->__apilog['result']['msg'] = sprintf('[%s]未匹配到发货仓', $params['store_bn']);
                return false;
            }
        }

        return ['store' => $store, 'skus' => $skus, 'branch_id' => $channel['branch_id']];
    }


    /**
     * 查询大仓库存
     *
     * @return void
     * @author
     **/
    public function getWarehouseStockBystore($params)
    {
        $this->__apilog['title']       = '大仓库存查询';
        $this->__apilog['original_bn'] = $params['store_bn'];

        if (!$params['store_bn']) {
            $this->__apilog['result']['msg'] = 'store_bn不能为空';

            return false;
        }

        if(!$params['branch_bn']) {
            $this->__apilog['result']['msg'] = '大仓branch_bn不能为空';

            return false;
        }
        $store = app::get('o2o')->model('store')->dump([
            'store_bn' => $params['store_bn'],
        ]);

        if (!$store) {
            $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
            return false;
        }


        $skus = @json_decode($params['skus'], 1);
        if (!$skus) {
            $this->__apilog['result']['msg'] = sprintf('[%s]主档列表不能为空', $params['store_bn']);
            return false;
        }

        
        foreach ($skus as $key => $value) {

            if($value['barcode'] && empty($value['bn'])){
                $bn = kernel::single('material_codebase')->getBnBybarcode($value['barcode']);
                $skus[$key]['bn'] = $bn;
                $value['bn'] = $bn;
                if(empty($bn)){
                    $this->__apilog['result']['msg'] = sprintf('行明细[%s]：条码不存在', $value['barcode']);
                    return false;
                }
            }
            if (!$value['bn']) {
                $this->__apilog['result']['msg'] = '主档编码不能为空';
                return false;
            }
            
        }

        $bmList = app::get('material')->model('basic_material')->getList('bm_id,material_bn', [
            'material_bn' => array_column($skus, 'bn'),
        ]);

        if (!$bmList) {
            $this->__apilog['result']['msg'] = '未查到相关商品';
            return false;
        }
        $bmList = array_column($bmList, null, 'material_bn');


        $syncproductMdl = app::get('o2o')->model('syncproduct');

        $syncproductList = $syncproductMdl->getList('bm_id,material_bn', [
            'material_bn' => array_column($skus, 'bn'),
        ]);

        $syncproductList = array_column($syncproductList, null, 'material_bn');
        foreach ((array)$skus as $key => $value) {

            $bm_id = $bmList[$value['bn']]['bm_id'];

            if (!$bm_id) {
                $this->__apilog['result']['msg'] = sprintf('行明细物料[%s]：未维护', $value['bn']);

                return false;
            }

            //
            if(!isset($syncproductList[$value['bn']]['bm_id'])){

                $this->__apilog['result']['msg'] = sprintf('行明细物料[%s]：不在门店可销售范围内,请联系商品同事维护', $value['bn']);

                return false;
            }
            
            $skus[$key]['bm_id'] = $bm_id;
        }

        $branchList = app::get('ome')->model('branch')->getList('branch_id', [
                'check_permission' => 'false',
                'type'             => 'main',
                'is_deliv_branch'  => 'true',
                // 'owner'            => '2',
                'b_type'           => '1',
                'branch_bn'        => $params['branch_bn'],
        ]);

        if (!$branchList) {
            $this->__apilog['result']['msg'] = sprintf('[%s]:仓库不存在', $params['branch_bn']);
            return false;
        }
        $channel['branch_id'] = array_column($branchList, 'branch_id');

        return ['store' => $store, 'skus' => $skus, 'branch_id' => $channel['branch_id']];
    }
}
