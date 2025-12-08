<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_receipt_dlyitemsstoragelife{

    /**
     *
     * 唯一码发货历史记录
     * @param Array $sdf 
     */
    public function consign($params, &$msg){
        //校验传入参数
        if(!$this->checkParams($params,$msg)){
            return false;
        }

        $delivery_id = $params['delivery_id'];
        $delivery_bn = $params['delivery_bn'];
        $branch_id = $params['branch_id'];
        $out_storagelife = $params['out_storagelife'];
        $nowTime = time();
        $opinfo = kernel::single('ome_func')->getDesktopUser();

        $dlyItemsStoragelifeObj    = app::get('ome')->model('delivery_items_storagelife');
        $prdStoragelifeHistoryObj    = app::get('ome')->model('product_storagelife_history');

        //delivery items serial info
        $serial_sql = 'insert into sdb_ome_delivery_items_storagelife(delivery_id,bm_id,bn,product_name,expire_bn,number,status) values ';
        //io serial history
        $log_sql = 'insert into sdb_ome_product_storagelife_history(branch_id,bn,product_name,act_type,act_time,act_owner,bill_type,bill_no,expire_bn,number) values ';

        foreach($out_storagelife as $storagelife){
            //convert params
            $storagelife_vals[] = "('".$delivery_id."','".$storagelife['bm_id']."','".$storagelife['material_bn']."','".$storagelife['product_name']."','".$storagelife['expire_bn']."','".$storagelife['nums']."','1')";
            //history log
            $log_vals[] = "('".$branch_id."','".$storagelife['material_bn']."','".$storagelife['product_name']."','1',".$nowTime.",'".$opinfo['op_id']."','1','".$delivery_bn."','".$storagelife['expire_bn']."','".$storagelife['nums']."')";
        }

        $sql1 = $serial_sql.implode(',',$storagelife_vals);
        if($dlyItemsStoragelifeObj->db->exec($sql1)){
            $sql2 = $log_sql.implode(',',$log_vals);
            if($prdStoragelifeHistoryObj->db->exec($sql2)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    private function checkParams($params,&$msg){
        return true;
    }

    /**
     *
     * 唯一码退货历史记录
     * @param Array $sdf 
     */
    public function returnProduct($history_storagelife, &$msg){
        //校验传入参数
        if(!$this->checkReturnParams($history_storagelife,$msg)){
            return false;
        }

        $nowTime = time();
        $opinfo = kernel::single('ome_func')->getDesktopUser();

        $prdStoragelifeHistoryObj    = app::get('ome')->model('product_storagelife_history');

        //io serial history
        $log_sql = 'insert into sdb_ome_product_storagelife_history(branch_id,bn,product_name,act_type,act_time,act_owner,bill_type,bill_no,expire_bn,number) values ';

        foreach($history_storagelife as $storagelife){
            //history log
            $log_vals[] = "('".$storagelife['branch_id']."','".$storagelife['bn']."','".$storagelife['product_name']."','2',".$nowTime.",'".$opinfo['op_id']."','2','".$storagelife['reship_bn']."','".$storagelife['expire_bn']."','".$storagelife['nums']."')";
        }

        $sql = $log_sql.implode(',',$log_vals);
        if($prdStoragelifeHistoryObj->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }

    private function checkReturnParams($params,&$msg){
        return true;
    }

}