<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_delivery_electron {

    /**
     * dealElectron
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $channelId ID
     * @param mixed $afterPrint afterPrint
     * @param mixed $controller controller
     * @return mixed 返回值
     */
    public function dealElectron(&$arrDelivery, $channelId, $afterPrint, $controller) {
    
        if($afterPrint) {
            return $this->_dealDeliveryElectron($arrDelivery, $channelId, $controller);
        } else {
            if(count($_REQUEST['b_id'])) {
                return $this->_dealBillElectron($arrDelivery, $channelId, $_REQUEST['b_id'], $controller);
            }
        }
        return false;
    }

    #非补打
    private function _dealDeliveryElectron(&$arrDelivery, $channelId, $controller) {
        $noWaybillBill = array();
        foreach($arrDelivery as $delivery) {
            if(empty($delivery['logi_no'])) {
                $noWaybillBill[] = $delivery;
            }
        }
        $objEventElectron = kernel::single('wms_event_trigger_logistics_electron');
        $buffer = $objEventElectron->bufferGetWaybill($channelId);

        if($buffer === true) {
            $idBn = array();
            if(!empty($noWaybillBill)) {
                list($getWaybill, $notGetWaybill) = $objEventElectron->getBufferWaybill($noWaybillBill, $channelId);
                foreach ($getWaybill as $k => $val) {
                    //应对rds对事务处理漏掉sql的情况, 如rds修复可还原到上一版本
                    $logiNO = app::get('wms')->model('delivery_bill')->dump(array('delivery_id'=>$k,'type'=>'1'), 'logi_no');
                    if(empty($logiNO['logi_no'])) {
                        $notGetWaybill[$k] =  $logiNO['delivery_id'];
                    } else {
                        $arrDelivery[$k]['logi_no'] = $val;
                    }
                }
                foreach ($notGetWaybill as $key => $value) {
                    $idBn[$key] = array(
                        'bn' => $arrDelivery[$key]['delivery_bn'],
                        'msg' => '获取电子面单失败'
                    );
                    unset($arrDelivery[$key]);
                }
            }
            //获取大头笔
           
            $notGetExtend = $objEventElectron->getWaybillExtend($arrDelivery, $channelId);
            
                foreach($notGetExtend as $eKey => $extend) {
                    $idBn[$eKey] = array(
                        'bn' => $arrDelivery[$eKey]['delivery_bn'],
                        'msg' => '获取大头笔失败',
                    );
                    //unset($arrDelivery[$eKey]);
                
            }
            if($idBn) {
                return array('id_bn'=>$idBn);
            }
        } elseif (is_numeric($buffer)) {
            if(empty($noWaybillBill)) {
                return false;
            }
            $directParam = array(
                'get' => $_GET,
                'ids' => array_keys($arrDelivery),
                'channel' => array('channel_id'=>$channelId),
                'directNum' => $buffer
            );

            $controller->getElectronLogiNo($directParam,true);
        } else {
            return false;
        }
    }

    //补打
    private function _dealBillElectron(&$arrDelivery, $channelId, $billId, $controller) {
        $delivery = current($arrDelivery);
        $arrBill = app::get('wms')->model('delivery_bill')->getList('*', array('b_id'=>$billId));
        $noWaybillBill = array();
        foreach($arrBill as $bill) {
            if(empty($bill['logi_no'])) {
                $noWaybillBill[] = $bill;
            }
        }
        $objEventElectron = kernel::single('wms_event_trigger_logistics_electron');
        $buffer = $objEventElectron->bufferGetWaybill($channelId);
        if($buffer === true) {
            $idBn = array();
            if(!empty($noWaybillBill)) {
                list($getWaybill, $notGetWaybill) = $objEventElectron->getBufferWaybill($noWaybillBill, $channelId, true);
                foreach ($notGetWaybill as $key => $value) {
                    $idBn[$key] = array(
                        'bn' => $delivery['delivery_bn'] . '-' . $key,
                        'msg' => '获取电子面单失败'
                    );
                }
                $_REQUEST['b_id'] = array_diff($_REQUEST['b_id'], array_keys($notGetWaybill));
            }
            //获取大头笔

            $notGetExtend = $objEventElectron->getWaybillExtend($arrDelivery, $channelId, $arrBill);
            if($notGetExtend) {
                foreach($notGetExtend as $eKey => $extend) {
                    $idBn[$eKey] = array(
                        'bn' => $delivery['delivery_bn'],
                        'msg' => '获取大头笔失败'
                    );

                }
            }
            if($idBn) {
                return array('id_bn'=>$idBn);
            }
        } elseif (is_numeric($buffer)) {
            if(empty($noWaybillBill)) {
                return false;
            }
            $directParam = array(
                'get' => $_GET,
                'ids' => array_keys($arrDelivery),
                'b_id' => $billId,
                'channel' => array('channel_id'=>$channelId),
                'directNum' => $buffer
            );
            $controller->getElectronLogiNo($directParam, false);
        } else {
            return false;
        }
    }
}