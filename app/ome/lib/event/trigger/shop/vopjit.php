<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_shop_vopjit {

    // 目前唯品会仅7大仓+集配仓会产生退供,oms无集配仓
    public static $returnBranchs = ['VIP_NH','VIP_SH','VIP_CD','VIP_BJ','VIP_HZ','VIP_SY','VIP_XA','VFN1700001','VFN1700002','VFN1700003','VFN1700004','VFN1700005','VFN1700006','VFN1700007','VFN1700008','VFN1700009','VFN17000010','VFN17000011','VFN17000012','VFN17000013','VFN17000014'];

    public function getReturnInfo($data) {
        $shop = app::get('ome')->model('shop')->getList('shop_id, name', ['node_type'=>'vop']);

        $branch_bns = $this::$returnBranchs;
       
        if(empty($shop) ) {
            return;
        }

        $returnSnList = array();
        foreach ($shop as $v) {
            foreach($branch_bns as $vv) {
                $pageNo = 1;
                do {
                    $sdf = ['start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'warehouse'=>$vv, 'page_no'=>$pageNo, 'page_size'=>'50'];

        
                    $result = kernel::single('erpapi_router_request')->set('shop', $v['shop_id'])->purchase_getReturnInfo($sdf);
                    

                    if (empty($result['data'])) {
                        break;
                    }
                    $pageNo ++;
                    foreach ($result['data'] as $main) {
                        list($resDetail, $errorMsg) = $this->getReturnDetail($main, $v['shop_id']);

                        if ($resDetail == true) {
                            $returnSnList[] = $main['return_sn'];
                        }
                    }
                } while(true);
            }
        }

        // 检测如果有明细，则自动审核
        if (!$returnSnList) return;

        foreach ($returnSnList as $return_sn) {
            //list($resCheck, $errorMsg) = $this->autoDoCheck($return_sn);
        }

    }


    public function getReturnDetail($main, $shopId) {
        if(empty($main) || empty($main['return_sn']) || empty($shopId)) {
            return [false, '数据不全'];
        }
        $shopInfo = app::get('ome')->model('shop')->db_dump(['shop_id'=>$shopId],'shop_id, name,shop_type,node_type');
        $mainObj = app::get('console')->model('vopreturn');
        $r = $mainObj->db_dump(['return_sn'=>$main['return_sn']], 'id');
        $logObj = app::get('ome')->model('operation_log');
        if(!$r) {
            $main['posted_time'] = strtotime($main['posted_time']);
            $main['out_time']    = strtotime($main['out_time']);
            
            $main['shop_id'] = $shopId;
            $main['shop_type'] = $shopInfo['shop_type'];
            $main['create_time'] = time();
            app::get('console')->model('vopreturn')->insert($main);
            if(!$main['id']) {
                return [false, $main['return_sn'].'主表保存失败'];
            }
            $mainId = $main['id'];
            $logObj->write_log('vopreturn@console',$mainId,'主表写入成功');
        } else {
            $mainId = $r['id'];
        }
        $itemObj = app::get('console')->model('vopreturn_items');
        if($itemObj->db_dump(['return_id'=>$mainId],'id')) {
            return [true, $main['return_sn'].'已存在明细'];
        }
        $sdf = [
            'warehouse' => $main['warehouse'],
            'return_sn' => $main['return_sn'],
        ];
        $result = kernel::single('erpapi_router_request')->set('shop', $shopId)->purchase_getReturnDetail($sdf);
        if($result['rsp'] != 'succ' || empty($result['data'])) {
            $logObj->write_log('vopreturn@console',$mainId,'获取详情失败,'.$result['msg']);
            return [false, $main['return_sn'].':获取详情失败,'.$result['msg']];
        }
        kernel::database()->beginTransaction();
        $upData = [
            'total_cases' => $result['data']['total_cases'],
            'total_skus' => $result['data']['total_skus'],
            'total_qtys' => $result['data']['total_qtys'],
            'posted_time' => strtotime($result['data']['posted_time']),
            'out_time' => strtotime($result['data']['out_time']),
        ];
        $mainObj->update($upData, ['id'=>$mainId]);
        if($itemObj->db_dump(['return_id'=>$mainId],'id')) {
            kernel::database()->rollBack();
            return [true, $main['return_sn'].'已存在明细'];
        }
        
        $newSkuPriceList = $codebaseList = $materialList = [];
        $purchaseSkuPriceMdl = app::get('purchase')->model('order_sku_price');
        //商品条形码查基础物料编码
        if (isset($result['data']['delivery_list']) && is_array($result['data']['delivery_list']) && $result['data']['delivery_list']) {
            $codType      = kernel::single('material_codebase')->getBarcodeType();
            $codes        = array_column($result['data']['delivery_list'], 'barcode');
            $codebaseList = app::get('material')->model('codebase')->getList('bm_id,code', ['code' => $codes, 'type' => $codType]);
            $codebaseList = array_column($codebaseList, null, 'code');
            $bmIds        = array_column($codebaseList, 'bm_id');
            $materialList = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', ['bm_id' => $bmIds]);
            $materialList = array_column($materialList, null, 'bm_id');
        
            //获取供货价-含税结算价
            $poNos               = array_column($result['data']['delivery_list'], 'po_no');
            $skuPriceList        = $purchaseSkuPriceMdl->getList('id,po_bn,barcode,actual_market_price,price', ['po_bn' => $poNos, 'barcode' => $codes]);
            foreach ($skuPriceList as $sku_price) {
                $key                   = $sku_price['po_bn'] . '_' . $sku_price['barcode'];
                $newSkuPriceList[$key]['actual_market_price'] = $sku_price['actual_market_price'];
                $newSkuPriceList[$key]['price']               = $sku_price['price'];//原价
            }
        }
        
        $insertData = [];
        
        foreach($result['data']['delivery_list'] as $v) {
            $v['return_id'] = $mainId;
            $bm_id              = $codebaseList[$v['barcode']]['bm_id'] ?? 0;
            $v['bm_id']         = $bm_id;
            $v['material_bn']   = $materialList[$bm_id]['material_bn'] ?? '';
            $v['product_name']  = $v['product_name'] ? $v['product_name'] : ($materialList[$bm_id]['material_name'] ?? '');
    
            $price = $newSkuPriceList[$v['po_no'].'_'.$v['barcode']]['price'] ?? 0;//含税结算价
            $poNoSku = $v['po_no'];
            if ($price == 0 && isset($v['origin_po_no']) && $v['origin_po_no']) {
                $poNoSku      = $v['origin_po_no'];
                $skuPriceInfo = $purchaseSkuPriceMdl->db_dump(['po_bn' => $poNoSku, 'barcode' => $v['barcode']], 'id,po_bn,barcode,actual_market_price,price');
                $price        = $skuPriceInfo ? $skuPriceInfo['price'] : 0;
            }
            if($price == 0){
                list($skuPriceRs,,$skuPriceData) = kernel::single('purchase_purchase_sku')->getSkuPriceInfo($shopId, $poNoSku,[$v['barcode']]);
                if ($skuPriceRs) {
                    $skuPriceData = array_column($skuPriceData, null, 'barcode');
                    $price = $skuPriceData[$v['barcode']]['price'] ?? 0;
                }
            }
    
            $v['price']         = $price;//含税结算价
            //原采购单号
            if(isset($v['origin_po_no']) && $v['origin_po_no']){
                $v['originsaleordid'] = $v['origin_po_no'];
            }
            //原退供单号
            if(isset($v['origin_return_no']) && $v['origin_return_no']){
                $v['refundid'] = $v['origin_return_no'];
            }
            $insertData[] = $v;
        }
        if ($insertData) {
            $sql = kernel::single('ome_func')->get_insert_sql($itemObj, $insertData);
            $itemObj->db->exec($sql);
            $logObj->write_log('vopreturn@console',$mainId,'明细写入成功');
        }
        kernel::database()->commit();
        return [true];
    }


    public function autoDoCheck($return_sn)
    {
        $mainObj = app::get('console')->model('vopreturn');
        $itemObj = app::get('console')->model('vopreturn_items');

        $info = $mainObj->db_dump(array('return_sn'=>$return_sn));
        if (!$info) {
            return [false, sprintf('[%s]退供单不存在', $return_sn)];
        }

        if ($info['status']<>'0') {
            return [false, sprintf('[%s]退供单不允许审核', $return_sn)];
        }
        
      

        list($rs, $msg) = kernel::single('console_vopreturn')->doCheck($info['id'], $info['in_branch_id']);
        if(!$rs) {
            return [false, sprintf('[%s]审核失败：%s', $return_sn, $msg)];
        }

    }

}
