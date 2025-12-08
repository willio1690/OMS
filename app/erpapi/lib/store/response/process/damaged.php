<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_damaged
{
    
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){
        $branch_id = $params['branch_id'];
        $items     = $params['items'];

        $bpModel       = app::get('ome')->model('branch_product');

        foreach ($items as $item) {
            $ps = $bpModel->dump(array('branch_id' => $branch_id, 'product_id' => $item['product_id']));
            if (!$ps) {
                return array('rsp' => 'fail', 'msg' => '所选门店与所选物料无关联。');
            }

            $store        = $ps['store'];
            $store_freeze = $ps['store_freeze'] + $item['nums'];
            if ($store < $store_freeze) {
                return array('rsp' => 'fail', 'msg' => $item['bn'].":库存数[$store]小于冻结数[$store_freeze]");
            }
        }

        $trx = kernel::database()->beginTransaction();

        $optMdl       = app::get('ome')->model('operation_log');
        $atfFreezeLib = kernel::single('console_stock_artificial_freeze');

        $branch = app::get('ome')->model('branch')->dump(array('branch_id' => $branch_id, 'check_permission' => 'false'), 'branch_id,branch_bn');

        $group_name = $branch['branch_bn'] . '-' . date('YmdHis');

        $k = 0;
        foreach ($items as $key => $item) {
            $artificial = array(
                "branch_id"  => $branch_id,
                "bm_id"      => $item['product_id'],
                "freeze_num" => $item['nums'],
                "reason"     => '报残',
                'op_id'      => '16777215',
                "group_name" => $group_name,
                "original_type" => 'damaged_add',
                "bn"         => $item['bn'],
            );
            //如果数量是多件平铺残次登记记录
            if ($item['nums'] > 1) {
                for ($i = 0; $i < $item['nums']; $i++) {
                    $artificial['freeze_num'] = 1;
                    $bmsaf_id = $atfFreezeLib->insert_freeze_data($artificial);

                    if (!$bmsaf_id) {
                        kernel::database()->rollBack();
                        return array('rsp' => 'fail', 'msg' => kernel::database()->errorinfo());
                    }
            
                    $optMdl->write_log('add_artificial_freeze@ome', $bmsaf_id, '门店报残');
            
                    $items[$k]['obj_id'] = $bmsaf_id;
                    $items[$k]['num']    = 1;
                    $items[$k]['bn']    = $item['bn'];
                    $items[$k]['nums']    = 1;
                    $items[$k]['product_id']    = $item['product_id'];
                    $k++;
                }
            } else {

                $bmsaf_id = $atfFreezeLib->insert_freeze_data($artificial);
    
                if (!$bmsaf_id) {
                    kernel::database()->rollBack();
                    return array('rsp' => 'fail', 'msg' => kernel::database()->errorinfo());
                }
    
                $optMdl->write_log('add_artificial_freeze@ome', $bmsaf_id, '门店报残');
    
                $items[$k]['obj_id'] = $bmsaf_id;
                $items[$k]['num']    = $item['nums'];
                $items[$k]['bn']    = $item['bn'];
                $items[$k]['nums']    = $item['nums'];
                $items[$k]['product_id']    = $item['product_id'];
                $k++;
            }
        }

        $m = array(
            'branch_id' => $branch_id,
            'items'     => $items,
            'node_type' => 'artificialFreeze',
        );
       
        $storeManageLib = kernel::single('ome_store_manage');
        $storeManageLib->loadBranch($m);
        $m['params'] = $m;
        $processResult = $storeManageLib->processBranchStore($m, $err_msg);
        if (!$processResult) {

            kernel::database()->rollBack();
            return array("rsp" => 'fail', 'msg' => $err_msg);
        }

        kernel::database()->commit($trx);

        return array('rsp' => 'succ', 'data' => array(),'msg'=>'残次登记成功');
    }

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter){
        $mdl_artificial_freeze = app::get('material')->model('basic_material_stock_artificial_freeze');
        $bmMdl     = app::get('material')->model('basic_material');
       
        set_time_limit(0);
        @ini_set('memory_limit','512M');
        
        $material = array();
       
        if ($filter['bn']) {
            $material['material_bn'] = $filter['bn'];
            unset($filter['bn']);
        }
        

        if (!empty($filter)) {
            $filter = $mdl_artificial_freeze->_filter($filter,'a');
        }
        
        if (!empty($material)) {
            $material = $bmMdl->_filter($material,'b');
          
            $filter .=' AND ' . $material;
        }

        $sql = 'SELECT a.* FROM `sdb_material_basic_material_stock_artificial_freeze` as a left join `sdb_material_basic_material` as b on a.bm_id = b.bm_id WHERE '.$filter . ' GROUP BY a.bm_id ORDER BY a.freeze_time DESC ';

        $data = count(kernel::database()->select($sql));
        return array('rsp' => 'succ', 'data' => array('count' => $data));
    }


   
    /**
     * 创建
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function create($params){


        $approMdl     = app::get('taoguanallocate')->model('appropriation');
        $approItemMdl = app::get('taoguanallocate')->model('appropriation_items');
     
        kernel::database()->beginTransaction();

        $appropriation_no = kernel::single('console_receipt_allocate')->create($params,$msg);

        if (!$appropriation_no) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => $msg);
        }

       
        $mdl_artificial_freeze = app::get('material')->model('basic_material_stock_artificial_freeze');
        $product_id = array_column($params['items'],'product_id');
       

        $mdl_artificial_freeze->update(['appropriation_no'=>$appropriation_no,'original_bn'=>$appropriation_no],['branch_id'=>$params['branch_id'],'bm_id|in'=>$product_id,'appropriation_no'=>0,'original_type'=>'damaged_add']);

        kernel::database()->commit();
        $data = array('appro'=>$appropriation_no);
        return array('rsp' => 'succ', 'data' => $data);
    }
}

?>