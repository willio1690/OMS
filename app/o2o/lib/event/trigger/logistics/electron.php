<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_event_trigger_logistics_electron {
    private $logIdentifier = '';

    /**
     * isExistLogino
     * @param mixed $logiNo logiNo
     * @param mixed $channelId ID
     * @return mixed 返回值
     */
    public function isExistLogino($logiNo, $channelId) {
        if($logiNo) {
            $filter = array('channel_id' => $channelId, 'waybill_number' => $logiNo);
            $count = app::get('logisticsmanager')->model('waybill')->count($filter);
            return $count > 0 ? true : false;
        }
        return false;
    }

    //直连请求获取电子面单
    /**
     * directGetWaybill
     * @param mixed $deliveryId ID
     * @param mixed $channelId ID
     * @return mixed 返回值
     */
    public function directGetWaybill($deliveryId, $channelId) {
        $arrDeliveryId = explode(';', $deliveryId);
        $directRet = array();
        $channel = app::get('logisticsmanager')->model('channel')->dump(array('channel_id'=>$channelId));
        $param = kernel::single('o2o_event_trigger_logistics_data_electron_router')
            ->setChannel($channel)
            ->directDealParam($arrDeliveryId, $arrBillId);

        if($param['succ']) {
            foreach($param['succ'] as $succ) {
                $directRet['succ'][] = $succ;
            }
        }
        if($param['fail']) {
            foreach($param['fail'] as $succ) {
                $directRet['fail'][] = $succ;
            }
        }
        
        if($param['sdf']) {
            $back = kernel::single('erpapi_router_request')->set('logistics', $channel['channel_id'])->electron_directRequest($param['sdf']);
            $this->logIdentifier = $param['sdf']['primary_bn'];
            $backRet = $this->directCallback($back, $param['need_request_id'], $channel);
            if ($backRet['succ']) {
                foreach ($backRet['succ'] as $val) {
                    $directRet['succ'][] = $val;
                }
            }
            if ($backRet['fail']) {
                foreach ($backRet['fail'] as $val) {
                    $directRet['fail'][] = $val;
                }
            }
        }
        $directRet['doFail'] = count((array)$directRet['fail']);
        $directRet['doSucc'] = count((array)$directRet['succ']);
        $directRet['dealResult'] = 1;
        return $directRet;
    }

    /**
     * directCallback
     * @param mixed $result result
     * @param mixed $needRequestId ID
     * @param mixed $channel channel
     * @return mixed 返回值
     */
    public function directCallback($result, $needRequestId, $channel) {
        $waybillCodeArr = array();
        if($result && is_array($result)) {
            $db = kernel::database();
            foreach ($result as $val) {
                $retData = array();
                $retData['delivery_id'] = $val['delivery_id'];
                $retData['delivery_bn'] = $val['delivery_bn'];
                if($val['succ']) {
                    $db->beginTransaction();
                    $retData['logi_no'] = $val['logi_no'];
                    $ret = $this->_dealDirectResult($val, $channel);

                    if ($ret) {
                        $db->commit();
                        $waybillCodeArr['succ'][] = $retData;
                    } else {
                        $db->rollBack();
                        $retData['msg'] = '保存失败：运单号`'.$val['logi_no'].'`可能已被占用';
                        $waybillCodeArr['fail'][] = $retData;
                    }
                } elseif($val['succ'] === false) {
                    $retData['msg'] = $val['msg'];
                    $waybillCodeArr['fail'][] = $retData;
                }
            }
        } elseif(!empty($needRequestId)) {
            $msg = $result ? $result : '请求没有返回结果';
            foreach ($needRequestId as $val) {
                $waybillCodeArr['fail'][] = array(
                    'delivery_id' => $val,
                    'msg' => $msg."(请求日志标识：" . $this->logIdentifier .")"
                );
            }
        }
        //记录失败请求运单号日志
        if (isset($waybillCodeArr['fail'])) {
            $filter['delivery_id'] = array_merge(array_column($waybillCodeArr['fail'],'request_id'),array_column($waybillCodeArr['fail'],'delivery_id'));
            $logMsg = current($waybillCodeArr['fail'])['msg'];
            app::get('ome')->model('operation_log')->batch_write_log('delivery_expre@o2o', $filter, $logMsg,time());
        }
        return $waybillCodeArr;
    }

    private function _dealDirectResult($params, $channel) {
        $params['logi_no'] = trim($params['logi_no']);
        
        $billObj = app::get('wap')->model('delivery_bill');
        $deliveryObj = app::get('wap')->model('delivery');
        $ret = $billObj->update(array('logi_no' => $params['logi_no']), array('delivery_id' => $params['delivery_id'],'type'=>'1'));
        if (is_bool($ret)) {
            return false;
        }
        //信息变更更新到oms
        $delivery = $deliveryObj->dump(array('delivery_id'=>$params['delivery_id']),'branch_id,outer_delivery_bn');
        $store_id = kernel::single('ome_branch')->isStoreBranch($delivery['branch_id']);
        $tmp_data = array(
            'delivery_bn' =>$delivery['outer_delivery_bn'],
            'logi_no' => $params['logi_no'],
            'action' => 'addLogiNo',
        );
        kernel::single('wap_event_trigger_delivery')->doUpdate($store_id, $tmp_data, true);
        $logMsg = $this->_getWriteLogMsg("成功：". $params['logi_no']);
        $ret = app::get('ome')->model('operation_log')->write_log('delivery_expre@o2o', $params['delivery_id'], $logMsg);
        if (!$ret) {
            return false;
        }
         
        return true;
    }

    private function _getWriteLogMsg($logMsg) {
        $curr_op_name = kernel::single('desktop_user')->get_name();
        $curr_op_name = $curr_op_name ? $curr_op_name : kernel::single('desktop_user')->get_login_name();
        $bnFlag = $this->logIdentifier ? "(请求日志标识：" . $this->logIdentifier .")" : "";
        $logMsg = $curr_op_name."分派,获取运单号" . $logMsg . $bnFlag;
        return $logMsg;
    }
}