<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 转储单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_transferorder
{
    /**
     * 转储单
     *
     * @param Array $params=array(
     *                  'order_code'=>@调拨单号@
     *                  'out_order_code'=>@出库单号@
     *                  'in_order_code'=>@入库单号@
     *                  'out_confirm_time'=>@确认出库时间@
     *                  'in_confirm_time'=>@确认入库时间@
     *                  'create_time'=>@创建时间@
     *                  'from_warehouse_code'=>@调拨出库仓编码@
     *                  'to_warehouse_code'=>@调拨入库仓编码@
     *                  'owner_code'=>@货主编码@
     *                  'erp_order_code'=>@ERP的调拨单号@
     *                  'orderStatus'=>@创建时间@ FINISH|FAILED|CANCEL|CLOSE
     *                  'extendProps'=>@@
     *                  'item'=>array(
     *                      'product_bn'=>@货号@
     *                      'order_code'=>@调拨单号@
     *                      'normal_num'=>@正常品数量@
     *                      'defective_num'=>@残次品数量@
     *                      'warehouse'=>@仓库编码@
     *
     *                      'sc_item_code'=>@货品编码@
     *                      'out_count'=>@实际出库数量@
     *                      'in_count'=>@实际入库数量@
     *                      'plan_count'=>@计划调拨数量@
     *                  )
     *  
     *              )
     * @return void
     * @author 
     **/
    public function update($params)
    {
        $result = kernel::single('console_event_receive_transferorder')->ioStorage($params);
        // 报警
        if($result['rsp'] == 'fail' && $params['status'] == 'FINISH') {
            kernel::single('monitor_event_notify')->addNotify('wms_transferorder_finish', [
                'stockdump_bn' => $params['stockdump_bn'],
                'errmsg'      => $result['msg'],
            ]);
        }
        return $result;
    }
}