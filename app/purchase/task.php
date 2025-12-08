<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_task{

    function post_install(){

        //唯品会仓库定义
        $purchaseLib    = kernel::single('purchase_purchase_order');
        $branch_list    = $purchaseLib->initWarehouse();
        
        if($branch_list)
        {
            $warehouseObj    = app::get('purchase')->model('warehouse');
            foreach ($branch_list as $key => $val)
            {
                $tempData    = $warehouseObj->dump(array('branch_bn'=>$val['branch_bn']), '*');
                if(empty($tempData))
                {
                    $data    = array('branch_bn'=>$val['branch_bn'], 'branch_name'=>$val['branch_name']);
                    $warehouseObj->save($data);
                }
            }
        }
    }

    function install_options(){
        return array(
                
            );
    }

}
