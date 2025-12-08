<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_aftersale
{
    
    /**POS订单 电商仓发货*/
    public function returngoods_confirm($reship_id){
        $reshipMdl = app::get('ome')->model('reship');
        $reship_detail = $reshipMdl->dump(array('reship_id'=>$reship_id,'shop_type'=>'pekon','return_type'=>'return'),'branch_id,return_id,reship_bn,source,is_check,out_iso_bn,reship_id,shop_id,order_id');
        if(!$reship_detail) return true;

        $order_id = $reship_detail['order_id'];

        $orders = $this->getorders($order_id);

        if(!$orders) return true;
        $reship_items = app::get('ome')->model('reship_items')->getList(
            'product_id, num, defective_num, normal_num, order_item_id,bn', ['reship_id'=>$reship_id, 'return_type'=>'return']);

        $product_serial = $this->getSerials($reship_id);
        if($product_serial){
            foreach($reship_items as $k=>$v){
                if ($product_serial[$v['bn']]){
                    $number = $v['defective_num']+$v['normal_num'];
                    $uniqueCodes = array_splice($product_serial[$v['bn']], 0, $number);
                    $reship_items[$k]['uniqueCodes'] = $uniqueCodes;
                }
           
            }
        }
        
        $reship_detail['reship_items'] = $reship_items;
        $branch_id = $reship_detail['branch_id'];
        $branchs = $reshipMdl->db->selectrow("SELECT branch_id FROM sdb_ome_branch WHERE branch_id=".$branch_id." AND b_type=1");

        if(!in_array($reship_detail['is_check'],array('7'))){
            return true;
        }
        
        if(!$reship_detail || !$branchs){
            return true;
        }
        if($reship_detail &&  $branchs){
            $rs = kernel::single('erpapi_router_request')->set('shop', $reship_detail['shop_id'])->aftersale_warehouseConfirm($reship_detail);
            
        }

        $logMdl = app::get('ome')->model('operation_log');
        if($rs['rsp'] == 'fail'){
            $msg = "失败. 原因:".$rs['msg'];
        }else{
            $msg ="成功";   
        }
        $logMdl->write_log('reship@ome',$reship_id,$msg);
    }

    /**
     * 退货单创建
     * @param  
     * @return 
     */
    public function reship_add($reship_id){
        $reshipMdl = app::get('ome')->model('reship');
        $reship_detail = $reshipMdl->dump(array('reship_id'=>$reship_id,'shop_type'=>'pekon','source'=>'local','return_type'=>'return'),'branch_id,return_id,reship_bn,source,return_type,shop_id,order_id');
        
        if(!$reship_detail) return true;
        if($reship_detail['source'] == 'matrix') return true;
        $branch_id = $reship_detail['branch_id'];
        $branchs = $reshipMdl->db->selectrow("SELECT branch_id FROM sdb_ome_branch WHERE branch_id=".$branch_id." AND b_type=1");
        if(!$reship_detail || !$branchs){
            return true;
        }
        $order_id = $reship_detail['order_id'];
        $orders = $this->getorders($order_id);

        if(!$orders) return true;
        if($reship_detail &&  $branchs){
            $shop_id = $reship_detail['shop_id'];
            $storeMdl = app::get('o2o')->model('store');
            $stores = $storeMdl->db_dump(array('shop_id'=>$shop_id),'store_id');
            $store_id = $stores['store_id'];
            $channel_type = 'store';
            $channel_id = $store_id;
            $data = kernel::single('ome_receipt_reship')->reship_create(array('reship_id'=>$reship_id));
            

            $rs = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->reship_create($data);
            if($rs['rsp'] == 'succ'){
                if($rs['data']['wms_order_code']){
                    $saveData['out_iso_bn'] = $rs['data']['wms_order_code'];
                    $reshipMdl->update($saveData, array('reship_id'=>$reship_id));
                }
            
                
            }
            $logMdl = app::get('ome')->model('operation_log');
            if($rs['rsp'] == 'fail'){
                $msg = "失败. 原因:".$rs['msg'];
            }else{
                $msg ="成功";   
            }
            $logMdl->write_log('reship@ome',$reship_id,$msg);
        }
    }
  
    /**
     * 退货单取消
     * @param  
     * @return 
     */
    public function reship_cancel($reship_id){
        $reshipMdl = app::get('ome')->model('reship');
        $reship_detail = $reshipMdl->dump(array('reship_id'=>$reship_id,'shop_type'=>'pekon','source'=>'local'),'branch_id,return_id,reship_bn,source,return_type,shop_id,order_id');
        
        if(!$reship_detail) return true;
        $branch_id = $reship_detail['branch_id'];
        $branchs = $reshipMdl->db->selectrow("SELECT branch_id FROM sdb_ome_branch WHERE branch_id=".$branch_id." AND b_type=1");

        if($reship_detail &&  $branchs){
            $shop_id = $reship_detail['shop_id'];
            $storeMdl = app::get('o2o')->model('store');
            $stores = $storeMdl->db_dump(array('shop_id'=>$shop_id),'store_id');
            $store_id = $stores['store_id'];
            $channel_type = 'store';
            $channel_id = $store_id;
            $data = [
                'reship_bn' => $reship_detail['reship_bn'],
            ];
            $rs = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->reship_cancel($data);
            
            $logMdl = app::get('ome')->model('operation_log');
            if($rs['rsp'] == 'fail'){
                $msg = "推送pos取消失败. 原因:".$rs['msg'];
            }else{
                $msg ="推送pos取消成功";   
            }
            $logMdl->write_log('reship@ome',$reship_id,$msg);
        }
    }

    /**
     * changegoods_confirm
     * @param mixed $reship_id ID
     * @return mixed 返回值
     */
    public function changegoods_confirm($reship_id){
        $reshipMdl = app::get('ome')->model('reship');
        $reship_detail = $reshipMdl->dump(array('reship_id'=>$reship_id,'shop_type'=>'pekon','source'=>'local','return_type'=>'change','is_check'=>'7'),'branch_id,return_id,reship_bn,source,return_type,shop_id,t_end');
        
        if(!$reship_detail) return true;
        $branch_id = $reship_detail['branch_id'];
        $branchs = $reshipMdl->db->selectrow("SELECT branch_id FROM sdb_ome_branch WHERE branch_id=".$branch_id." AND b_type=1");
        if(!$reship_detail || !$branchs){
            return true;
        }
        if($reship_detail &&  $branchs){
            $shop_id = $reship_detail['shop_id'];
            $storeMdl = app::get('o2o')->model('store');
            $stores = $storeMdl->db_dump(array('shop_id'=>$shop_id),'store_id');
            $store_id = $stores['store_id'];
            $channel_type = 'store';
            $channel_id = $store_id;
            $data = kernel::single('ome_receipt_reship')->reship_create(array('reship_id'=>$reship_id));
            $data['t_end'] = $reship_detail['t_end'];
            $product_serial = $this->getSerials($reship_id);
          

            if($product_serial){
                foreach($data['items'] as $k=>$v){
                    if ($product_serial[$v['bn']]){
                        $number = $v['defective_num']+$v['normal_num'];
                        $uniqueCodes = array_splice($product_serial[$v['bn']], 0, $number);
                        $data['items'][$k]['uniqueCodes'] = $uniqueCodes;
                    }
               
                }
            }
           
            $rs = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->reship_create($data);
            if($rs['rsp'] == 'succ'){
                if($rs['data']['wms_order_code']){
                    $saveData['out_iso_bn'] = $rs['data']['wms_order_code'];
                    $reshipMdl->update($saveData, array('reship_id'=>$reship_id));
                }
            
                
            }
            $logMdl = app::get('ome')->model('operation_log');
            if($rs['rsp'] == 'fail'){
                $msg = "失败. 原因:".$rs['msg'];
            }else{
                $msg ="成功";   
            }
            $logMdl->write_log('reship@ome',$reship_id,$msg);
        }
    }

    function getSerials($reship_id)
    {
        $product_serial = [];
        
        $serialMdl    = app::get('ome')->model('product_serial_history');
        $rows = $serialMdl->getList('bn,serial_number', array('bill_id'=>$reship_id,'bill_type'=>2), 0, -1);

        foreach ($rows as $row) {
            $product_serial[$row['bn']][] = $row['serial_number'];
        }

        return $product_serial;
    }

    /**
     * 判断原订单是售后产生的
     * @param  
     * @return 
     */
    public function getorders($order_id){
        $orderMdl = app::get('ome')->model('orders');
        $orders = $orderMdl->db_dump(array('order_id'=>$order_id),'source,createway');
        if($orders && $orders['createway'] =='after'){
            return false;
        }
        return true;
    }
}
