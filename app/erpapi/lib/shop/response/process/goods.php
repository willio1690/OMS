<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_response_process_goods {

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params)
    {
        if (!$params['item']['jdp_delete']) {
            return kernel::single('inventorydepth_event_receive_goods')->add($params);
        } else {
            return kernel::single('inventorydepth_event_receive_goods')->delete($params);
        }
    }
    
    /**
     * 删除
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function delete($params)
    {
        return kernel::single('inventorydepth_event_receive_goods')->delete($params);
    }

    /**
     * sku_delete
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function sku_delete($params)
    {
        $skuMdl  = app::get('inventorydepth')->model('shop_skus');

        $skuMdl->delete(array('shop_iid'=>$params['iid'],'shop_sku_id'=>$params['sku_id'],'shop_id'=>$params['shop']['shop_id']));

        return array('rsp'=>'succ','msg' => '删除成功');
    }
    
    /**
     * [翱象系统]货品新建&更新结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_update($params)
    {
        $axProductMdl = app::get('dchain')->model('aoxiang_product');
        
        $shop_id = $params['shop_id'];
        $dataList = $params['items'];
        $msg = '';
        
        //check
        if(empty($shop_id)){
            return array('rsp'=>'fail', 'msg'=>'更新失败：没有获取到店铺', 'result'=>0, 'data'=>$dataList);
        }
        
        if(empty($dataList)){
            return array('rsp'=>'fail', 'msg'=>'更新失败：没有可更新的普通商品', 'result'=>0, 'data'=>$dataList);
        }
        
        //list
        $succList = array();
        $failList = array();
        $result_error_msg = '';
        foreach ($dataList as $key => $val)
        {
            $product_bn = $val['scItemId'];
            
            if($val['success']){
                $succList[] = $product_bn;
            }else{
                $failList[] = $product_bn;
                
                $result_error_msg = ($val['bizMessage'] ? $val['bizMessage'] : '更新商品失败');
            }
        }
        
        //succ
        if($succList){
            //update
            $updateData = array('sync_status'=>'succ', 'last_modified'=>time());
            $axProductMdl->update($updateData, array('shop_id'=>$shop_id, 'product_bn'=>$succList));
        }
        
        //error
        if($failList){
            //error_msg
            $result_error_msg = stripslashes($result_error_msg);
            $result_error_msg = str_replace(array('"', "'", '/'), '', $result_error_msg);
            
            //update
            $updateData = array('sync_status'=>'fail', 'last_modified'=>time(), 'sync_msg'=>$result_error_msg);
            $axProductMdl->update($updateData, array('shop_id'=>$shop_id, 'product_bn'=>$failList));
        }
        
        //unset
        unset($dataList, $succList, $failList);
        
        //result
        return array('rsp'=>'succ', 'msg'=>'更新普通商品成功', 'result'=>1, 'data'=>array());
    }
    
    /**
     * [翱象系统]组合货品新建&更新结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_combine_update($params)
    {
        $axProductMdl = app::get('dchain')->model('aoxiang_product');
        
        $shop_id = $params['shop_id'];
        $dataList = $params['items'];
        $msg = '';
        
        //check
        if(empty($shop_id)){
            return array('rsp'=>'fail', 'msg'=>'更新失败：没有获取到店铺', 'result'=>0, 'data'=>$dataList);
        }
        
        if(empty($dataList)){
            return array('rsp'=>'fail', 'msg'=>'更新失败：没有可更新的组合商品', 'result'=>0, 'data'=>$dataList);
        }
        
        //list
        $succList = array();
        $failList = array();
        $result_error_msg = '';
        foreach ($dataList as $key => $val)
        {
            $product_bn = ($val['combineScItemId'] ? $val['combineScItemId'] : $val['scItemId']);
            
            if($val['success']){
                $succList[] = $product_bn;
            }else{
                $failList[] = $product_bn;
                
                $result_error_msg = ($val['bizMessage'] ? $val['bizMessage'] : '更新组合商品失败');
            }
        }
        
        //succ
        if($succList){
            //update
            $updateData = array('sync_status'=>'succ', 'last_modified'=>time(), 'sync_msg'=>'');
            $axProductMdl->update($updateData, array('shop_id'=>$shop_id, 'product_bn'=>$succList));
        }
        
        //error
        if($failList){
            //error_msg
            $result_error_msg = stripslashes($result_error_msg);
            $result_error_msg = str_replace(array('"', "'", '/'), '', $result_error_msg);
            
            //update
            $updateData = array('sync_status'=>'fail', 'last_modified'=>time(), 'sync_msg'=>$result_error_msg);
            $axProductMdl->update($updateData, array('shop_id'=>$shop_id, 'product_bn'=>$failList));
        }
        
        //unset
        unset($dataList, $succList, $failList);
        
        //result
        return array('rsp'=>'succ', 'msg'=>'更新组合商品成功', 'result'=>1, 'data'=>array());
    }
    
    /**
     * [翱象系统]货品删除结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_delete($params)
    {
        $axProductMdl = app::get('dchain')->model('aoxiang_product');
        
        $shop_id = $params['shop_id'];
        $product_bn = $params['product_bn'];
        $msg = '';
        
        //check
        if($params['rsp'] == 'succ'){
            //delete
            $axProductMdl->delete(array('shop_id'=>$shop_id, 'product_bn'=>$product_bn));
            
            $msg = '删除成功'. ($params['err_msg'] ? '('. $params['err_msg'] .')' : '');
        }else{
            $msg = '删除失败'. ($params['err_msg'] ? '('. $params['err_msg'] .')' : '');
        }
        
        return array('rsp'=>$params['rsp'], 'msg'=>$msg);
    }
    
    /**
     * [翱象系统]商货品关联关系结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_mapping($params)
    {
        $axProductMdl = app::get('dchain')->model('aoxiang_product');
        $axSkuMdl = app::get('dchain')->model('aoxiang_skus');
        
        $shop_id = $params['shop_id'];
        $dataList = $params['items'];
        $msg = '';
        
        //check
        if(empty($shop_id)){
            return array('rsp'=>'fail', 'msg'=>'更新商品关系失败：没有获取到店铺', 'result'=>0, 'data'=>$dataList);
        }
        
        if(empty($dataList)){
            return array('rsp'=>'fail', 'msg'=>'更新商品关系失败：没有可更新的组合商品', 'result'=>0, 'data'=>$dataList);
        }
        
        //list
        $succList = array();
        $failList = array();
        foreach ($dataList as $key => $val)
        {
            if($val['success']){
                $succList[] = array(
                    'product_bn' => $val['scItemId'], //product_bn
                    'shop_iid' => $val['itemId'], //shop_iid
                    'shop_sku_id' => $val['skuId'], //shop_sku_id
                );
            }else{
                //error_msg
                $error_msg = ($val['bizMessage'] ? $val['bizMessage'] : $val['bizCode']);
                $error_msg = stripslashes($error_msg);
                $error_msg = str_replace(array('"', "'", '/'), '', $error_msg);
                
                $failList[] = array(
                    'product_bn' => $val['scItemId'], //product_bn
                    'shop_iid' => $val['itemId'], //shop_iid
                    'shop_sku_id' => $val['skuId'], //shop_sku_id
                    'error_msg' => $error_msg, //error_msg
                );
            }
        }
        
        //succ
        if($succList){
            $updateSku = array('mapping_status'=>'succ', 'mapping_time'=>time(), 'sync_msg'=>'');
            $product_bns = array();
            foreach ($succList as $key => $val)
            {
                //update sku
                $filter = array('shop_id'=>$shop_id, 'product_bn'=>$val['product_bn'], 'shop_iid'=>$val['shop_iid']);
                if($val['shop_sku_id']){
                    $filter['shop_sku_id'] = $val['shop_sku_id'];
                }
                $axSkuMdl->update($updateSku, $filter);
                
                //product_bn
                $product_bns[] = $val['product_bn'];
            }
            
            //update mapping_status
            $updateProdcut = array('mapping_status'=>'succ', 'mapping_time'=>time(), 'sync_msg'=>'');
            $axProductMdl->update($updateProdcut, array('shop_id'=>$shop_id, 'product_bn'=>$product_bns));
        }
        
        //error
        if($failList){
            $updateSku = array('mapping_status'=>'fail', 'last_modified'=>time());
            $product_bns = array();
            $error_msg = '';
            foreach ($failList as $key => $val)
            {
                $error_msg = $val['error_msg'];
                
                //sdf
                $updateSku['sync_msg'] = $error_msg;
                
                //update sku
                $filter = array('shop_id'=>$shop_id, 'product_bn'=>$val['product_bn'], 'shop_iid'=>$val['shop_iid']);
                if($val['shop_sku_id']){
                    $filter['shop_sku_id'] = $val['shop_sku_id'];
                }
                $axSkuMdl->update($updateSku, $filter);
                
                //product_bn
                $product_bns[] = $val['product_bn'];
            }
            
            //update mapping_status
            $updateProdcut = array('mapping_status'=>'fail', 'last_modified'=>time(), 'sync_msg'=>$error_msg);
            $axProductMdl->update($updateProdcut, array('shop_id'=>$shop_id, 'product_bn'=>$product_bns));
        }
        
        //unset
        unset($dataList, $succList, $failList);
        
        //result
        return array('rsp'=>'succ', 'msg'=>'更新商品关系成功', 'result'=>1, 'data'=>array());
    }
}
