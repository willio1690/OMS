<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_event_receive_purchase extends console_event_response{

    /**
     * 
     * 采购入库事件处理
     * @param array $datainStorage
     */
    public function inStorage($data){

        $purchaseObj = kernel::single('console_receipt_purchase');
        if ($data['io_source'] == 'selfwms'){#自有仓储不作处理
            return $this->send_succ();
        }
      
        $io_status = $data['io_status'];
        //验证采购单当前状态是否有效
        $msg = '';
        $purchase = $purchaseObj->checkValid($data['io_bn'],$io_status,$msg);
        if (!$purchase){
            return $this->send_error($msg);

        }
        
        switch($io_status){
            case 'PARTIN':
            case 'FINISH':
                //参数转换
               
                $result = $purchaseObj->update($data,$msg);

            break;
            case 'FAILED':
            case 'CANCEL':
            case 'CLOSE':
                
                $result = $purchaseObj->cancel($data['io_bn']);
                break;
            default:
                return $this->send_succ('无法识别的操作指令');
                break;
        }
        if ($result){
            return $this->send_succ('采购入库操作成功');
        }else{
            $msg = $msg!='' ? $msg :'更新失败';
            return $this->send_error($msg);
        }
        
        
    }


   
}