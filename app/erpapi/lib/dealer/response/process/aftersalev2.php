<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 售后退货业务
 *
 * @version 2024.04.11
 */
class erpapi_dealer_response_process_aftersalev2
{
    
    static public $refund_status = array(
        'REFUND_WAIT_SELLER_AGREE'=>'6',
        'WAIT_SELLER_AGREE'=>'6',
        'WAIT_BUYER_RETURN_GOODS'=>'6',//卖家已经同意退款
        'SELLER_REFUSE_BUYER'=>'6',//卖家拒绝seller_refuse
        'CLOSED'=>'1',//退款关闭
        'SUCCESS'=>'6',//退款成功

    );

    /**
     * 添加
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function add($sdf)
    {
        $rs = $this->_dealAftersale($sdf);
        return $rs;
    }

    private function _dealAftersale($sdf) {

        $modelAftersale = app::get('dealer')->model('platform_aftersale');
        $logMdl = app::get('ome')->model('operation_log');
        if($sdf['platform_aftersale']) {
            $filter = array(
                'plat_aftersale_id' => $sdf['platform_aftersale']['plat_aftersale_id'],
            );


            $upData = array(
                'status' => $sdf['status'],
               
                'outer_lastmodify'=> $sdf['modified'],
            );
            
            $upData['memo']  = $sdf['reason'];
            $upData['money'] = $sdf['refund_fee'];
           
            
            $rs = $modelAftersale->update($upData, $filter);
            $idBn = array(
                'plat_aftersale_id' => $sdf['platform_aftersale']['plat_aftersale_id'],
                'plat_aftersale_bn' => $sdf['platform_aftersale']['plat_aftersale_bn']
            );
            
            
            $this->_dealAftersaleExtend($sdf, $idBn['plat_aftersale_id']);

            $insertData['plat_aftersale_id'] = $sdf['platform_aftersale']['plat_aftersale_id'];

            $msg = '更新成功';
            $logMdl->write_log('aftersale@dealer', $insertData['plat_aftersale_id'], $msg);

        } else {


            $insertData = $this->_aftersaleSdfToData($sdf);

            $aftersale_objects = $insertData['aftersale_objects'];
            unset($insertData['aftersale_objects']);
           
           
            //insert
            $rs = $modelAftersale->insert($insertData);

        
            if(!$rs) {
                return array('rsp'=>'fail', 'msg'=>'售后申请单新建失败');
            }
          

            $itemrs = $this->_insertaftersaleItems($aftersale_objects, $insertData['plat_aftersale_id']);
         
            $msg = '创建平台售后申请单成功';
            $logMdl->write_log('aftersale@dealer', $insertData['plat_aftersale_id'], $msg);

            $this->_dealAftersaleExtend($sdf, $insertData['plat_aftersale_id']);
        }
        

        $refund_item_list =$sdf['refund_item_list'];
        

        $refund_items = array();

        foreach($refund_item_list as $k=>$v){
            foreach($v['items'] as $iv){
                $refund_items[$iv['erp_order_id']][] = $iv;
            }
            
        }
        //明细处理
        
        foreach($refund_items as $k=>$v){

            if($k>0){
                kernel::single('dealer_event_trigger_aftersale')->push($insertData['plat_aftersale_id']);
            }
        }

        //
        $this->_dealPlatOrderitems($sdf);


        if(in_array($sdf['status'],array('SUCCESS'))) {

            $this->_updatePlaOrderPayed($sdf['platform_orders']['plat_order_id'], $sdf['refund_fee']); 

            $this->update_plaorder_pay_status($sdf['platform_orders']['plat_order_id']);
        } 
        //
        return array('rsp'=>'succ', 'msg' => $msg);
    }

    private function _aftersaleSdfToData($sdf) {
      
        $data = array(

            'plat_aftersale_bn' => $sdf['refund_bn'],
            'plat_order_bn'     => $sdf['order_bn'],
            'plat_order_id'     => $sdf['platform_orders']['plat_order_id'], 
            'oid'               => $sdf['oid'],
            'shop_id'           => $sdf['shop_id'],
            'shop_type'         => $sdf['shop_type'],   
            'member_id'         => $sdf['member_id'],
            'add_time'          => $sdf['created']?:time(),
            'at_time'           => time(),
            'up_time'           => time(),
            'status'            => $sdf['status'],        
            'refund_apply_money'=> $sdf['refund_fee'],
            'refundmoney'      => $sdf['refund_fee'],
            'return_type'      =>  $sdf['refund_type'],
            'betc_id'           => $sdf['betc_id'],
            'cos_id'            => $sdf['cos_id'],
            'outer_lastmodify' => $sdf['modified'],

        );
    
        $refund_item_list = $sdf['refund_item_list'];


        $this->divide_objects_to_items($refund_item_list);

     
        foreach($refund_item_list as $val) {

            $aftersale_items = array();

            foreach($val['items'] as $iv){
                $aftersale_items[] = array(
              
                    'shop_goods_bn' =>  $iv['shop_goods_bn'],
                    'product_id'    =>  $iv['product_id'],
                    'bn'            =>  $iv['bn'],
                    'name'          =>  $iv['name'],
                    'oid'           =>  $iv['oid'],
                    'num'           =>  $iv['num'],
                    'price'         =>  $iv['price'],
                    'erp_num'       =>  $iv['erp_num'],
                    'erp_price'     =>  $iv['erp_price'],
                    'betc_id'       =>  $iv['betc_id'],
                    'plat_obj_id'   =>  $iv['plat_obj_id'],
                    'plat_item_id'  =>  $iv['plat_item_id'],
                    'erp_order_bn'  =>  $iv['erp_order_bn'],
                    'erp_order_id'  =>  $iv['erp_order_id'],
                    'erp_obj_id'    =>  $iv['erp_obj_id'],
                    'at_time'       =>  time(),
                    'up_time'       =>  time(),
                );
                $data['erp_order_bn'] = $iv['erp_order_bn'];

                $data['erp_order_id'] = $iv['erp_order_id'];
            }
            $objectsData = array(
                'oid'               =>  $val['oid'],
                'num'               =>  $val['num'],
                'price'             =>  $val['price'],
                'outer_id'          =>  $val['outer_id'],
                'goods_id'          =>  $val['goods_id'],
                'aftersale_items'   =>  $aftersale_items,
                
            );

          
            $data['aftersale_objects'][] = $objectsData;
            
        }
        $erp_order_id = $data['erp_order_id'];

        $omeorderMdl = app::get('ome')->model('orders');

        $omeorders = $omeorderMdl->db_dump(array('order_id'=>$erp_order_id),'betc_id,cos_id');

        $data['betc_id'] = $omeorders['betc_id'];
        $data['cos_id'] = $omeorders['cos_id'];
        return $data;
    }
    
    private function _insertaftersaleItems($aftersale_items, $plat_aftersale_id) {
        if(empty($plat_aftersale_id) || empty($aftersale_items)) {
            return false;
        }
        $modelItem = app::get('dealer')->model('platform_aftersale_items');
        $objectsMdl = app::get('dealer')->model('platform_aftersale_objects');
        foreach($aftersale_items as $val) {

            $objectsData = array(

                'plat_aftersale_id' =>  $plat_aftersale_id,
                'oid'               =>  $val['oid'],
                'num'               =>  $val['num'],
                'price'             =>  $val['price'],
                'outer_id'          =>  $val['outer_id'],
                'goods_id'          =>  $val['goods_id'],
            );
          
            $rs = $objectsMdl->insert($objectsData);

            $plat_aftersale_obj_id = $objectsData['plat_aftersale_obj_id'];
            $aftersale_itemsData = $val['aftersale_items'];
            foreach($aftersale_itemsData as &$iv){

                $iv['plat_aftersale_id'] = $plat_aftersale_id;
                $iv['plat_aftersale_obj_id'] = $plat_aftersale_obj_id;
            }
            if($aftersale_itemsData){
                $sql = ome_func::get_insert_sql($modelItem, $aftersale_itemsData);
      
                $rs = $modelItem->db->exec($sql);
            }

            
            
        }
        
        
        return true;
    }


    /**
     * 更新_plaorder_pay_status
     * @param mixed $plat_order_id ID
     * @return mixed 返回值
     */
    public function update_plaorder_pay_status($plat_order_id)
    {
        $plat_ordersMdl        = app::get('dealer')->model('platform_orders');
        $order_filter    = array('plat_order_id' => $plat_order_id);

        $order_detail = $plat_ordersMdl->dump($order_filter, 'plat_order_bn,payed,total_amount');

        $payed        = strval($order_detail['payed']);
        $total_amount = strval($order_detail['total_amount']);
   
        $pay_status = '6';

        if ($payed == '0') {
            $pay_status = '5';
        }elseif ($payed < $total_amount) {
            $pay_status = '4'; 

        }

        $data['pay_status'] = $pay_status;

        $order_filter    = array('plat_order_id' => $plat_order_id);
        if($data){
         
            $plat_ordersMdl->update($data, $order_filter);

        }

        return true;
    }


