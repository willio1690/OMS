<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 按照已经拆分出来的销售物料进行库存回写
 */
class ome_autotask_task_inventorydepthproduct
{
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '1024M');
        $sdf = $params['sdf'];
        if($sdf['delivery_mode'] == 'self') {
            $this->stockSelf($sdf);
        }
        if($sdf['delivery_mode'] == 'shopyjdf') {
            $this->stockYjdf($sdf);
        }
        return true;
    }

    protected function stockSelf($params){
        $salesMaterialObj = app::get('material')->model('sales_material');
        $sm_ids = $params["sm_ids"];
        $shop_ids = $params["shop_ids"];
        if(!empty($sm_ids)){
            $products = $salesMaterialObj->getList('sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id',array('sm_id'=>$sm_ids));
            if(!empty($products)){
                kernel::single('inventorydepth_logic_stock')->set_readStoreLastmodify($params['read_store_lastmodify'])->do_sync_products_stock($products, $shop_ids);
            }
        }
    }

    protected function stockYjdf($params) {
        $salesMaterialObj = app::get('dealer')->model('sales_material');
        $sm_ids = $params["sm_ids"];
        $shop_ids = $params["shop_ids"];
        if(!empty($sm_ids)){
            $products = $salesMaterialObj->getList('sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id',array('sm_id'=>$sm_ids));
            if(!empty($products)){
                kernel::single('inventorydepth_logic_stock')->set_readStoreLastmodify($params['read_store_lastmodify'])->do_sync_products_stock($products, $shop_ids);
            }
        }
    }
}