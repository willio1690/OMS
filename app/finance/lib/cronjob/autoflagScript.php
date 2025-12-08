<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_cronjob_autoflagScript{
    /*自动核销账单*/
    function autoflag_queue(){
        /*
        $last_autoflag_queue_run_time = app::get('finance')->getConf("last_autoflag_queue_run_time");
        if(!$last_autoflag_queue_run_time) $last_autoflag_queue_run_time = 0;
        app::get('finance')->setConf("last_autoflag_queue_run_time",time());
        if((time()-$last_autoflag_queue_run_time)<3600) return ;
        */
        $ar = app::get("finance")->model('ar');
        $araData = $ar->db->select("SELECT a.ar_id,a.ar_bn,a.money as ar_money,a.confirm_money as ar_confirm_money,a.unconfirm_money as ar_unconfirm_money,b.bill_id, b.bill_bn,b.money as bill_money,b.confirm_money as bill_confirm_money,b.unconfirm_money as bill_unconfirm_money,b.trade_time as bill_trade_time from `sdb_finance_ar` as a left join `sdb_finance_bill` as b on a.order_bn=b.order_bn and a.money=b.money where a.auto_flag=0 and a.status=0 and a.charge_status=1 and b.charge_status=1 and b.fee_type_id=1");
        foreach($araData as $ak=>$aval){
            $time = time();
            $ar->db->beginTransaction();
            if($aval['ar_id']&&$aval['bill_id']){
                $ar_update['auto_flag'] = $bill_update['auto_flag'] = 1;
                $ar_update['status'] = $bill_update['status'] = 2;
                $ar_update['unconfirm_money'] = $bill_update['unconfirm_money'] = 0;
                $ar_update['confirm_money']  = $aval['ar_money'];
                $bill_update['confirm_money']  = $aval['bill_money'];
                $ar_update['verification_time'] = $bill_update['verification_time']= $time;
                $ar_filter['ar_id'] = $aval['ar_id'];
                $bill_filter['bill_id'] = $aval['bill_id'];
                $ar_rs = $this->auto_ar_status($ar_update,$ar_filter);
                $bill_rs = $this->auto_bill_status($bill_update,$bill_filter);
                if(!$ar_rs || !$bill_rs){
                        $ar->db->rollBack();
                        continue ;
                    }
                //todo 日志
                $log_data = $aval;
            }
            elseif($aval['ar_id']){
                $ar_update['auto_flag'] = 1;
                $ar_filter['ar_id'] = $aval['ar_id'];
                $ar_rs = $this->auto_ar_status($ar_update,$ar_filter);
            }

            $ar->db->commit();
            if($log_data){
                $this->write_auto_log($aval);
            }
        }

       $billaData = $ar->db->select("SELECT a.ar_id,a.ar_bn,a.money as ar_money,a.confirm_money as ar_confirm_money,a.unconfirm_money as ar_unconfirm_money,b.bill_id, b.bill_bn,b.money as bill_money,b.confirm_money as bill_confirm_money,b.unconfirm_money as bill_unconfirm_money,b.trade_time as bill_trade_time from `sdb_finance_bill` as b left join `sdb_finance_ar` as a on a.order_bn=b.order_bn and a.money=b.money where b.auto_flag=0 and b.status=0 and a.charge_status=1 and b.charge_status=1 and b.fee_type_id=1");
        foreach($billaData as $bk=>$bval){
            $time = time();
            $ar->db->beginTransaction();
            if($bval['ar_id']&&$bval['bill_id']){
                $ar_update['auto_flag'] = $bill_update['auto_flag'] = 1;
                $ar_update['status'] = $bill_update['status'] = 2;
                $ar_update['verification_time'] = $bill_update['verification_time']= $time;
                $ar_update['unconfirm_money'] = $bill_update['unconfirm_money'] = 0;
                $ar_update['confirm_money'] = $bval['ar_money'];
                $bill_update['confirm_money'] = $bval['bill_money'];
                $ar_filter['ar_id'] = $bval['ar_id'];
                $bill_filter['bill_id'] = $bval['bill_id'];
                $ar_rs = $this->auto_ar_status($ar_update,$ar_filter);
                $bill_rs = $this->auto_bill_status($bill_update,$bill_filter);
                //todo 日志
                if(!$ar_rs || !$bill_rs){
                        $ar->db->rollBack();
                        continue ;
                }
                $blog_data = $bval;
            }
            elseif($bval['bill_id']){
                $bill_update['auto_flag'] = 1;
                $bill_filter['bill_id'] = $bval['bill_id'];
                $ar_rs = $this->auto_bill_status($bill_update,$bill_filter);
            }
            $ar->db->commit();
            if($blog_data){
                $this->write_auto_log($blog_data);
            }
        }
    }

    function auto_ar_status($update_data=array(),$filter=array()){
        if(!$update_data || !$filter) return false;
        $ar = app::get("finance")->model('ar');
        return $ar->update($update_data,$filter);
    }

    function auto_bill_status($update_data=array(),$filter=array()){
        if(!$update_data || !$filter) return false;
        $bill = app::get("finance")->model('bill');
        return $bill->update($update_data,$filter);
    }

    function write_auto_log($aData)
    {
        $bill_lib = kernel::single("finance_bill");
        $bill_data['bill_id'] = $aData['bill_id'];
        $bill_data['bill_bn'] = $aData['bill_bn'];
        $bill_data['money'] = $aData['bill_money'];
        $bill_data['unconfirm_money'] = $aData['bill_unconfirm_money']; 
        $bill_data['confirm_money'] = $aData['bill_confirm_money']; 
        $ar_data['ar_id'] = $aData['ar_id'];
        $ar_data['ar_bn'] = $aData['ar_bn'];
        $ar_data['money'] = $aData['ar_money'];
        $ar_data['unconfirm_money'] = $aData['ar_unconfirm_money']; 
        $ar_data['confirm_money'] = $aData['ar_confirm_money'];
        $bdata[] = $bill_data;
        $adata[] = $ar_data;
        $bill_lib->write_verification_log($bdata,$adata,$aData['bill_trade_time'],1);  
    }
}
    