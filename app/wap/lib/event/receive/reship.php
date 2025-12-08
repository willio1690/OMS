<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 退换货事件处理类
 * 20170516
 * wangjianjun@shopex.cn
 */

class wap_event_receive_reship extends wap_event_response{

    /**
     * 退换货单创建事件
     * @param array $data
     */
    public function create($data){
        //创建
        $res = kernel::single('wap_receipt_reship')->create($data, $msg);
        if($res){
            return $this->send_succ();
        }else{
            return $this->send_error($msg);
        }
    }

    public function cancel($data){
       
        $res = kernel::single('wap_receipt_reship')->cancel($data, $msg);
        if($res){
            return $this->send_succ();
        }else{
            return $this->send_error($msg);
        }
    }
}

?>
