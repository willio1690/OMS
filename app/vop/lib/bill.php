<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_bill {

    /**
     * 获取BillNumber
     * @param mixed $startTime startTime
     * @param mixed $endTime endTime
     * @param mixed $shopId ID
     * @return mixed 返回结果
     */
    public function getBillNumber($startTime, $endTime, $shopId) {
        $pageNo = 1;
        $model = app::get('vop')->model('bill');
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

    /**
     * 获取BillDetail
     * @param mixed $row row
     * @return mixed 返回结果
     */
    public function getBillDetail($row) {
        if(empty($row['id']) || empty($row['bill_number']) || empty($row['shop_id'])) {
            return [false, '数据不全'];
        }
        $mainObj = app::get('vop')->model('bill');
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

        $poObj = app::get('vop')->model('po');
        $itemObj = app::get('vop')->model('source_billgoods');
        $extMdl = app::get('vop')->model('source_billgoods_ext');
        $minid = '';

        $maxid = '';
       
        do {
            $sdf = ['start_time' => $start_time, 'end_time' => $row['get_time'], 'bill_number'=>$row['bill_number'], 'page_no'=>$pageNo, 'page_size'=>'100'];

           
            $result = kernel::single('erpapi_router_request')->set('shop', $row['shop_id'])->finance_fetchBillGoodsDetail($sdf);
           


            if($result['rsp'] == 'fail'){
                $mainObj->update(['sync_status'=>'0'], ['id'=>$row['id']]);
                break;
            }
            
            if ($result['rsp'] == 'succ' && $result['data']['count'] == 0){
                
                $mainObj->update(['sync_status'=>'2'], ['id'=>$row['id']]);
                break;
            }

            
            if($result['data']['count']) {
                $mainObj->update(['sku_count'=>$result['data']['count']], ['id'=>$row['id']]);
            }
            foreach ($result['data']['items'] as $v) {
                if(!$itemObj->db_dump(['global_id'=>$v['globalId']])) {
                    kernel::database()->beginTransaction();
                    
                    $insertItem = $this->formatGoodsDetail($v);
                    //po表保存
                    $amount = $insertItem['total_bill_amount']*$insertItem['datasign'];
                    $quantity = $insertItem['payable_quantity']*$insertItem['datasign'];
                    $insertItem['shop_id'] = $row['shop_id'];
                    $insertItem['bill_id'] = $row['id'];
                    $insertItem['final_total_amount'] = $amount;
                    $rs = $itemObj->insert($insertItem);
                   
                  
                    if(!$insertItem['id']) {
                        kernel::database()->rollBack();
                        continue;
                    }

                    $insertext = array(
                        'source_billgoods_id'   =>  $insertItem['id'],
                        'bill_id'               =>  $row['id'],
                        'addon'                 =>  json_encode($v),
                    );
                    $extMdl->insert($insertext);

                    $updateBillSql = 'update sdb_vop_bill set get_count=get_count+1 ';
                    
                    if($v['detailLineType'] == 'OTHER') {

                        $updateBillSql .= ' ,other_amount=other_amount+'.$amount.',other_quantity=other_quantity+'.$quantity;
                    }elseif($insertItem['detail_line_type'] == 'CR_CUST') {
                        $updateBillSql .= ' ,cr_cust_amount=cr_cust_amount+'.$amount.',cr_cust_quantity=cr_cust_quantity+'.$quantity;
                    }elseif($insertItem['detail_line_type'] == 'DR_CUST') {
                        $updateBillSql .= ' ,dr_cust_amount=dr_cust_amount+'.$amount.',dr_cust_quantity=dr_cust_quantity+'.$quantity;
                    }
                    $updateBillSql .= ' where id='.$row['id'];

                  
                    $mainObj->db->exec($updateBillSql);

                    
                    $insertpo = [
                        'bill_id'           =>  $row['id'],
                        'bill_number'       =>  $row['bill_number'],
                        'po_no'             =>  $insertItem['po_no'],
                        'bill_type'         =>  $insertItem['detail_line_type'],
                        'bill_type_name'    =>  $insertItem['detail_line_name'],
                        'shop_id'           =>  $insertItem['shop_id'],
                        'quantity'          =>  $insertItem['payable_quantity']*$insertItem['datasign'],
                        'amount'            =>  $amount,
                        'create_time'       =>  time(),
                    ];

                    $poFilter = [
                        'bill_type'     => $insertpo['bill_type'],
                        'bill_id'       => $insertpo['bill_id'],

                        'po_no'         => $insertpo['po_no'],
                    ];
                    $oldpo = $poObj->db_dump($poFilter, 'po_id');;
                    if($oldpo) {
                        $sql = 'update sdb_vop_po 
                            set quantity=quantity+'.$insertpo['quantity'].',amount=amount+'.$insertpo['amount'].' where po_id='.$oldpo['po_id'];
                        $poObj->db->exec($sql);
                        $insertpo['po_id'] = $oldpo['po_id'];
                    } else {
                       
                        
                        $poObj->insert($insertpo);
                        
                    }

                    $itemObj->update(['po_id'=>$insertpo['po_id']], ['id'=>$insertItem['id']]);


                    kernel::database()->commit();


                }
            }

            $nbRow = $mainObj->db_dump(['id'=>$row['id']], 'sku_count,get_count');
            if($result['rsp'] == 'succ' && $nbRow['get_count'] >= $nbRow['sku_count']) {
                
                //做sum更新
                
                $mainObj->update(['sync_status'=>'2'], ['id'=>$row['id']]);


                break;
            }
            if($result['data']['hasNext']==false){
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

      
        $discountObj = app::get('vop')->model('source_discount');
        $mainObj = app::get('vop')->model('bill');
        $filter = [
            'status' => '0',
            'discount_sync_status' => '0',
            'id' => $row['id']
        ];
        $rs = $mainObj->update(['discount_sync_status'=>'1', 'last_modified'=>time()], $filter);
        if(is_bool($rs)) {
            return [false, '并发了'];
        } 

        $poObj = app::get('vop')->model('po');

        $extMdl = app::get('vop')->model('source_discount_ext');
        $start_time = $row['get_time'] - 90*86400;
        $pageNo = 1;

        $minid = '';
        $maxid = '';
        do {

            $sdf = [
                'start_time'    =>  $start_time, 
                'end_time'      =>  $row['get_time'], 
                'bill_number'   =>  $row['bill_number'], 
                'page_no'       =>  $pageNo, 
                'page_size'     =>  '150',
                'minid'         =>  $minid,
                'maxid'         =>  $maxid,
            ];
         
            $result = kernel::single('erpapi_router_request')->set('shop', $row['shop_id'])->finance_fetchBillDiscountDetail($sdf);
            
            if($result['rsp'] == 'fail'){
                $mainObj->update(['discount_sync_status'=>'0'], ['id'=>$row['id']]);
                break;
            }
            $minid = $result['data']['minId'];

            $maxid = $result['data']['maxId'];


            if ($result['rsp'] == 'succ' && $result['data']['count'] == 0){
                $mainObj->update(['discount_sync_status'=>'2'], ['id'=>$row['id']]);
                break;
            }

            if($result['data']['count']) {
                $mainObj->update(['discount_count'=>$result['data']['count']], ['id'=>$row['id']]);
            }
            foreach ($result['data']['items'] as $v) {
           
                if(!$discountObj->db_dump(['global_id'=>$v['globalId']])) {
                    kernel::database()->beginTransaction();
                    //po表保存
                    
                    $insertItem = $this->formatDiscountDetail($v);
                    $amount = $insertItem['total_bill_amount']*$insertItem['datasign'];
                    $insertItem['shop_id'] = $row['shop_id'];
                    $insertItem['bill_id'] = $row['id'];
                    $insertItem['final_total_amount'] = $amount;
                    $rs = $discountObj->insert($insertItem);
                  
                    if(!$insertItem['id']) {
                        kernel::database()->rollBack();
                        continue;
                    }
                  
                    $insertext = array(
                        'source_discount_id'    =>  $insertItem['id'],
                        'bill_id'               =>  $row['id'],
                        'addon'                 =>  json_encode($v),
                    );
                    $extMdl->insert($insertext);
                    $updateBillSql = 'update sdb_vop_bill set get_discount_count=get_discount_count+1,discount_amount=discount_amount+'.$amount;

                    $updateBillSql .= ' where id='.$row['id'];
                    $mainObj->db->exec($updateBillSql);
                   
                    

                    $detail_line_type = $insertItem['detail_line_type'];

                    ////活动折扣优惠  detail_line_type like '%DISCOUNT%
                    ///会员价保  detail_line_type like '%INSURE%'
                    if(preg_match('/INSURE/', $insertItem['detail_line_type'])){

                        $detail_line_type = 'INSURE';
                        $insertItem['detail_line_name'] = '会员价保';
                    }
                    if(preg_match('/DISCOUNT/', $insertItem['detail_line_type'])){

                        $detail_line_type = 'DISCOUNT';
                        $insertItem['detail_line_name'] = '活动折扣优惠';
                    }
                    $insertpo = [
                        'bill_id'           =>  $row['id'],
                        'bill_number'       =>  $row['bill_number'],
                        'po_no'             =>  $insertItem['po_no'],
                        'bill_type'         =>  $detail_line_type,
                        'bill_type_name'    =>  $insertItem['detail_line_name'],
                        'shop_id'           =>  $insertItem['shop_id'],
                        'quantity'          =>  0,
                        'amount'            =>  $amount,
                        'create_time'       =>  time(),
                    ];
                    $poFilter = [
                        'bill_type'     => $insertpo['bill_type'],
                        'bill_id'       => $insertpo['bill_id'],

                        'po_no'         => $insertpo['po_no'],
                    ];
                    $oldpo = $poObj->db_dump($poFilter, 'po_id');;
                    if($oldpo) {
                        $sql = 'update sdb_vop_po 
                            set quantity=quantity+'.$insertpo['quantity'].',amount=amount+'.$insertpo['amount'].' where po_id='.$oldpo['po_id'];
                        $poObj->db->exec($sql);
                        $insertpo['po_id'] = $oldpo['po_id'];
                    } else {
                       
                        $poObj->insert($insertpo);
                        
                    }

                    $discountObj->update(['po_id'=>$insertpo['po_id']], ['id'=>$insertItem['id']]);

                    kernel::database()->commit();

                }
               
            }

            $nbRow = $mainObj->db_dump(['id'=>$row['id']], 'discount_count,get_discount_count');
            if($nbRow['get_discount_count'] >= $nbRow['discount_count']) {
                
                //做sum更新
                
                $mainObj->update(['discount_sync_status'=>'2'], ['id'=>$row['id']]);
                break;
            }
           
            $pageNo ++;

        } while(true);

        
    }

   

    /**
     * formatGoodsDetail
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function formatGoodsDetail($row){
        $detailLineType = $row['detailLineType'] == 'DR_CUST_EXTRA' ? 'DR_CUST' : $row['detailLineType'];
        $itemrow = array(

            'global_id'         =>  $row['globalId'],
            'vendor_id'         =>  $row['vendorId'],
            'vendor_code'       =>  $row['vendorCode'],
            'vendor_name'       =>  $row['vendorName'],
            'schedule_id'       =>  $row['scheduleId'],
            'schedule_name'     =>  $row['scheduleName'],
            'bill_number'       =>  $row['billNumber'],
            'order_num'         =>  $row['orderNum'],
            'order_date'        =>  strtotime($row['orderDate']),
            'signtime'          =>  $row['signTime'] ? $row['signTime']/1000: 0,
            'item_no'           =>  $row['itemNo'],
            'item_description'  =>  $row['itemDescription'],
            'po_no'             =>  $row['poNo'],
            'goods_no'          =>  $row['goodsNo'],
            'order_price'       =>  $row['orderPrice'],
            'tax_rate'          =>  $row['taxRate'],
            'is_deleted'        =>  $row['isDeleted'],
            'detail_line_type'  =>  $detailLineType,
            'bill_price'        =>  $row['billPrice'],
            'bill_tax_price'    =>  $row['billTaxPrice'],
            'payable_quantity'  =>  $row['payableQuantity'],
            'bill_quantity'     =>  $row['billQuantity'],
            'bill_amount'       =>  $row['billAmount'],
            'datasign'          =>  $row['dataSign'],
            'total_bill_amount' =>  $row['totalBillAmount'],
            'reference_number'  =>  $row['referenceNumber'],
            'exchange_flag'     =>  $row['exchangeFlag'],
            'source_line_type'  =>  $row['sourceLineType'],
            'pick_no'           =>  $row['pickNo'],
            'delivery_no'       =>  $row['deliveryNo'],
            'source_line_name'  =>  $row['sourceLineType'],
            'detail_line_name'  =>  $row['detailLineName'],
            


        );

        return $itemrow;
    }


    /**
     * formatDiscountDetail
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function formatDiscountDetail($row){


        $itemrow = array(
            'global_id'                         =>  $row['globalId'],
            'vendor_id'                         =>  $row['vendorId'],
            'vendor_code'                       =>  $row['vendorCode'],
            'vendor_name'                       =>  $row['vendorName'],
            'schedule_id'                       =>  $row['scheduleId'],
            'schedule_name'                     =>  $row['scheduleName'],
            'po_no'                             =>  $row['poNo'],
            'signtime'                          =>  $row['signTime'] ? $row['signTime']/1000: 0,
            'bill_number'                       =>  $row['billNumber'],
            'order_num'                         =>  $row['orderNum'],
            'order_date'                        =>  strtotime($row['orderDate']),
            'item_no'                           =>  $row['itemNo'],
            'item_description'                  =>  $row['itemDescription'],
            'goods_no'                          =>  $row['goodsNo'],
            'order_price'                       =>  $row['orderPrice'],
            'tax_rate'                          =>  $row['taxRate'],
            'payable_bill_amount'               =>  $row['payable_bill_amount'],
            'is_deleted'                        =>  $row['isDeleted'],
            'po_price'                          =>  $row['poPrice'],
            'po_tax_price'                      =>  $row['poTaxPrice'],
            'payable_total_bill_amount'         =>  $row['payableTotalBillAmount'],
            'detail_line_type'                  =>  $row['detailLineType'],
            'active_type'                       =>  $row['activeType'],
            'active_type_name'                  =>  $row['activeTypeName'],
            'act_parent_no'                     =>  $row['actParentNo'],
            'act_parent_name'                   =>  $row['actParentName'],
            'red_packet_value'                  =>  $row['redPacketValue'],
            'fav_price'                         =>  $row['favPrice'],
            'total_amount'                      =>  $row['totalAmount'],
            'datasign'                          =>  $row['dataSign'],
            'vendor_red_packet_count'           =>  $row['vendorRedpacketCount'],
            'enter_total_bill_amount'           =>  $row['enterTotalBillAmount'],
            'enter_payable_total_bill_amount'   =>  $row['enterPayableTotalBillAmount'],
            'act_vendor_amount'                 =>  $row['actVendorAmount'],
            'new_act_vendor_amount'             =>  $row['newActVendorAmount'],
            'bill_amount'                       =>  $row['billAmount'],
            'total_bill_amount'                 =>  $row['totalBillAmount'],
            
            


        );
        return $itemrow;
    }


    /**
     * 获取ItemSourceDetail
     * @param mixed $row row
     * @return mixed 返回结果
     */
    public function getItemSourceDetail($row){
        $mainObj = app::get('vop')->model('bill');
        $detailMdl = app::get('vop')->model('source_detail');
        $poObj = app::get('vop')->model('po');
        $extMdl = app::get('vop')->model('source_detail_ext');
        $pageNo = 1;
        $start_time = $row['get_time'] - 90*86400;
        do {
            $sdf = ['start_time' => $start_time, 'end_time' => $row['get_time'], 'bill_number'=>$row['bill_number'], 'page_no'=>$pageNo, 'page_size'=>'50'];
         
            $result = kernel::single('erpapi_router_request')->set('shop', $row['shop_id'])->finance_getItemSourceDetail($sdf);

            if($result['rsp'] == 'fail'){
                $mainObj->update(['detail_sync_status'=>'0'], ['id'=>$row['id']]);
                break;
            }

            
            if ($result['rsp'] == 'succ' && $result['data']['count'] == 0){
                $mainObj->update(['detail_sync_status'=>'2'], ['id'=>$row['id']]);
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
                        'signtime'          =>  $row['signtime'] ? strtotime($row['signtime']/1000): 0,
                        'shop_id'           =>  $row['shop_id'],
                        'po_no'             =>  $v['po'],
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
                        'defcode'           =>  $v['defCode'],
                        
                    ];
                    
                    $detailMdl->insert($insertItem);
                    if(!$insertItem['id']) {
                        kernel::database()->rollBack();
                        continue;
                    }
                    
                    $insertext = array(
                        'source_detail_id'      =>  $insertItem['id'],
                        'bill_id'               =>  $row['id'],
                        'addon'                 =>  json_encode($v),
                    );
                    $extMdl->insert($insertext);
                    $updateBillSql = 'update sdb_vop_bill set get_detail_count=get_detail_count+1,detail_amount=detail_amount+'.$insertItem['amount'].' where id='.$row['id'];

                    $mainObj->db->exec($updateBillSql);

                    if($insertItem['amount']>0){
                        $bill_type = 'reshipdiff';
                        $bill_type_name = '退货差异';
                    }

                    if($insertItem['amount']<0){
                        $bill_type = 'onlyrefund';
                        $bill_type_name = '仅退款';
                    }
                   
                    $insertpo = [
                        'bill_id'           =>  $row['id'],
                        'bill_number'       =>  $row['bill_number'],
                        'po_no'             =>  $insertItem['po_no'],
                        'bill_type'         =>  $bill_type,
                        'bill_type_name'    =>  $bill_type_name,
                        'shop_id'           =>  $insertItem['shop_id'],
                        'quantity'          =>  0,
                        'amount'            =>  $insertItem['amount'],
                        'create_time'       =>  time(),
                    ];
                    $poFilter = [
                        'bill_type'     => $insertpo['bill_type'],
                        'bill_id'       => $insertpo['bill_id'],

                        'po_no'         => $insertpo['po_no'],
                    ];
                    $oldpo = $poObj->db_dump($poFilter, 'po_id');;
                    if($oldpo) {
                        $sql = 'update sdb_vop_po 
                            set quantity=quantity+'.$insertpo['quantity'].',amount=amount+'.$insertpo['amount'].' where po_id='.$oldpo['po_id'];
                        $poObj->db->exec($sql);
                        $insertpo['po_id'] = $oldpo['po_id'];
                    } else {
                       
                        $poObj->insert($insertpo);
                        
                    }

                    $detailMdl->update(['po_id'=>$insertpo['po_id']], ['id'=>$insertItem['id']]);

                    kernel::database()->commit();
                }
            }

            $nbRow = $mainObj->db_dump(['id'=>$row['id']], 'detail_count,get_detail_count');
            if($nbRow['detail_count'] >= $nbRow['get_detail_count']) {

                //做sum更新
                $mainObj->update(['detail_sync_status'=>'2'], ['id'=>$row['id']]);

                break;
            }
            $pageNo ++;
        } while(true);    
            
    }


    /**
     * 获取Detail
     * @param mixed $bill_id ID
     * @return mixed 返回结果
     */
    public function getDetail($bill_id){

        $detailMdl = app::get('vop')->model('source_detail');
        $sql = "SELECT sum(if(amount=0,targetamount,amount))  as detail_amount FROM `sdb_vop_source_detail` WHERE bill_id=".$bill_id." and amount>0";
      
        $reshipdetails = $detailMdl->db->selectrow($sql);


        $sql1 = "SELECT sum(if(amount=0,targetamount,amount))  as detail_amount FROM `sdb_vop_source_detail` WHERE bill_id=".$bill_id." and amount<0";

        $refunddetails = $detailMdl->db->selectrow($sql1);

        $details = array(

            'reship_amount' =>  sprintf('%.2f',$reshipdetails['detail_amount']),
            'refund_amount' =>  sprintf('%.2f',$refunddetails['detail_amount']),
        );
        return $details;
    }
}