    /**
     * _dealAftersaleExtend
     * @param mixed $sdf sdf
     * @param mixed $plat_aftersale_id ID
     * @return mixed 返回值
     */
    public function _dealAftersaleExtend($sdf, $plat_aftersale_id) {
        if(empty($sdf['json_data']) || empty($plat_aftersale_id)) {
            return false;
        }
        $extenddata = array(

            'json_data' =>$sdf['json_data'],

        );
        $extendmodel = app::get('dealer')->model('platform_aftersale_extend');
         $old = $extendmodel->db_dump(array('plat_aftersale_id'=>$plat_aftersale_id), 'plat_extend_id');
            if($old) {
                $extendmodel->update($extenddata, array('plat_extend_id'=>$old['plat_extend_id']));
                return;
            }
        $extenddata['plat_aftersale_id'] = $plat_aftersale_id;
        $extendmodel->db_save($extenddata);
    }


    //更新订单金额
    /**
     * _updatePlaOrderPayed
     * @param mixed $plat_order_id ID
     * @param mixed $money money
     * @return mixed 返回值
     */
    public function _updatePlaOrderPayed($plat_order_id, $money) {

        if (empty($plat_order_id) || !$money) {
            return false;
        }
        #更新订单分先后，避免订单退款并发时发货单撤回不及时导致订单明细未删除
        $transaction = kernel::database()->beginTransaction();
        $sql ="update sdb_dealer_platform_orders set payed=IF((CAST(payed AS char)-".$money.")>=0,payed-".$money.",0)  where plat_order_id=".$plat_order_id;

        kernel::database()->exec($sql);
        kernel::database()->commit($transaction);

        return true;
        
    }


