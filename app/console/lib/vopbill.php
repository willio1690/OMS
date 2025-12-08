<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */



/**
 * sunjing@shopex.cn
 */
class console_vopbill {

    public function getDetail($bill_id){

        $detailMdl = app::get('console')->model('vopbill_detail');
        $sql = "SELECT sum(if(amount=0,targetamount,amount))  as detail_amount FROM `sdb_console_vopbill_detail` WHERE bill_id=".$bill_id;

        $details = $detailMdl->db->selectrow($sql);

        return $details;
    }

   

    /**
     * 获取Bills
     * @param mixed $bill_id ID
     * @return mixed 返回结果
     */
    public function getBills($bill_id){
        $amountMdl = app::get('console')->model('vopbill_amount');
        $amounts = $amountMdl->db->select("SELECT detail_line_type,sum(qty) as qty,sum(amount) as amount,sum(total_amount) as total_amount FROM sdb_console_vopbill_amount WHERE bill_id=".$bill_id." group by detail_line_type");

        $amounts = array_column($amounts, null,'detail_line_type');
        return $amounts;

    }


    

    /**
     * 获取BillsAmount
     * @param mixed $bill_id ID
     * @return mixed 返回结果
     */
    public function getBillsAmount($bill_id){
        $amountMdl = app::get('console')->model('vopbill_amount');
        $amounts = $amountMdl->db->selectrow("SELECT sum(amount),sum(discount_amount) as sum_discount_amount,sum(adjust_amount) as sum_adjust_amount,sum(total_amount) as sum_total_amount FROM sdb_console_vopbill_amount WHERE bill_id=".$bill_id."");
        $amounts['sum_total_amount'] = sprintf('%.2f',$amounts['sum_total_amount']);
        return $amounts;
    }


    

    
}