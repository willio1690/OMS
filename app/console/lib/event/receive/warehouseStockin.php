<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 转仓入库
 * 20180529 by wangjianjun
 */
class console_event_receive_warehouseStockin extends console_event_response{
    
    /**
     * 
     * 转仓入库事件处理
     * @param array $data
     */
    public function inStorage($data){
        $stockObj = kernel::single('console_receipt_stock');
        $io_status = $data['io_status'];
        
        #检查状态是否可操作
        if (!$stockObj->checkValidWarehouse($data['io_bn'],$io_status,$msg)){
            return $this->send_error($msg);
        }
        
        switch($io_status){
            case 'PARTIN':
            case 'FINISH':
                $result = $stockObj->do_save_warehouse($data,$msg);
                break;
            case 'FAILED':
            case 'CANCEL':
            case 'CLOSE':
                $result = $stockObj->cancel_warehouse($data);
                break;
            default:
                return $this->send_succ('无法识别的操作指令');
                break;
        }
        
        if ($result){
            return $this->send_succ('入库请求操作成功');
        }else{
            return $this->send_error('入库请求操作失败');
        }
    }
    
}