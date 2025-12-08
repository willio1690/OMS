<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_inventory
{
    static public $status = array(
        1 => '未确认',
        2 => '已确认',
        3 => '作废',
        4 => '关单',
       
    );
    
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){

        $branch_id  = $params['branch_id'];

        if(!$params['inventory_id']){
            $params['inventory_id'] = $this->create($params);

            if (!$params['inventory_id']) return array('rsp' => 'fail', 'msg'=>'盘点单创建失败');

        }
        kernel::database()->beginTransaction();
        $import_data = array(
            'sdfdata' => array(
                'inv_id'    => $params['inventory_id'],
                'branch_id' => $params['branch_id'],
                'products'  => array(),
                'mode'      => $params['mode'], 

            ),
        );

        // 盘点创建
        $products = array();
        foreach ($params['items'] as $key => $value) {
            $amount = $value['nums']*$value['price'];
            $tmp = array(
                'bm_id'              => $value['bm_id'],
                'actual_num'         => $value['totalqty'],
                'diff_stores'        => $value['nums'],
                'material_bn'        => $value['material_bn'],
                'material_name'      => $value['material_name'],
                'price'              => $value['price'],
                'amount'             => $amount,
                'pos_accounts_num'   => $value['pos_accounts_num'],
            );
            $products[] = $tmp;
        }
       

        $import_data['sdfdata']['products'] = $products;
      
      
        kernel::single('o2o_inventory_import')->run($cursor_id, $import_data);

        kernel::database()->commit();

        //完成
        $confirm_data = array(

            'inventory_id'  =>  $params['inventory_id'],
            'branch_id'     =>  $params['branch_id'],
            'inventory_bn'  =>  $params['inventory_bn'],
            'business_type' =>  $params['business_type'],
        );
       
        $this->finish($confirm_data);
        return array('rsp' => 'succ', 'data' => array('inventory_bn'=>$params['inventory_bn']));
    }



    /**
     * finish
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function finish($params){
        $invObj = app::get('o2o')->model('inventory');
        $inventory_id = $params['inventory_id'];
        $business_type = $params['business_type'];
        $logMdl = app::get('ome')->model('operation_log');
        if($business_type == 'day'){//日盘

            $update_inventory_arr = array(
                "confirm_op_id" => $oper['op_id'],
                "confirm_time"  => time(),
                "status"        => 2,
            );
            $filter_inventory_arr = array('inventory_id' => $inventory_id);
            $invObj->update($update_inventory_arr, $filter_inventory_arr);

        }

        if($business_type == 'month'){//月盘
            $items = $invObj->db->select("SELECT item_id FROM sdb_o2o_inventory_items WHERE inventory_id=".$inventory_id." AND short_over!=0");
          
            if(count($items)==0){
                $update_inventory_arr = array(
                   
                    'status'        => 4
                );
                $filter_inventory_arr = array('inventory_id' => $inventory_id);
                $invObj->update($update_inventory_arr, $filter_inventory_arr);
               
                $log_msg = '无差异更新状态为:无需确认';

                list($rs, $rsData) = kernel::single('pos_event_trigger_inventory')->check($inventory_id);

                if(!$rs){
                    $log_msg.='请求pos确认失败';
                }else{
                    $log_msg.='请求pos确认成功';
                }
                $logMdl->write_log('inventory_confirm@o2o', $inventory_id, $log_msg);
            }
    
        }
    }



    /**
     * confirm
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function confirm($params){
        $inventory_id = $params['inventory_id'];
        $oper = kernel::single('ome_func')->getDesktopUser();

        $itemObj = app::get('o2o')->model('inventory_items');
        $overage = $shortage = array();

        $item_list = $itemObj->getlist('bm_id,actual_num,short_over,item_id',array('inventory_id'=>$inventory_id));

        $bm_id_list = array_column($item_list, 'bm_id');

        $bm_list = app::get('material')->model('basic_material')->getList('bm_id,material_bn,material_name', array('bm_id' => $bm_id_list));
        $bm_list = array_column($bm_list, null, 'bm_id');

        $inventory = array(

            'inventory_bn'  =>  $params['inventory_bn'],
            'branch_id'     =>  $params['branch_id'],
            'inventory_id'  =>  $params['inventory_id'],
        );


        foreach($item_list as $v){
            $bm     = $bm_list[$v['bm_id']];
            if ($v['short_over'] ==0) continue;
            if($v['short_over']>0){
                $overage['items'][] = array(
                    'bn'                => $bm['material_bn'],
                    'normal_num'        => $v['short_over'],
                    'item_id'           => $v['item_id'],
                    'inventory_item_id' => $v['item_id'],
                );
            }else{

                $shortage['items'][] = array(
                    'bn'                => $bm['material_bn'],
                    'normal_num'        => abs($v['short_over']),
                    'item_id'           => $v['item_id'],
                    'inventory_item_id' => $v['item_id'],
                 );

            }

        }


        if (count($overage['items'])>0){
            $overage_arr = array_chunk($overage['items'], 500);
            foreach($overage_arr as $ov){
                $overage_data = array();

                $overage_data = $inventory;
                $overage_data['items'] = $ov;
                $overageLib = kernel::single('siso_receipt_iostock_overage');
               
                $overageLib->create($overage_data, $data, $msg);
                unset($data);
            }
        }
        if (count($shortage['items'])>0){
            $shortage_arr = array_chunk($shortage['items'], 500);

            foreach($shortage_arr as $sv){
                $shortage_data = array();
                $shortage_data = $inventory;
                $shortage_data['items'] = $sv;

                $shortageLib = kernel::single('siso_receipt_iostock_shortage');
                $shortageLib->create($shortage_data, $data, $msg);
                unset($data);
            }
            
        }

        $invObj = app::get('o2o')->model('inventory');
        //更新盘点主表状态为已确认
        $update_inventory_arr = array(
            "confirm_op_id" => $oper['op_id'],
            "confirm_time"  => time(),
            "status"        => 2,
        );
        $filter_inventory_arr = array('inventory_id' => $inventory_id);
        $invObj->update($update_inventory_arr, $filter_inventory_arr);

        
        return array('rsp' => 'succ', 'data' => array('inventory_bn'=>$params['inventory_bn']) );

    }

    /**
     * cancel
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function cancel($filter){
        $inventory_id = $filter['inventory_id'];
        $invObj = app::get('o2o')->model('inventory');
        $oper = kernel::single('ome_func')->getDesktopUser();
        //更新盘点主表状态为已确认
        $update_inventory_arr = array(
            'confirm_op_id' => $oper['op_id'],
            'confirm_time'  => time(),
            'status'        => 3,
        );
        $filter_inventory_arr = array('inventory_id' => $inventory_id);
        $invObj->update($update_inventory_arr, $filter_inventory_arr);

        return array('rsp' => 'succ', 'msg'=>'盘点单:'.$params['inventory_bn'].'取消成功' );
        
    }

    
    /**
     * 盘点任务单新建
     * @param  
     * @return
     */
    public function create($params){


        set_time_limit(0);
        @ini_set('memory_limit','512M');
        $inventory_type = $params['inventory_type'];
        $invMdl = app::get('o2o')->model('inventory');
        $branch_id = $params['branch_id'];
        $oper = kernel::single('ome_func')->getDesktopUser();

        $inv = array(
            'inventory_bn'   => $params['inventory_bn'],
            'inventory_type' => $inventory_type,
            'op_id'          => $oper['op_id'],
            'confirm_op_id'  => $oper['op_id'],
            'createtime'     => time(),
            'branch_id'      => $params['branch_id'],
            'business_type'  => $params['business_type'],
            'physics_id'     => $params['physics_id'],
            'apply_name'     => $params['apply_name'], 
            'confirm_name'   => $params['confirm_name'],
            'inventory_time' => $params['inventory_time'],    
        );

        if (!$invMdl->save($inv)) {
          

            return false;
        }

        return $inv['inventory_id'];
    }

}

?>