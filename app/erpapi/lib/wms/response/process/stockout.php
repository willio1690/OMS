<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 出库单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_stockout
{
    /**
     * 出库单
     *
     * @param Array $param=array(
     *                 'io_type'=>@出库类型@ PURCHASE_RETURN|ALLCOATE
     *                  'io_source'=>selfwms
     *                  'io_bn'=>@出库单号@
     *                  'io_status'=>@出库类型@ FAILED|CLOSE|CANCEL|PARTIN|FINISH
     *                  'memo'=>@备注@
     *                  'operation_time'=>@操作时间@
     *                  'items'=>array(
     *                      'bn'=>@货号@
     *                      'num'=>@数量@
     *                      'defective_num'=>@次品数@
     *                      'normal_num'=>@正品数@
     *                  )
     *              )
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        if ($params['delivery_bn']) {
            
            return kernel::single('ome_event_receive_delivery')->update($params);
        } else {

            $result = kernel::single('console_event_receive_iostock')->stockout_result($params);
        }

        // 报警
        if($result['rsp'] == 'fail' && $params['io_status'] == 'FINISH') {
            kernel::single('monitor_event_notify')->addNotify('wms_stockout_finish', [
                'io_bn' => $params['io_bn'],
                'errmsg'      => $result['msg'],
            ]);
        }
        return $result;
    }
}