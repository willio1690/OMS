<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_receipt_dlyitemsserial{

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
        $out_serial = $params['out_serial'];
        $nowTime = time();
        $opinfo = kernel::single('ome_func')->getDesktopUser();

        $dlyItemsSerialObj    = app::get('ome')->model('delivery_items_serial');
        $prdSerialHistoryObj    = app::get('ome')->model('product_serial_history');

        //delivery items serial info
        $serial_sql = 'insert into sdb_ome_delivery_items_serial(delivery_id,product_id,bn,product_name,serial_number,status) values ';
        //io serial history
        $log_sql = 'insert into sdb_ome_product_serial_history(branch_id,bn,product_name,act_type,act_time,act_owner,bill_type,bill_no,serial_number) values ';

        foreach($out_serial as $serial){
            //convert params
            $serial_vals[] = "('".$delivery_id."','".$serial['product_id']."','".$serial['bn']."','".$serial['product_name']."','".$serial['serial_number']."','1')";
            //history log
            $log_vals[] = "('".$branch_id."','".$serial['bn']."','".$serial['product_name']."','1',".$nowTime.",'".$opinfo['op_id']."','1','".$delivery_bn."','".$serial['serial_number']."')";
        }

        $sql1 = $serial_sql.implode(',',$serial_vals);
        if($dlyItemsSerialObj->db->exec($sql1)){
            $sql2 = $log_sql.implode(',',$log_vals);
            if($prdSerialHistoryObj->db->exec($sql2)){
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
    public function returnProduct($history_serial, &$msg){
        //校验传入参数
        if(!$this->checkReturnParams($history_serial,$msg)){
            return false;
        }

        $nowTime = time();
        $opinfo = kernel::single('ome_func')->getDesktopUser();

        $prdSerialHistoryObj    = app::get('ome')->model('product_serial_history');

        //io serial history
        $log_sql = 'insert into sdb_ome_product_serial_history(branch_id,bn,product_name,act_type,act_time,act_owner,bill_type,bill_no,serial_number) values ';

        foreach($history_serial as $serial){
            //history log
            $log_vals[] = "('".$serial['branch_id']."','".$serial['bn']."','".$serial['product_name']."','2',".$nowTime.",'".$opinfo['op_id']."','2','".$serial['reship_bn']."','".$serial['serial_number']."')";
        }

        $sql = $log_sql.implode(',',$log_vals);
        if($prdSerialHistoryObj->db->exec($sql)){
            return true;
        }else{
            return false;
        }
    }

    private function checkReturnParams($params,&$msg){
        return true;
    }

}