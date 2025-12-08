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

class ome_delivery_outerlogi_to_import {

    /**
     * 第三方发货导入的队列任务执行
     *
     * @param String $cursor_id
     * @param Array $params
     * @param String $errmsg
     * @return Boolean
     */
    function run(&$cursor_id,$params,&$errmsg){
        $now = time();
        $opObj = app::get('ome')->model('operation_log');
        $deliModel = app::get('ome')->model('delivery');
        $db = kernel::database();

        foreach ($params['sdfdata'] as $key=>$value) {
            $transaction = $db->beginTransaction();

            //opInfo
            $opInfo = $value['opInfo']; unset($value['opInfo']);
            $is_super = $value['is_super']; unset($value['is_super']);
            kernel::single('desktop_user')->user_data = $value['user_data']; unset($value['user_data']);
            kernel::single('desktop_user')->user_id = $opInfo['op_id'];

            #第三方发货，更该了快递公司
            if($value['logi_id']){
                $deliModel->update(array('logi_name'=>$value['logi_name'],'logi_id'=>$value['logi_id']),array('delivery_bn'=>$value['delivery_bn']));
                $opObj->write_log('delivery_modify@ome', $value['delivery_id'], '第三方发货，更改快递公司',$now,$opInfo);
            }else{
                unset($value['logi_id'],$value['logi_name']);
            }

            if ($value['stock_status'] != 'true') {
                $opObj->write_log('delivery_stock@ome', $value['delivery_id'], '备货单打印(系统模拟打印)',$now,$opInfo);
            }
            if ($value['deliv_status'] != 'true') {
                $opObj->write_log('delivery_deliv@ome', $value['delivery_id'], '发货单打印(系统模拟打印)',$now,$opInfo);
            }
            if ($value['expre_status'] != 'true') {
                $opObj->write_log('delivery_expre@ome', $value['delivery_id'], '快递单打印(系统模拟打印)',$now,$opInfo);
            }

            $value['stock_status'] = 'true';
            $value['deliv_status'] = 'true';
            $value['expre_status'] = 'true';
            $value['status'] = 'progress';

            $result = $deliModel->save($value);
            if ($result) {
                if ($is_super) {
                    $branches = array('_ALL_');
                } else {
                    $branches = kernel::single('ome_op')->getBranchByOp($opInfo['op_id']);
                }
                
                $process = false;

                # 校验
                if ($value['verify'] != 'true') {
                    $delivery = kernel::single('ome_delivery_check')->checkAllow($value['logi_no'],$branches,$msg);
                    if ($delivery === false) {
                        $db->rollback();
                        continue;
                    }

                    $verify = $deliModel->verifyDelivery($delivery);
                    if (!$verify) {
                        $db->rollback();
                        continue;
                    }
                }
                
                # 发货
                $delivery = kernel::single('ome_delivery_consign')->deliAllow($value['logi_no'],$branches,$msg,$patch);
                if (!$delivery) {
                    $db->rollback();
                    continue;
                }
                
                $result = $deliModel->consignDelivery($value['delivery_id'],$value['weight'],$msg);
                if (!$result) {
                    $db->rollback();
                    continue;
                }
                
                $db->commit($transaction);
            } else {
                $db->rollback();
            }
        }

        return false;
    }

}
