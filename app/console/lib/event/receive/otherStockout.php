<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_event_receive_otherStockout extends console_event_response{

    /**
     * 
     * 其它出库事件处理
     * @param array $data
     */
    public function outStorage($data){
       
        $stockObj = kernel::single('console_receipt_stock');
        $io = '0';
        if ($data['io_source'] == 'selfwms'){//自有仓储不作处理
            //return $this->send_succ();
        }
       
        $io_status = $data['io_status'];
        if (!$stockObj->checkValid($data['io_bn'],$io_status,$msg)){
            return $this->send_error($msg);
        }

        switch($io_status){
            case 'PARTIN':
            case 'FINISH':
                $result = $stockObj->do_save($data,$io,$msg);
            break;
            case 'FAILED':
            case 'CANCEL':
            case 'CLOSE':
                $result = $stockObj->cancel($data,$io);
                break;
            default:
                return $this->send_succ('无法识别的操作指令');
                break;
        }
        if ($result){
            return $this->send_succ('出库处理成功');
        }else{
            return $this->send_error($msg,'',$data);
        }
    }
    

}