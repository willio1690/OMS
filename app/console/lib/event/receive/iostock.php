<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class console_event_receive_iostock{

    /**
     * 入库单结果回传
     */
    public function stockin_result($sdf='')
    {
        $type = $sdf['io_type'];
        
        switch($type){
            case 'PURCHASE':#采购
                $wms_class = 'console_event_receive_purchase';
                $wms_method = 'inStorage';
                break;
            case 'ALLCOATE':#调拨
                $wms_class = 'console_event_receive_transferStockIn';
                $wms_method = 'inStorage';
                break;
            case 'WAREHOUSE':#转仓
                $wms_class = 'console_event_receive_warehouseStockin';
                $wms_method = 'inStorage';
                break;
            default:#其它
                $wms_class = 'console_event_receive_otherStockin';
                $wms_method = 'inStorage';
                break;
        }
        
        return kernel::single($wms_class)->$wms_method($sdf);
    }
    
    /**
     *  退货单结果回传
     */
    public function reship_result($sdf)
    {
       return kernel::single('console_event_receive_reship')->updateStatus($sdf);
    }

    /**
     * 出库单结果回传
     */
    public function stockout_result($sdf='')
    {
        $type = $sdf['io_type'];
        
        switch($type){
            case 'PURCHASE_RETURN':#采购
                $wms_class = 'console_event_receive_purchasereturn';
                $wms_method = 'outStorage';
                break;
            case 'ALLCOATE':#调拨
                $wms_class = 'console_event_receive_transferStockOut';
                $wms_method = 'outStorage';
                break;
            case 'VOPSTOCKOUT':#唯品会出库
                $wms_class = 'console_event_receive_vopstockout';
                $wms_method = 'outStorage';
                break;
            default:#其它
                $wms_class = 'console_event_receive_otherStockout';
                $wms_method = 'outStorage';
                break;
        }
        
        return kernel::single($wms_class)->$wms_method($sdf);
    }

    /**
     * 盘点单结果回传
     */
    public function inventory_result($sdf)
    {
        return kernel::single('console_event_receive_inventory')->create($sdf);
    }

    /**
     * 库存对账状态结果回传
     */
    public function stock_result($params=array())
    {
        return kernel::single('console_event_receive_stockaccount')->create($params);
    }

    /**
     * 库内转储结果回传
     */
    public function stockdump_result($sdf)
    {
        return kernel::single('console_event_receive_stockdump')->ioStorage($sdf);
    }
}
