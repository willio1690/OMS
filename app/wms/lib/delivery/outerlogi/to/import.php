<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 第三方发货导入
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class wms_delivery_outerlogi_to_import {

    /**
     * 第三方发货导入的队列任务执行
     *
     * @param String $cursor_id
     * @param Array $params
     * @param String $errmsg
     * @return Boolean
     */
    function run(&$cursor_id,$params,&$msg){
        $now = time();
        $opObj = app::get('ome')->model('operation_log');
        $dlyCheckLib = kernel::single('wms_delivery_check');
        $dlyProcessLib = kernel::single('wms_delivery_process');
        $deliveryObj = app::get('wms')->model('delivery');
        $deliveryBillObj = app::get('wms')->model('delivery_bill');

        foreach ($params['sdfdata'] as $key=>$value) {

            //opInfo
            $opInfo = $value['opInfo']; unset($value['opInfo']);
            $is_super = $value['is_super']; unset($value['is_super']);
            kernel::single('desktop_user')->user_data = $value['user_data']; unset($value['user_data']);
            kernel::single('desktop_user')->user_id = $opInfo['op_id'];
            
            $dlyInfo = $deliveryObj->dump($value['delivery_id'],'print_status,process_status,branch_id,outer_delivery_bn,logi_id,logi_name,memo,delivery_bn');
            if($value['logi_id']){
                $deliveryObj->update(array('logi_name'=>$value['logi_name'],'logi_id'=>$value['logi_id']),array('delivery_id'=>$value['delivery_id']));
                $opObj->write_log('delivery_modify@wms', $value['delivery_id'], '第三方发货，更改快递公司',$now,$opInfo);
            }else{
                unset($value['logi_id'],$value['logi_name']);
            }
            if($dlyInfo['print_status'] > 0){
                if (($dlyInfo['print_status'] & 1) != 1) {
                    $opObj->write_log('delivery_stock@wms', $value['delivery_id'], '备货单打印(系统模拟打印)',$now,$opInfo);
                }
                if (($dlyInfo['print_status'] & 2) != 2) {
                    $opObj->write_log('delivery_deliv@wms', $value['delivery_id'], '发货单打印(系统模拟打印)',$now,$opInfo);
                }
                if (($dlyInfo['print_status'] & 4) != 4) {
                    $opObj->write_log('delivery_expre@wms', $value['delivery_id'], '快递单打印(系统模拟打印)',$now,$opInfo);
                }
            }

            $value['print_status'] = 7;
            $value['process_status'] = (($dlyInfo['process_status'] == 3) ? 3 : 1);

            //更新打印及物流重量等信息
            $result = $deliveryObj->save($value);
            if ($result) {
                //同步打印状态到oms
                $wms_id = kernel::single('ome_branch')->getWmsIdById($dlyInfo['branch_id']);
                $data = array(
                    'delivery_bn' => $dlyInfo['outer_delivery_bn'],
                );
                $res = kernel::single('wms_event_trigger_delivery')->doPrint($wms_id, $data, true);

                //保存物流单号
                $deliveryBillObj->update(array('logi_no'=>$value['logi_no']),array('delivery_id' => $value['delivery_id'],'type'=>1));
                $opObj->write_log('delivery_modify@wms', $value['delivery_id'], '修改发货单详情');

                //信息变更更新到oms
                $oms_update_logi_id=$dlyInfo['logi_id'];
                if($value['logi_id']){
                	$oms_update_logi_id=$value['logi_id'];
                }
                $oms_update_logi_name=$dlyInfo['logi_name'];
                if($value['logi_name']){
                	$oms_update_logi_name=$value['logi_name'];
                }
                $data = array(
                    'delivery_bn' => $dlyInfo['outer_delivery_bn'],
                    'weight' => $value['weight'],
                    'delivery_cost_actual' => $value['delivery_cost_actual'],
                    'logi_id' => $oms_update_logi_id,
                    'logi_no' => $value['logi_no'],
                    'logi_name' => $oms_update_logi_name,
                    'memo' => $dlyInfo['memo'],
                    'action' => 'updateDetail',
                );
                $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $data, true);

                if ($is_super) {
                    $branches = array('_ALL_');
                } else {
                    $branches = kernel::single('ome_op')->getBranchByOp($opInfo['op_id']);
                }

                $process = false;
                # 校验
                if (($value['process_status'] &  2) != 2) {
                    $delivery = $dlyCheckLib->checkAllow($value['logi_no'], $msg, 'outer_consign');
                    if ($delivery === false) {
                        #加入报错msg
                        $errormsg[]    = $msg . '(发货单: '. $dlyInfo['delivery_bn'] .'校验报错)';
                        continue;
                    }

                    $verify = $dlyProcessLib->verifyDelivery($value['delivery_id']);
                    if (!$verify) {
                        #加入报错msg
                        $errormsg[]    = '发货单校验失败(发货单: '. $dlyInfo['delivery_bn'] .')';
                        continue;
                    }
                }

                # 发货
                $return_error = $dlyCheckLib->consignAllow('', $value['logi_no'], $value['weight']);
                if ($return_error) {
                    #加入报错msg
                    $errormsg[]    = $return_error . '(发货单: '. $dlyInfo['delivery_bn'] .'发货报错)';
                    continue;
                }

                $data = array(
                    'status'=> 1,
                    'weight'=> $value['weight'],
                    'delivery_cost_actual'=> $value['delivery_cost_actual'],
                    'delivery_time'=>time(),
                );
                $filter = array('delivery_id'=>$value['delivery_id'],'status'=> 0, 'type'=>1);
                $deliveryBillObj->update($data,$filter);

                $numdata = array('delivery_logi_number'=>1);
                $numfilter = array('delivery_id'=>$value['delivery_id']);
                $deliveryObj->update($numdata,$numfilter);

                $result = $dlyProcessLib->consignDelivery($value['delivery_id']);
                if (!$result) {
                    #加入报错msg
                    $errormsg[]    = '执行发货事务失败(发货单: '. $dlyInfo['delivery_bn'] .')';
                    continue;
                }
            } else {
                #加入报错msg
                $errormsg[]    = '更新打印状态失败(发货单: '. $dlyInfo['delivery_bn'] .')';
            }
        }

        #汇总报错msg
        if ($errormsg) {
            $msg    = implode(',', $errormsg);
        }
        return false;
    }

}
