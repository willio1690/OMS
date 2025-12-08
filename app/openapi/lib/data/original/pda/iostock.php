<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @describe 出入库
 * @author   pangxp
 * @version  2020.11.23 19:00:00
 */
class openapi_data_original_pda_iostock{

    /**
     * 入库单
     *
     * @param Array $params=array(
     *                  'io_type'=>@入库类型@ PURCHASE|ALLCOATE
     *                  'io_source'=>selfwms
     *                  'io_bn'=>@入库单号@                                           
     *                  'io_status'=>@入库单状态@ PARTIN|FINISH|FAILED|CANCEL|CLOSE   
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
    public function inStorage($data)
    {
        if (!$data['io_type']) {
            $data['io_type'] = $this->get_iostock_types($data['type_id']);
        }

        // 入库确认
        return kernel::single('console_event_receive_iostock')->stockin_result($data);
    }

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
    public function outStorage($data)
    {
        if (!$data['io_type']) {
            $data['io_type'] = $this->get_iostock_types($data['type_id']);
        }

        // 出库确认
        return kernel::single('console_event_receive_iostock')->stockout_result($data);
    }

    /**
     * 根据type_id获取对应的出入库类型
     * @param   Int     $type_id
     * @return  String
     * @author 
     **/
    public function get_iostock_types($type_id = ''){
        switch ( intval($type_id) ) {
            // 采购入库
            case '1':
                return 'PURCHASE';
                break;

            // 采购退货
            case '10':
                return 'PURCHASE_RETURN';
                break;

            // 调拨入库
            case '4':
                return 'ALLCOATE';
                break;
            
            // 调拨出库
            case '40':
                return 'ALLCOATE';
                break;

            // 唯品会出库
            case '900':
                return 'VOPSTOCKOUT';
                break;

            // 7：直接出库;70：直接入库;300：样品出库;400：样品入库';
            default:
                return 'OTHER';
                break;
        }
    }

}