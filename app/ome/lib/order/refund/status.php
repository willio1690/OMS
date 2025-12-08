<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/9/27 18:24:13
 * @describe: 退款单状态
 * ============================
 */
class ome_order_refund_status {
    private $__statusNotifyRead;  
    private $__statusNotifyWrite;  

    /**
     * fetch
     * @param mixed $tid ID
     * @param mixed $nodeId ID
     * @param mixed $shopId ID
     * @return mixed 返回值
     */

    public function fetch($tid, $nodeId, $shopId){
        if(!$this->__statusNotifyRead) {
            try{
                if(defined('TMC_REFUND_READ_MODE')) {
                    if(TMC_REFUND_READ_MODE == 'redis') {
                        $this->__statusNotifyRead = kernel::single('ome_order_refund_status_redis');
                    } elseif (TMC_REFUND_READ_MODE == 'mysql') {
                        $this->__statusNotifyRead = kernel::single('ome_order_refund_status_mysql');
                    }
                }
                if(empty($this->__statusNotifyRead)) {
                    $this->__statusNotifyRead = kernel::single('ome_order_refund_status_api');
                }
            } catch(Exception $e) {
                return [false, ['msg'=>$e->getMessage()]];
            }
        }
        return $this->__statusNotifyRead->fetch($tid, $nodeId, $shopId);
    }

    /**
     * store
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function store($sdf){
        if(!$this->__statusNotifyWrite) {
            try{
                if(defined('TMC_REFUND_WRITE_MODE')) {
                    if(TMC_REFUND_WRITE_MODE == 'redis') {
                        $this->__statusNotifyWrite = kernel::single('ome_order_refund_status_redis');
                    } elseif (TMC_REFUND_WRITE_MODE == 'mysql') {
                        $this->__statusNotifyWrite = kernel::single('ome_order_refund_status_mysql');
                    }
                }
                if(empty($this->__statusNotifyWrite)) {
                    $this->__statusNotifyWrite = kernel::single('ome_order_refund_status_api');
                }
            } catch(Exception $e) {
                return [false, ['msg'=>$e->getMessage()]];
            }
        }
        return $this->__statusNotifyWrite->store($sdf);
    }
}