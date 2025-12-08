<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_warehouse{
    
    /**
     * 添加
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function add($data){
        $result = array('rsp'=>'succ');
        
        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $branch_mdl = app::get('ome')->model('branch');
        $mdl_ome_extrabranch = app::get('ome')->model('extrabranch');
        $barcodeLib = kernel::single('material_basic_material_barcode');
        
        //来源地仓库名称必填
        $extrabranch = $mdl_ome_extrabranch->dump(array("name"=>$data['extrabranch_name']),"branch_id");
        if(empty($extrabranch)){
            $result['rsp'] = 'fail';
            $result['msg'] = '来源地仓库不存在';
            return $result;
        }
        
        //仓库编号必填
        $branch = $branch_mdl->dump(array('branch_bn'=>$data['branch_bn']),'branch_id');
        if(empty($branch)){
            $result['rsp'] = 'fail';
            $result['msg'] = '仓库不存在';
            return $result;
        }
        
        $items = $data['items'];
        if(count($items)<=0 || !$items){
            $result['rsp'] = 'fail';
            $result['msg'] = '缺少出入库商品';
            return $result;
        }
        
        //主数据
        $sdf['iostockorder_name'] = $data['name'];
        $type = array('DC'=>'1000');
        $sdf['type_id'] = $type[$data['type']];
        $sdf['branch'] = $branch['branch_id'];
        $sdf["extrabranch_id"] = $extrabranch["branch_id"];
        //供应商非必填
        $sdf['supplier_id'] = "";
        $sdf['supplier'] = "";
        if($data['vendor']){
            $supplier_mdl = app::get('purchase')->model('supplier');
            $supplier = $supplier_mdl->dump(array('name'=>$data['vendor']), 'supplier_id');
            if(!empty($supplier)){
                $sdf['supplier_id'] = $supplier['supplier_id'];
                $sdf['supplier'] = $data['vendor'];
            }
        }
        $sdf['iso_price'] = $data['delivery_cost'] ? $data['delivery_cost'] : 0;
        $sdf['operator'] = $data['operator'];
        $sdf['memo'] = $data['memo'];
        $sdf['original_iso_bn'] = $data['original_iso_bn'];
        
        
        //返回所有不存在的EAN条形码
        $noBns = array();
        $noNums = array();
        $itemList = array();
        foreach($items as $v){
            
            //中间件转仓单传过来的是barcode条形码
            $bm_id = $barcodeLib->getIdByBarcode($v['bn']);
            if(empty($bm_id)){
                $noBns[] = $v['bn'];
                continue;
            }
            
            //基础物料信息
            $product = $basicMaterialObj->dump(array('bm_id'=>$bm_id),'bm_id,material_name,material_bn');
            if (empty($product)) {
                $noBns[] = $v['bn'];
                continue;
            }
            
            //检查数量
            if($v['nums'] == 0){
                $noNums[] = $v['bn'];
                continue;
            }
            
            //扩展信息
            $basicMExtInfo = $basicMaterialExtObj->dump(array('bm_id'=>$product['bm_id']),'unit,retail_price');
            $product['unit'] = $basicMExtInfo['unit'];
            
            if(!$v['price']){
                $v['price'] = $basicMExtInfo['retail_price'];
            }
            
            if(!$v['name']){
                $v['name'] = $product['material_name'];
            }
            
            $v = array_merge($v, $product);
            
            $itemList[] = $v;
        }
        
        unset($items, $product);
        
        //error_msg
        if($noBns || $noNums){
            
            $error_msg = '';
            if($noBns){
                $error_msg .= sprintf('条形码[%s]不存在;', implode('、', $noBns));
            }
            
            if($noNums){
                $error_msg .= sprintf('条形码[%s]库存数量不能为0;', implode('、', $noNums));
            }
            
            $result['rsp'] = 'fail';
            $result['msg'] = $error_msg;
            return $result;
        }
        
        
        //明细数据
        foreach($itemList as $v){
            $bm_id = $v['bm_id'];
            
            if($v["po_name"] && $v["dly_note_number"]){ //获取pbook数据的时候 会有bn po_name dly_note_number 为一个维度
                $products[$bm_id][] = array(
                    'bn' => $v['material_bn'],
                    'nums'=>$v['nums'],
                    'unit'=>$v['unit'],
                    'name'=>$v['name'],
                    'price'=>$v['price'],
                    'po_name'=>$v["po_name"],
                    'dly_note_number'=>$v["dly_note_number"],
                    'box_number'=>str_replace("|",",",$v["box_number"]),
                );
            }else{
                $products[$bm_id] = array(
                    'bn' => $v['material_bn'],
                    'nums'=>$v['nums'],
                    'unit'=>$v['unit'],
                    'name'=>$v['name'],
                    'price'=>$v['price'],
                );
            }
        }
        $sdf['products'] = $products;
        
        $msg = '';
        $rs = kernel::single('console_iostockorder')->save_warehouse_iostockorder($sdf,$msg);
        if($rs){
            $result['data'] = kernel::single('console_iostockorder')->getIoStockOrderBn();
        }else{
            $result['rsp'] = 'fail';
            $result['msg'] = $msg ? $msg : "添加转仓单失败";
        }
        
        return $result;
    }
    
    /**
     * 获取List
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($start_time,$end_time,$offset=0,$limit=100){
        if(empty($start_time) || empty($end_time)){
            return false;
        }
        
        $codebaseObj    = app::get('material')->model('codebase');
        $codeBaseLib    = kernel::single('material_codebase');
        $codType = $codeBaseLib->getBarcodeType(); //获取条码的类型值,默认1
        
        $mdl_wi = app::get('warehouse')->model('iso');
        $mdl_wii = app::get('warehouse')->model('iso_items_simple');
        $filter = array(
            'iso_status' => '3',
            "complete_time|bthan" => $start_time,
            "complete_time|sthan" => $end_time,
        );
        $current_count = $mdl_wi->count($filter);
        if($current_count > 0){
            $lists = $mdl_wi->getList("*",$filter,$offset,$limit,"complete_time asc");
            $dataList = array();
            $iso_ids = array();
            
            //主信息
            foreach ($lists as $key => $val){
                $iso_id = $val['iso_id'];
                $iso_ids[] = $iso_id;
                
                $dataList[$iso_id] = $val;
            }
            
            //明细列表
            $itemList = $mdl_wii->getList("*", array("iso_id"=>$iso_ids));
            foreach ($itemList as $key => $val){
                $iso_id = $val['iso_id'];
                $bm_id = $val['product_id'];
                
                $code_info = $codebaseObj->dump(array('bm_id'=>$bm_id, 'type'=>$codType), 'code');
                $val['barcode'] = $code_info['code'];
                
                $dataList[$iso_id]['items'][] = $val;
            }
            
            unset($lists, $itemList, $iso_ids);
            return array(
                'lists' => $dataList,
                'count' => $current_count,
            );
        }else{
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
    }
    
}