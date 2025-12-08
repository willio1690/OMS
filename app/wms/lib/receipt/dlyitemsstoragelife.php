<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_receipt_dlyitemsstoragelife{

    /**
     *
     * 发货单明细对应的批次明细生成方法
     * @param array $sdf 批次信息
     */
    public function generate($sdf,&$msg){
        //校验传入参数
        if(!$this->checkParams($sdf,$msg)){
            return false;
        }

        $dlyItemsSLObj = app::get('wms')->model('delivery_items_storage_life');

        //事务开启
        //$dlyItemsSLObj->db->beginTransaction();

        //循环每行批次明细进行保存
        foreach($sdf['items'] as $item){
            foreach($item['expire_bns_info'] as $expire_bn_info){
                $data = array();

                $data['item_id']  = $item['item_id'];
                $data['bm_id']  = $item['bm_id'];
                $data['bn']  = $item['bn'];
                $data['product_name']  = $item['product_name'];
                $data['delivery_id']  = $sdf['bill_id'];
                $data['expire_bn']  = $expire_bn_info['expire_bn'];
                $data['number']  = $expire_bn_info['nums'];

                if(!$dlyItemsSLObj->save($data)){
                    //$dlyItemsSLObj->db->rollBack();
                    return false;
                }
                unset($data);
            }
        }

        //$dlyItemsSLObj->db->commit();
        return true;
    }

    private function checkParams($params,&$msg){
        return true;
    }

    /**
     *
     * 保质期批次退入数据处理
     * @param Array $sdf 
     */
    public function returnProduct($sdf, &$msg, &$return_storagelife){
        //校验传入参数
        if(!$this->checkReturnParams($sdf,$msg)){
            return false;
        }

        //return storagelife in branch
        $storageLifeReceiptLib = kernel::single('material_receipt_storagelife');
        //params is array
        $storagelife_info[0] = $sdf;
        $msg = [];
        $rs = $storageLifeReceiptLib->generate($storagelife_info, $msg);
        if(!$rs){
            return false;
        }

        $reshipItemsStorageLifeObj    = app::get('ome')->model('reship_items_storagelife');
        $rs = $reshipItemsStorageLifeObj->insert($sdf);
        if($rs){
            //return quote 
            $return_storagelife = array('branch_id' => $sdf['branch_id'], 'bn' => $sdf['material_bn'], 'product_name' => $sdf['product_name'], 'reship_bn' => $sdf['reship_bn'], 'expire_bn' => $sdf['expire_bn'], 'nums' => $sdf['nums'],'product_id'=>$sdf['product_id'],'reship_id'=>$sdf['reship_id']);

            return true;
        }else{
            return false;
        }
    }

    private function checkReturnParams(&$params,&$msg){
        //check required params
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');

        $filter = array('expire_bn' => $params['expire_bn'], 'branch_id' => $params['old_branch_id']);
        if(isset($params['bm_id'])){
            $filter['bm_id'] = $params['bm_id'];
        }

        if(isset($params['material_bn'])){
            $filter['material_bn'] = $params['material_bn'];
        }

        $storagelife_info = $basicMaterialStorageLifeObj->dump($filter,'bm_id,material_bn,production_date,guarantee_period,date_type,warn_day,quit_day');
        if($storagelife_info){
            switch($storagelife_info['date_type']){
                case '1':
                    $storagelife_info['date_type'] = 'day';
                    break;
                case '2':
                    $storagelife_info['date_type'] = 'month';
                    break;
                case '3':
                    $storagelife_info['date_type'] = 'year';
                    break;
            }
            $storagelife_info['production_date'] = date('Y-m-d', $storagelife_info['production_date']);
            $params = array_merge($params, $storagelife_info);
        }
        unset($storagelife_info);

        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialInfo = $basicMaterialObj->dump(array('bm_id' => $params['bm_id']),'material_name');
        if($basicMaterialInfo){
            $params['product_name'] = $basicMaterialInfo['material_name'];
        }

        $params['in_num'] = $params['nums'];
        $params['number'] = $params['nums'];
        $params['product_id'] = $params['bm_id'];
        $params['bn'] = $params['material_bn'];
        $params['reship_id'] = $params['bill_id'];
        $params['reship_bn'] = $params['bill_bn'];

        return true;
    }
}