<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @description 库存管理接口类
 * @access public
 */
interface  ome_store_manage_interface{

    public function addDly($params, &$err_msg);

    public function cancelDly($params, &$err_msg);

    public function consignDly($params, &$err_msg);

    public function pauseOrd($params, &$err_msg);

    public function renewOrd($params, &$err_msg);
    
    public function checkChangeReship($params, &$err_msg);
    
    public function refuseChangeReship($params, &$err_msg);
    
    public function confirmReshipReturn($params, &$err_msg);
    
    public function confirmReshipChange($params, &$err_msg);
    
    public function reshipReturnRefuseChange($params, &$err_msg);
    
    public function editChangeToReturn($params, &$err_msg);
    
    public function checkReturned($params, &$err_msg);
    
    public function finishReturned($params, &$err_msg);
    
    public function cancelReturned($params, &$err_msg);
    
    public function checkStockout($params, &$err_msg);
    
    public function finishStockout($params, &$err_msg);
    
    public function saveStockdump($params, &$err_msg);
    
    public function finishStockdump($params, &$err_msg);
    
    public function checkVopstockout($params, &$err_msg);
    
    public function finishVopstockout($params, &$err_msg);
}