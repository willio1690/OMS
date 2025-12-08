<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_po {

    

    /**
     * push
     * @param mixed $bill_id ID
     * @return mixed 返回值
     */
    public function push($bill_id){

        $billMdl = app::get('vop')->model('bill');
        $bills = $this->getBills($bill_id);


        list($checkRs, $checkMsg) = $this->_checkPush($bills);

        if ($checkRs == false) {
            

            return [false, $checkMsg];
        }
        $errmsg = array();
        $this->process($bill_id,$errmsg);
        $po_sync_status = 1;
        $log_msg = '账单确认';
        if(count($errmsg)>0){
            $log_msg.='返回'.serialize($errmsg);
            $po_sync_status = 2;
        }
        app::get('ome')->model('operation_log')->write_log('bill@vop',$bill_id,$log_msg);

        $billMdl->update(array('po_sync_status'=>$po_sync_status),array('id'=>$bill_id));
        return [true];

    }


    /**
     * _checkPush
     * @param mixed $bills bills
     * @return mixed 返回值
     */
    public function _checkPush($bills){

        if($bills['sku_count']!=$bills['get_count'] || $bills['discount_count']!=$bills['get_discount_count'] || $bills['detail_count']!=$bills['get_detail_count']){

            return [false, '获取数量异常'];
        }


        if($bills['sync_status']!='2' || $bills['discount_sync_status']!='2' || $bills['detail_sync_status']!='2'){
            return [false, '同步状态异常'];
        }
        return [true];
    }

    /**
     * 获取Bills
     * @param mixed $bill_id ID
     * @return mixed 返回结果
     */
    public function getBills($bill_id){

        $billMdl = app::get('vop')->model('bill');

        $bills = $billMdl->dump($bill_id,'sku_count,get_count,discount_count,get_discount_count,detail_count,get_detail_count,sync_status,discount_sync_status,detail_sync_status');

        return $bills;
    }


    /**
     * 处理
     * @param mixed $bill_id ID
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function process($bill_id,&$errmsg)
    {
        
        $poMdl = app::get('vop')->model('po');

        $polist = $poMdl->getlist('po_id,po_no,bill_type,bill_type_name,amount,shop_id,bill_number',array('bill_id'=>$bill_id,'sync_status'=>array('0','2')));

        if(empty($polist)) return true;
        $o = kernel::single('financebase_data_bill_vop');
        $title = $o->getTitle();
        $title = array_values($title);
        $po_ids = array();
        $task_info = array();
        $task_info['queue_data']['title'] = $title;
        $task_info['queue_data']['shop_id'] = $polist[0]['shop_id'];
        $queueData['queue_data']['shop_type']  = 'vop';
        $task_info['queue_data']['data'] = array();
        $data = array();
        foreach($polist as $v){
            if(empty($v['po_no'])) continue;

            $v['signtime'] = $v['signtime'] ? $v['signtime'] : time();
            $data[] = array(
                '0' =>  $v['bill_type']=='reshipdiff' ? $v['bill_number'] : $v['po_no'],
                '1' =>  $v['bill_type_name'],
                '2' =>  sprintf('%.2f',$v['amount']),
                '3' =>  date('Y-m-d H:i:s',$v['signtime']),
                '4' =>  $v['bill_number'],
                '5' =>  $v['po_no'].'_'.$v['po_id'],
            );

            $po_ids[] = $v['po_id'];
        }
        
        $task_info['queue_data']['data'] = $data;
        
        
        $o->process(1,$task_info['queue_data'],$errmsg);
       
        
        //更新同步状态
        if($po_ids){
            $sync_status = 1;
            if(count($errmsg)>1){
                $sync_status = 2;
            }
            $poMdl->update(array('sync_status'=>$sync_status),array('po_id'=>$po_ids));
        }
        
        return true;
    }

}