    /**
     * _dealPlatOrderitems
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function _dealPlatOrderitems($sdf) {

        $db = kernel::database();
        $pay_status = self::$refund_status[strtoupper($sdf['status'])];

        $pay_status = $pay_status ? $pay_status : '6';

        $plat_ordersMdl        = app::get('dealer')->model('platform_orders');
        $plat_obj_ids = array();
        foreach($sdf['refund_item_list'] as $val) {
           foreach($val['items'] as $iv){
                $plat_obj_ids[$iv['plat_obj_id']] = $iv['plat_obj_id'];
           }
           
        }

        if($plat_obj_ids){

            $plat_obj_ids = implode(',',$plat_obj_ids);
            $db->exec("UPDATE sdb_dealer_platform_order_objects set pay_status ='".$pay_status."' WHERE plat_obj_id in (".$plat_obj_ids.")");

        }
        
        $data['pay_status'] = $pay_status;
        $order_filter    = array('plat_order_id' => $sdf['platform_orders']['plat_order_id']);
        if($data){
         
            $plat_ordersMdl->update($data, $order_filter);

        }

    }


    /**
     * 获取SmBmRate
     * @param mixed $refund_item_list refund_item_list
     * @return mixed 返回结果
     */
    public function getSmBmRate($refund_item_list) {
        $smIds = array();
        $bmIds = array();
        foreach ($refund_item_list as $object) {
            $smIds[$object['goods_id']] = $object['goods_id'];
            foreach ($object['items'] as $i => $item) {
                $bmIds[$item['product_id']] = $item['product_id'];
            }
        }
        $smBc = app::get('material')->model('sales_basic_material')->getList('sm_id, bm_id, rate', array('sm_id'=>$smIds));
        $smBmRate = array();
        foreach ($smBc as $v) {
            $smBmRate[$v['sm_id']][$v['bm_id']] = $v['rate'];
        }
        $bmExt = app::get('material')->model('basic_material_ext')->getList('bm_id,cost', ['bm_id'=>$bmIds]);
        $bmExt = array_column($bmExt, null, 'bm_id');
        $smIdBn = array();
        foreach ($refund_item_list as $object) {
            if(!$smBmRate[$object['goods_id']]) {
                $porth = 'number';
                foreach ($object['items'] as $i => $item) {
                    if($bmExt[$item['product_id']]['cost'] > 0) {
                        $porth = 'cost';
                    }
                }
                foreach ($object['items'] as $i => $item) {
                 
                    $smBmRate[$object['goods_id']][$item['product_id']] = $bmExt[$item['product_id']]['cost'];
                }
            }
        }
        return $smBmRate;
    }

    
    /**
     * divide_objects_to_items
     * @param mixed $refund_item_list refund_item_list
     * @return mixed 返回值
     */
    public function divide_objects_to_items(&$refund_item_list) {
        $smBmRate = $this->getSmBmRate($refund_item_list);

        foreach ($refund_item_list as $k => $object) {
            if(empty($smBmRate[$object['goods_id']])) {
                continue;
            }
            $tmpOrderItems = $object['items'];
            foreach ($tmpOrderItems as $i => $item) {
                if($item['product_id'] < 1) {
                    continue 2;
                }
                $tmpOrderItems[$i]['porth_field'] = $smBmRate[$object['goods_id']][$item['product_id']];
            }
            $options = array (
                'part_total'  => $object['price'],
                'part_field'  => 'price',
                'porth_field' => 'porth_field',
            );
            $tmpOrderItems = kernel::single('ome_order')->calculate_part_porth($tmpOrderItems, $options);
            
            $refund_item_list[$k]['items'] = $tmpOrderItems;
          
        }
        
        return $refund_item_list;
    }
}