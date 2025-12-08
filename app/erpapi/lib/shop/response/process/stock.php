<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_response_process_stock {
    /**
     * 获取
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get($params){
        $filter = ['sales_material_bn'=>$params['bn'], 'sales_material_type'=>'1'];
        $salesMaterialObj = app::get('material')->model('sales_material');
        $products = $salesMaterialObj->getList('sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id',$filter);
        if(empty($products)) {
            return array('rsp'=>'fail', 'msg' => '系统中没有这些货号');
        }
        $options = [
            'need_write' => true,
            'branch_id' => $params['branch_id'],
            'select_stock' => true,
        ];
        list($rs, $msg) = kernel::single('inventorydepth_offline_queue')->store_update($products, $params['shop'], $options);
        if(!$rs || !is_array($msg)) {
            return array('rsp'=>'fail', 'msg' => '库存查询失败：'.$msg);
        }
        $store = array_column($msg, null, 'bn');
        $data = [];
        foreach ($params['bn'] as $v) {
            if(empty($store[$v])) {
                $data[] = [
                    'bn' => $v,
                    'detail' => '系统中未查到该货号的库存'
                ];
                continue;
            }
            $data[] = [
                'bn' => $v,
                'quantity' => $store[$v]['quantity'],
                'detail' => $store[$v]['regulation']
            ];
        }
        return array('rsp'=>'succ', 'msg' => '获取成功', 'data'=>$data);
    }
    
    /**
     * [翱象系统]实仓库存查询接口
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_query($params)
    {
        $aoBranchMdl = app::get('dchain')->model('aoxiang_branch');
        $axProductMdl = app::get('dchain')->model('aoxiang_product');
        
        $branch_bn = $params['branch_bn'];
        $product_bn = $params['product_bn'];
        $shopInfo = $params['shopInfo'];
        $shop_id = $shopInfo['shop_id'];
        $shop_bn = $shopInfo['shop_bn'];
        
        $result = array('rsp'=>'fail', 'msg'=>'', 'error_msg'=>'', 'msg_code'=>'');
        
        //branch
        $branchInfo = $aoBranchMdl->dump(array('branch_bn'=>$branch_bn, 'shop_id'=>$shop_id, 'sync_status'=>'succ'), '*');
        if(empty($branchInfo)){
            $error_msg = '仓库编码：'. $branch_bn .'没有分配翱象';
            
            $result['msg'] = $error_msg;
            $result['error_msg'] = $error_msg;
            $result['msg_code'] = '204';
            
            return $result;
        }
        
        $branch_id = $branchInfo['branch_id'];
        
        //product
        if($product_bn){
            $axProductList = $axProductMdl->getList('pid,product_id,product_bn,product_type', array('shop_id'=>$shop_id, 'product_bn'=>$product_bn, 'sync_status'=>'succ'));
            if(empty($axProductList)){
                $error_msg = '商品编码：'. $product_bn .'没有同步给翱象,不可以查询库存';
                
                $result['msg'] = $error_msg;
                $result['error_msg'] = $error_msg;
                $result['msg_code'] = '204';
                
                return $result;
            }
            
            $axProductList = array_column($axProductList, null, 'product_bn');
        }else{
            $axProductList = $axProductMdl->getList('pid,product_id,product_bn,product_type', array('shop_id'=>$shop_id, 'sync_status'=>'succ'), 0, 100);
            if(empty($axProductList)){
                $error_msg = '没有可查询的商品列表';
                
                $result['msg'] = $error_msg;
                $result['error_msg'] = $error_msg;
                $result['msg_code'] = '204';
                
                return $result;
            }
            
            $axProductList = array_column($axProductList, null, 'product_bn');
        }
        
        //查询库存
        $aoxiangLib = kernel::single('dchain_aoxiang');
        $physicsInventory = array();
        foreach ($axProductList as $key => $val)
        {
            $product_bn = $val['product_bn'];
            
            //stock
            $resultStock = $aoxiangLib->getProductBranchStock($product_bn, $shop_bn, $shop_id, $branch_bn);
            $totalStock = intval($resultStock['branch_store']);
            $actualStock = intval($resultStock['actual_stock']);
            
            //data
            $physicsInventory[] = array(
                'totalQuantity' => $totalStock, //仓库总库存
                'erpWarehouseCode' => $branch_bn,
                'scItemId' => $product_bn,
                'avaliableQuantity' => $actualStock, //仓库可用库存
            );
        }
        
        //check
        if(empty($physicsInventory)){
            $error_msg = '没有查询到库存结果';
            
            $result['msg'] = $error_msg;
            $result['error_msg'] = $error_msg;
            $result['msg_code'] = '207';
            
            return $result;
        }
        
        $result = array(
            'rsp' => 'succ',
            'msg' => '请求成功',
            'scItemId' => $physicsInventory[0]['scItemId'],
            'erpWarehouseCode' => $physicsInventory[0]['erpWarehouseCode'],
            'totalQuantity' => $physicsInventory[0]['totalQuantity'],
            'avaliableQuantity' => $physicsInventory[0]['avaliableQuantity'],
        );
        
        return $result;
    }

    /**
     * occupy
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function occupy($params) {
        $rsp = array('rsp'=>'succ', 'msg'=>'处理成功');
        $status = $params['status'];
        
        $occupyLib = kernel::single('console_stock_occupy');
        if($status == '100'){
            list($rs,$msg) = $occupyLib->add($params);
        }else if($status == '900'){
            list($rs,$msg) = $occupyLib->deleteOrderOccupy($params['order_bn']);
        }
        
        if(!$rs){
            $rsp['rsp']='fail';
            $rsp['msg']=$msg;
        }
        return $rsp;
    }
}
