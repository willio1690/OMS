<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_logistics_electron {
    private $logIdentifier = '';

    // 发货单物流回传(只回传主单)
    public function delivery($deliveryId,$logiNo="")
    {
        $delivery     = app::get('ome')->model('delivery')->getList('*', array('delivery_id' => $deliveryId));
        $deliveryData = $delivery[0];
        $corp = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$deliveryData['logi_id']), 'tmpl_type,channel_id');
        if($corp['tmpl_type'] != 'electron') {
            return true;
        }

        if($logiNo){
           $deliveryData['logi_no'] = $logiNo;
        }

        $channel = app::get('logisticsmanager')->model('channel')->dump(array('channel_id'=>$corp['channel_id']));
        $shop = app::get('logisticsmanager')->model('channel_extend')->dump(array('channel_id'=>$corp['channel_id']),'province,city,area,address_detail,seller_id,default_sender,mobile,tel,zip');
        $sdf = kernel::single('ome_event_trigger_logistics_data_electron_router')
            ->setChannel($channel)
            ->getDeliverySdf($deliveryData, $shop);
        if($sdf) {
            return kernel::single('erpapi_router_request')->set('logistics', $channel['channel_id'])->electron_delivery($sdf);
        }
        return true;
    }
    
    /**
     * 打印打印机指令
     *
     * @return void
     * @author 
     **/
    public function getPrintCPCL($delivery_id, $printer)
    {
        $deliveryMdl = app::get('ome')->model('delivery');
        $delivery = $deliveryMdl->db_dump($delivery_id, 'delivery_id,delivery_bn,logi_id,logi_no,shop_id');

        if (!$delivery) return array (false, '发货单ID['.$delivery_id.']不存在');

        $corpMdl = app::get('ome')->model('dly_corp');
        $corp = $corpMdl->db_dump($delivery['logi_id'],'corp_id,channel_id');
        app::get('ome')->model('dly_corp_channel')->getChannel($corp, array($delivery));
        if (!$corp['channel_id']) return array (false , '发货单['.$delivery['delivery_bn'].']非电子面单');

        $channel = app::get('logisticsmanager')->model('channel')->db_dump($corp['channel_id'], 'channel_type,channel_id');
        if ($channel['channel_type'] != 'taobao') return array (false , '暂不支持非菜鸟电子面单来源');

        $waybill = app::get('logisticsmanager')->model('waybill')->db_dump(array ('waybill_number'=>$delivery['logi_no']), 'id');
        if (!$waybill) return array (false, '发货单['.$delivery['delivery_bn'].']未查到运单号');

        $waybillExtend = app::get('logisticsmanager')->model('waybill_extend')->db_dump(array ('waybill_id' => $waybill['id']));

        $sdf = array (
            'json_packet'  => $waybillExtend['json_packet'],
            'printer_name' => $printer['printer_name'],
            'client_id'    => $printer['client_id'],
            'client_type'  => $printer['client_type'],
        );


        $res = kernel::single('erpapi_router_request')->set('logistics', $channel['channel_id'])->electron_getPrintCPCL($sdf);

        if ($res['rsp'] == 'fail') {
            return array (false, $res['err_msg']);
        }

        if (!$res['data']['cainiao_cloudprint_cmdprint_render_response']['cmd_content']) {
            $sub_msg = $res['data']['cainiao_cloudprint_cmdprint_render_response']['ret_msg'];

            return array (false, $sub_msg);
        }

        return array (true, $res['data']['cainiao_cloudprint_cmdprint_render_response']);
    }

    public function isExistLogino($logiNo, $channelId) {
        if($logiNo) {
            $filter = array('channel_id' => $channelId, 'waybill_number' => $logiNo);
            $count = app::get('logisticsmanager')->model('waybill')->count($filter);
            return $count > 0 ? true : false;
        }
        return false;
    }

    //直连请求获取电子面单
    public function directGetWaybill($deliveryId, $channelId) {
        $arrDeliveryId = explode(';', $deliveryId);
        $directRet = array();
        $channel = app::get('logisticsmanager')->model('channel')->dump(array('channel_id'=>$channelId));
        $param = kernel::single('ome_event_trigger_logistics_data_electron_router')
            ->setChannel($channel)
            ->directDealParam($arrDeliveryId, []);

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
            app::get('ome')->model('operation_log')->batch_write_log('delivery_expre@ome', $filter, $logMsg,time());
        }
        return $waybillCodeArr;
    }

    private function _dealDirectResult($params, $channel) {
        $logi_no = trim($params['logi_no']);
        $tmp_data = array(
            'delivery_bn' => $params['delivery_bn'],
            'logi_no'     => $logi_no,
            'action'      => 'addLogiNo',
            'status'      => 'update',
        );

        $ret = kernel::single('ome_event_receive_delivery')->update($tmp_data);
        if ($ret['rsp'] != 'succ') {
            return false;
        }
         
        return true;
    }

}