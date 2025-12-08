<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_shop_vopbill {

    public function getBillNumber($startTime, $endTime, $shopId) {
        $pageNo = 1;
        $model = app::get('console')->model('vopbill');
        do {
            $sdf = ['start_time' => $startTime, 'end_time' => $endTime, 'page_no'=>$pageNo, 'page_size'=>'50'];
          
            $result = kernel::single('erpapi_router_request')->set('shop', $shopId)->finance_getBillNumber($sdf);

            if (empty($result['data'])) {
                break;
            }
            foreach ($result['data'] as $v) {
                if(!$model->db_dump(['bill_number'=>$v])) {
                    $data = ['bill_number'=>$v, 'shop_id'=>$shopId, 'get_time'=>$endTime, 'create_time'=>time()];
                    $model->insert($data);
                }
            }
            $pageNo ++;
        } while(true);
    }

    public function getBillDetail($row) {
        if(empty($row['id']) || empty($row['bill_number']) || empty($row['shop_id'])) {
            return [false, '数据不全'];
        }
        $mainObj = app::get('console')->model('vopbill');
        $filter = [
            'status' => '0',
            'sync_status' => '0',
            'id' => $row['id']
        ];
        $rs = $mainObj->update(['sync_status'=>'1', 'last_modified'=>time()], $filter);
        if(is_bool($rs)) {
            return [false, '并发了'];
        } 
        $pageNo = 1;
        $start_time = $row['get_time'] - 90*86400;
        $itemObj = app::get('console')->model('vopbill_items');
        $amountObj = app::get('console')->model('vopbill_amount');
        $basicObj = kernel::single('material_basic_material');
        do {
            $sdf = ['start_time' => $start_time, 'end_time' => $row['get_time'], 'bill_number'=>$row['bill_number'], 'page_no'=>$pageNo, 'page_size'=>'100'];
            $result = kernel::single('erpapi_router_request')->set('shop', $row['shop_id'])->finance_getBillDetail($sdf);

            if($result['rsp'] == 'fail'){
                $mainObj->update(['sync_status'=>'0'], ['id'=>$row['id']]);
            }
            if (empty($result['data']['items'])) {
                if ($result['data']['count'] === 0){
                    $mainObj->update(['sync_status'=>'2'], ['id'=>$row['id']]);
                }

                break;
            }
            if($result['data']['count']) {
                $mainObj->update(['sku_count'=>$result['data']['count']], ['id'=>$row['id']]);
            }
            foreach ($result['data']['items'] as $v) {
                if(!$itemObj->db_dump(['origin_id'=>$v['id']])) {
                    kernel::database()->beginTransaction();
                    $detailLineType = $v['detailLineType'] == 'DR_CUST_EXTRA' ? 'DR_CUST' : $v['detailLineType'];
                    if($v['detailLineType'] == 'OTHER') {
                        if($v['dataSign'] > 0) {
                            $detailLineType = 'CR_CUST';
                        } else {
                            $detailLineType = 'DR_CUST';
                            
                        }
                    }
                    $insertItem = [
                        'bill_id' => $row['id'],
                        'origin_id' => $v['id'],
                        'barcode' => $v['itemNo'],
                        'product_name' => $v['itemDescription'],
                        'detail_line_type' => $detailLineType,
                        'detail_line_name' => $v['detailLineName'],
                        'shop_id' => $row['shop_id'],
                        'qty' => $v['quantity'],
                        'price' => $v['billTaxPrice'],
                        'totalbillamount'=>$v['totalBillAmount'],
                        'datasign'       => $v['dataSign'],
                        'addon' => json_encode($v),
                    ];
                    $itemObj->insert($insertItem);
                    if(!$insertItem['id']) {
                        kernel::database()->rollBack();
                        continue;
                    }
                    $updateBillSql = 'update sdb_console_vopbill set get_count=get_count+1 ';
                    if($v['detailLineType'] == 'OTHER') {

                        $updateBillSql .= ' ,other_amount=other_amount+'.($insertItem['datasign'] * $insertItem['totalbillamount']).',other_quantity=other_quantity+'.($v['quantity']*$insertItem['datasign']);
                    }elseif($insertItem['detail_line_type'] == 'CR_CUST') {
                        $updateBillSql .= ' ,cr_cust_amount=cr_cust_amount+'.$insertItem['totalbillamount'].',cr_cust_quantity=cr_cust_quantity+'.$insertItem['qty'];
                    }elseif($insertItem['detail_line_type'] == 'DR_CUST') {
                        $updateBillSql .= ' ,dr_cust_amount=dr_cust_amount+'.$insertItem['datasign']*$insertItem['totalbillamount'].',dr_cust_quantity=dr_cust_quantity+'.$insertItem['qty'];
                    }
                    $updateBillSql .= ' where id='.$row['id'];
                    $mainObj->db->exec($updateBillSql);
                    $amount = $insertItem['totalbillamount']*$insertItem['datasign'];
                    $insertAmount = [
                        'bill_id'           =>  $row['id'],
                        'bm_id'             =>  '0',
                        'bn'                =>  '0',
                        'bill_number'       =>  $row['bill_number'],
                        'barcode'           =>  $insertItem['barcode'],
                        'product_name'      =>  $insertItem['product_name'],
                        'detail_line_type'  =>  $insertItem['detail_line_type'],
                        'detail_line_name'  =>  $insertItem['detail_line_name'],
                        'shop_id'           =>  $insertItem['shop_id'],
                        'qty'               =>  $insertItem['qty']*$insertItem['datasign'],
                        'amount'            =>  $amount,
                    ];
                    $amountFilter = [
                        'detail_line_type'  => $insertAmount['detail_line_type'],
                        'bill_id'           => $insertAmount['bill_id'],
                        'barcode'           => $insertAmount['barcode'],
                    ];
                    $oldAmount = $amountObj->db_dump($amountFilter, 'id');;
                    if($oldAmount) {
                        $sql = 'update sdb_console_vopbill_amount 
                            set qty=qty+'.$insertAmount['qty'].',amount=amount+'.$insertAmount['amount'].' where id='.$oldAmount['id'];
                        $amountObj->db->exec($sql);
                        $insertAmount['id'] = $oldAmount['id'];
                    } else {
                       
                        
                        $amountObj->insert($insertAmount);
                        
                    }

                    $itemObj->update(['bill_amount_id'=>$insertAmount['id']], ['id'=>$insertItem['id']]);
                   
                    kernel::database()->commit();
                }
            }

            $nbRow = $mainObj->db_dump(['id'=>$row['id']], 'sku_count,get_count');
            if($nbRow['get_count'] >= $nbRow['sku_count']) {
                $mainObj->db->exec("UPDATE sdb_console_vopbill_amount SET total_amount=(amount+discount_amount) WHERE bill_id=".$row['id']."");
                //做sum更新
                $mainObj->update(['sync_status'=>'2'], ['id'=>$row['id']]);
                break;
            }
            $pageNo ++;
        } while(true);
        return [true];
    }


    /**
     * dataSign*totalBillAmount
     */

    public function getBillDiscountDetail($row) {

        $amountObj = app::get('console')->model('vopbill_amount');
        $discountObj = app::get('console')->model('vopbill_discount');
        $mainObj = app::get('console')->model('vopbill');
        $filter = [
            'status' => '0',
            'discount_sync_status' => '0',
            'id' => $row['id']
        ];
        $rs = $mainObj->update(['discount_sync_status'=>'1', 'last_modified'=>time()], $filter);
        if(is_bool($rs)) {
            return [false, '并发了'];
        } 
        $start_time = $row['get_time'] - 90*86400;
        $pageNo = 1;
        do {

            $sdf = ['start_time' => $start_time, 'end_time' => $row['get_time'], 'bill_number'=>$row['bill_number'], 'page_no'=>$pageNo, 'page_size'=>'150'];
         
            $result = kernel::single('erpapi_router_request')->set('shop', $row['shop_id'])->finance_getBillDiscountDetail($sdf);
            
            if (empty($result['data']['items'])) {
                if ($result['data']['count'] === 0){
                    $mainObj->update(['discount_sync_status'=>'2'], ['id'=>$row['id']]);
                }

                break;
            }

            if($result['data']['count']) {
                $mainObj->update(['discount_count'=>$result['data']['count']], ['id'=>$row['id']]);
            }
            foreach ($result['data']['items'] as $v) {
            
                if(!$discountObj->db_dump(['origin_id'=>$v['id']])) {
                    kernel::database()->beginTransaction();
                    $insertItem = [
                        'bill_id'           => $row['id'],
                        'origin_id'         => $v['id'],
                        'bill_amount_id'    => $oldAmount['id'],
                        'bill_number'       => $row['bill_number'],
                        'ordernum'          => $v['orderNum'],
                        'barcode'           => $v['itemNo'],
                        'product_name'      => $v['itemDescription'],
                        'detail_line_type'  => $v['detailLineType'],
                        'detail_line_name'  => $v['detailLineName'],
                        'shop_id'           => $row['shop_id'],
                        'datasign'          => $v['dataSign'],
                        'totalbillamount'   => $v['totalBillAmount'],
                        'addon'             => json_encode($v),
                    ];


                    $amountFilter = [
                        
                        'bill_id' => $insertItem['bill_id'],
                        'barcode' => $insertItem['barcode'],
                    ];
                    if($v['detailLineType'] == 'CR_CUST_DISCOUNT'){
                        $amountFilter['detail_line_type'] = 'CR_CUST';
                    }else if($v['detailLineType'] == 'DR_CUST_DISCOUNT'){
                        $amountFilter['detail_line_type'] = 'DR_CUST';
                    }

                    $oldAmount = $amountObj->db_dump($amountFilter, 'id');
                    $insertItem['bill_amount_id'] = $oldAmount['id'];
                    $discountObj->insert($insertItem);
                    if(!$insertItem['id']) {
                        kernel::database()->rollBack();
                        continue;
                    }
                    $discount_amount = $insertItem['datasign']*$insertItem['totalbillamount'];
                    if($oldAmount){//更新对应online_type行
                        $updateamountsql = 'update sdb_console_vopbill_amount SET discount_amount=discount_amount+'.$discount_amount.' WHERE id='.$oldAmount['id'];
                        $mainObj->db->exec($updateamountsql);


                    }
                    $updateBillSql = 'update sdb_console_vopbill set get_discount_count=get_discount_count+1,discount_amount=discount_amount+'.$discount_amount;

                    $updateBillSql .= ' where id='.$row['id'];
                    $mainObj->db->exec($updateBillSql);
                   
                    kernel::database()->commit();

                }
            }

            $nbRow = $mainObj->db_dump(['id'=>$row['id']], 'discount_count,get_discount_count');
            if($nbRow['get_discount_count'] >= $nbRow['discount_count']) {

                $mainObj->db->exec("UPDATE sdb_console_vopbill_amount SET total_amount=(amount+discount_amount) WHERE bill_id=".$row['id']."");
                //做sum更新
                $mainObj->update(['discount_sync_status'=>'2'], ['id'=>$row['id']]);
                break;
            }
            $pageNo ++;

        } while(true);

        
    }

    public function getItemSourceDetail($row){
        $detailMdl = app::get('console')->model('vopbill_detail');
        $pageNo = 1;
        $start_time = $row['get_time'] - 90*86400;
        do {
            $sdf = ['start_time' => $start_time, 'end_time' => $row['get_time'], 'bill_number'=>$row['bill_number'], 'page_no'=>$pageNo, 'page_size'=>'50'];
         
            $result = kernel::single('erpapi_router_request')->set('shop', $row['shop_id'])->finance_getItemSourceDetail($sdf);
            if (empty($result['data']['items'])) {
                if ($result['data']['count'] === 0){
                    
                }

                break;
            }

            if($result['data']['count']) {
                $mainObj->update(['detail_count'=>$result['data']['count']], ['id'=>$row['id']]);
            }

            foreach ($result['data']['items'] as $v) {

                if(!$detailMdl->db_dump(['globalid'=>$v['globalId']])) {
                    kernel::database()->beginTransaction();
                    
                    $insertItem = [
                        'bill_id'           =>  $row['id'],
                        'bill_number'       =>  $row['bill_number'],
                        'shop_id'           =>  $row['shop_id'],
                        'globalid'          =>  $v['globalId'],
                        'barcode'           =>  $v['itemNo'],
                        'quantity'          =>  $v['quantity'],
                        'source'            =>  $v['source'],
                        'sourcetype'        =>  $v['sourceType'],
                        'amount'            =>  $v['amount'],
                        'targetamount'      =>  $v['targetAmount'],
                        'expid'             =>  $v['expId'],
                        'extordernum'       =>  $v['extOrderNum'],
                        'feeitem'           =>  $v['feeItem'],
                        'addon'             => json_encode($v),
                    ];
                    
                    $detailMdl->insert($insertItem);
                    if(!$insertItem['id']) {
                        kernel::database()->rollBack();
                        continue;
                    }
                    
                    $updateBillSql = 'update sdb_console_vopbill set get_detail_count=get_detail_count+1 where id='.$row['id'];

                    $mainObj->db->exec($updateBillSql);
                    kernel::database()->commit();
                }
            }

            $nbRow = $mainObj->db_dump(['id'=>$row['id']], 'sku_count,get_count');
            if($nbRow['detail_count'] >= $nbRow['get_detail_count']) {

                //做sum更新
                $mainObj->update(['sync_status'=>'2'], ['id'=>$row['id']]);
                break;
            }
            $pageNo ++;
        } while(true);    
            
    }
}