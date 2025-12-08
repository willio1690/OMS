<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @describe 发货
 * @author   pangxp
 * @version  2020.11.24 20:55:00
 */
class openapi_data_original_pda_delivery{

    /**
     * 发货单
     * @param Array $params=array(
     *                  'status'=>@状态@ delivery 
     *                  'delivery_bn'=>@发货单号@
     *                  'out_delivery_bn'=>@外部发货单号@
     *                  'logi_no'=>@运单号@
     *                  'delivery_time'=>@发货时间@
     *                  'weight'=>@重量@
     *                  'delivery_cost_actual'=>@物流费@
     *                  'logi_id'=>@物流公司编码@
     *                  ===================================
     *                  'status'=>print,
     *                  'delivery_bn'=>@发货单号@
     *                  'stock_status'=>@备货单打印状态@
     *                  'deliv_status'=>@发货单打印状态@
     *                  'expre_status'=>@快递单打印状态@
     *                  ===================================
     *                  'status'=>check
     *                  'delivery_bn'=>@发货单号@
     *                  ===================================
     *                  'status'=>cancel
     *                  'delivery_bn'=>@发货单号@
     *                  'memo'=>@备注@
     *                  ===================================
     *                  'status'=>update
     *                  'delivery_bn'=>@发货单号@
     *                  'action'=>updateDetail|addLogiNo
     *                  
     *
     *              )
     * @return void
     * @author 
     **/
    public function status_update($params)
    {
        return kernel::single('ome_event_receive_delivery')->update($params);
    }

    /**
     * 校验
     * @param Array $params 
     */
    public function check($params)
    {
        //检查运单号是否属于同一个处理的发货单
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $delivery_id = $deliveryBillLib->getDeliveryIdByPrimaryLogi($params['logi_no']);

        //检查订单的当前状态
        $dlyCheckLib = kernel::single('wms_delivery_check');
        if(!$dlyCheckLib->checkOrderStatus($delivery_id, true, $msg)){
            return array(
                'rsp' => 'succ',
                'msg' => $msg ? $msg : '当前订单处理状态不允许校验',
            );
        }

        $deliveryObj  = app::get('wms')->model('delivery');
        $dly = $deliveryObj->dump($delivery_id,'*', array('delivery_items'=>array('*')));

        if ($dly['process_status'] == '3') {
            return array(
                'rsp' => 'succ',
                'msg' => '发货单已校验完成',
            );
        }

        // 组织数据
        $data = array(
            'logi_no' => $params['logi_no'],
            'delivery_id' => $delivery_id,
            'checkType' => 'barcode',
            'count' => 0,
            'number' => 0,
        );

        $items = array();
        foreach ($dly['delivery_items'] as $delivery_items) {
            $items[$delivery_items['bn']] = $delivery_items;
        }

        $verify_items = @json_decode($params['verify_items'], true);
        foreach ($verify_items as $value) {

            if (!$verify_items) {
                return array(
                    'rsp' => 'fail',
                    'msg' => '校验明细为空',
                );
            }

            $bn = $value['bn']; 

            if (!$items[$bn]) {
                return array(
                    'rsp' => 'fail',
                    'msg' => sprintf('发货单【%s】不存在货号【%s】',$dly['delivery_bn'],$bn),
                );
            }

            if ($items[$bn]['verify'] == 'true') {
                return array(
                    'rsp' => 'fail',
                    'msg' => sprintf('发货单【%s】货号【%s】已校验',$dly['delivery_bn'],$value['bn']),
                );
                return false;
            }

            $data['count'] += $items[$bn]['number'];
            $verify_num = $items[$bn]['verify_num'] + $value['verify_nums'];
            if ($verify_num >= $items[$bn]['number']) {
                $data['number'] += $items[$bn]['number'];
                $data['number_' . $bn] = $items[$bn]['number'];
            } else {
                $data['number'] += $verify_num;
                $data['number_' . $bn] = $verify_num;
            }
            $data['t_verify_num'][$bn] = $value['verify_nums'];

        }

        unset($_POST['serial_data']);
        if (isset($params['serial_data'])) {
            $serial_data = @json_decode($params['serial_data'], true);
            if (!empty($serial_data)) {
                $wms_product_serial =  app::get('wms')->model('product_serial');
                foreach ($serial_data as $svalue) {
                    if (!$wms_product_serial->checkSerial($svalue['serial_number'], $svalue['bn'], $dly['branch_id'])) {
                        return array(
                            'rsp' => 'fail',
                            'msg' => sprintf('【%s】货号的唯一码【%s】不存在或当前状态不可用', $svalue['bn'], $svalue['serial_number']),
                        );
                    }
                    $_POST['serial_data'][$svalue['bn']][] = $svalue['serial_number']; 
                }

                foreach ($_POST['serial_data'] as $sk => $sv) {
                    if ( $data['t_verify_num'][$sk] != count($sv) ) {
                        return array(
                            'rsp' => 'fail',
                            'msg' => sprintf('【%s】货号的唯一码数量【%s】和实际货品数量【%s】不相符', $sk, count($sv), $data['number_' . $sk]),
                        );
                    }
                }
            }
        }

        //捡货绩效开始记录点
        foreach(kernel::servicelist('tgkpi.pick') as $o){
            if(method_exists($o,'begin_check')){
                $o->begin_check($delivery_id);
            }
        }

        $opObj = app::get('ome')->model('operation_log');
        $deliveryLib = kernel::single('wms_delivery_process');

        if ($data['count'] === $data['number']) {
            //对发货单详情进行校验完成处理
            if ($deliveryLib->verifyDelivery($delivery_id)){
                //增加发货单校验把保存后的扩展
                foreach(kernel::servicelist('ome.delivery') as $o){
                    if(method_exists($o,'after_docheck')){
                        $o->after_docheck($data);
                    }
                }
                return array(
                    'rsp' => 'succ',
                    'msg' => '发货单校验完成',
                );
            }else {
                return array(
                    'rsp' => 'fail',
                    'msg' => '发货单校验未完成，请重新校验',
                );
            }
        } else {
            //保存部分校验结果
            $flag = $this->verifyItemsByDeliveryId($delivery_id, $data);
            if ($flag){
                $opObj->write_log('delivery_check@wms', $delivery_id, '发货单部分检验数据保存完成');
                return array(
                    'rsp' => 'succ',
                    'msg' => '发货单部分检验数据保存完成',
                );
            }else {
                return array(
                    'rsp' => 'fail',
                    'msg' => '发货单校验未完成，请重新校验',
                );
            }
        }
    }

    /**
     *
     * 校验内容的临时保存方法
     * @param int $dly_id 发货单ID
     */
    function verifyItemsByDeliveryId($dly_id, $data)
    {
        $basicMaterialBarcode    = kernel::single('material_basic_material_barcode');
        $dlyObj  = app::get('wms')->model('delivery');
        $dly_itemObj  = app::get('wms')->model('delivery_items');
        $dlyItemsSLObj    = app::get('wms')->model('delivery_items_storage_life');
        $dlyItemsSerialLib    = kernel::single('wms_receipt_dlyitemsserial');

        $dlyInfo = $dlyObj->getList('delivery_bn,branch_id', array('delivery_id'=>$dly_id), 0, 1);
        $items = $dly_itemObj->getList('item_id,number,product_id,bn,product_name,verify,verify_num,use_expire', array('delivery_id'=>$dly_id), 0, -1);

        foreach ($items as $item)
        {
            //唯一码预占处理
            if(isset($_POST['serial_data'][$item['bn']])){
                foreach($_POST['serial_data'][$item['bn']] as $key => $serial){
                    $serialData = array(
                        'delivery_id' => $dly_id,
                        'delivery_bn' => $dlyInfo[0]['delivery_bn'],
                        'branch_id' => $dlyInfo[0]['branch_id'],
                        'product_id' => $item['product_id'],
                        'bn' => $item['bn'],
                        'product_name' => $item['product_name'],
                        'serial_number' => $serial,
                        'status' => 0,
                    );

                    $rs = $dlyItemsSerialLib->generate($serialData, $msg);
                    if(!$rs){
                        return false;
                    }
                    unset($serialData);
                }
            }

            //保质期批次校验
            if($item['use_expire'] == 1){
                $item_expire_arr = $dlyItemsSLObj->getList('*',array('delivery_id'=>$dly_id,'item_id'=>$item['item_id']));
                $all_item_num = 0;
                foreach($item_expire_arr as $item_expire){
                    $barcode = $item_expire['expire_bn'];
                    $num = intval($data['number_'. $barcode]);

                    $num = $num>$item_expire['number']? $item_expire['number'] : $num;
                    $update_data = array();
                    $update_data['verify'] = 'false';
                    $update_data['verify_num'] = $num;
                    if ($dlyItemsSLObj->update($update_data, array('itemsl_id'=>$item_expire['itemsl_id'])) == false) return false;
                    $update_data = null;
                    $all_item_num += $num;
                    $data['number_'. $barcode] -= $num;
                }

                $update_data = array();
                $update_data['verify'] = 'false';
                $update_data['verify_num'] = $all_item_num;
                if ($dly_itemObj->update($update_data, array('item_id'=>$item['item_id'])) == false) return false;
                $update_data = null;
            }else{
                //普通条码校验
                $barcode    = $basicMaterialBarcode->getBarcodeById($item['product_id']);
                $num = intval($data['number_'. $barcode]);
                $num = $num>$item['number']? $item['number'] : $num;
                $update_data = array();
                $update_data['verify'] = 'false';
                $update_data['verify_num'] = $num;
                if ($dly_itemObj->update($update_data, array('item_id'=>$item['item_id'])) == false) return false;
                $update_data = null;
                $data['number_'. $barcode] -= $num;
            }
        }
        return true;
    }

    /**
     * 发货
     * @param Array $params 
     */
    public function consign($params)
    {
        $logi_no = $params['logi_no'];
        $weight  = $params['weight'];

        $dlyObj = app::get('wms')->model('delivery');
        $dlyBillObj = app::get('wms')->model('delivery_bill');
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $opObj = app::get('ome')->model('operation_log');
        $wmsCommonLib = kernel::single('wms_common');
        $dlyProcessLib = kernel::single('wms_delivery_process');

        $primary = false;
        $secondary = false;
        //如果没有发货单信息，则根据物流单号识别是主单还是次单,并获取相关信息

        $delivery_id = $deliveryBillLib->getDeliveryIdByPrimaryLogi($logi_no);
        if(!is_null($delivery_id)){
            $primary = true;
            $dly = $dlyObj->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
        }else{
            $delivery_id = $deliveryBillLib->getDeliveryIdBySecondaryLogi($logi_no);
            if(!is_null($delivery_id)){
                $secondary = true;
                $dly = $dlyObj->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
            }
        }

        $logi_number = $dly['logi_number'];
        $delivery_logi_number =$dly['delivery_logi_number'];

        //检查前端订单是否退款,原有逻辑是否需要?

        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','发货单：逐单发货');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：consign');

        //获取物流费用
        $area = $dly['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$dly['logi_id'],$weight);

        //如果是次物流单号
        if($secondary){
            $data = array(
                'status'=>'1',
                'weight'=>$weight,
                'delivery_cost_actual'=>$delivery_cost_actual,
                'delivery_time'=>time(),
                'type' => 2,
            );
            $filter = array('logi_no'=>$logi_no);
            $dlyBillObj->update($data,$filter);

            $logstr = '快递单号:'.$logi_no.' 发货';
            $opObj->write_log('delivery_bill_express@wms', $dly["delivery_id"], $logstr);

            if(($logi_number==$delivery_logi_number)&&$dly['status'] != 3){
                if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                    return array(
                        'rsp' => 'succ',
                        'msg' => $dly['delivery_bn'] . '发货处理完成',
                    );
                }else {
                    return array(
                        'rsp' => 'fail',
                        'msg' => $dly['delivery_bn'] . '发货未完成',
                    );
                }
            }else{
                $data = array('delivery_logi_number'=>$delivery_logi_number+1,'weight'=>$dly['weight']+$weight,'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual);
                $filter = array('delivery_id'=>$dly['delivery_id']);
                $dlyObj->update($data,$filter);

                if($logi_number==($delivery_logi_number+1)){
                    if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                        return array(
                            'rsp' => 'succ',
                            'msg' => $dly['delivery_bn'] . '发货处理完成',
                        );
                    }else {
                        return array(
                            'rsp' => 'fail',
                            'msg' => $dly['delivery_bn'] . '发货未完成',
                        );
                    }
                }else{
                    return array(
                        'rsp' => 'succ',
                        'msg' => $dly['delivery_bn'] . '发货处理完成',
                    );
                }
            }
        }else{
            //判断这个主物流单有没有对应的次物流单,等于1的时候只有一个包裹单
            if($logi_number == 1){
                if(($logi_number==$delivery_logi_number)&&$dly['status'] != 3){
                     if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                        return array(
                            'rsp' => 'succ',
                            'msg' => $dly['delivery_bn'] . '发货处理完成',
                        );
                     }else {
                        return array(
                            'rsp' => 'succ',
                            'msg' => $dly['delivery_bn'] . '发货未完成,',
                        );
                     }
                }else{
                    $data = array(
                        'status'=>'1',
                        'weight'=>$weight,
                        'delivery_cost_actual'=>$delivery_cost_actual,
                        'delivery_time'=>time(),
                        'type' => 1,
                    );
                    $filter = array('logi_no'=>$logi_no);
                    $dlyBillObj->update($data,$filter);

                    $data = array('delivery_logi_number'=>$delivery_logi_number+1,'weight'=>$dly['weight']+$weight,'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual);
                    $filter = array('delivery_id'=>$dly['delivery_id']);
                    $dlyObj->update($data,$filter);

                    if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                        return array(
                            'rsp' => 'succ',
                            'msg' => $dly['delivery_bn'] . '发货处理完成',
                        );
                    }else {
                        return array(
                            'rsp' => 'fail',
                            'msg' => $dly['delivery_bn'] . '发货未完成,',
                        );
                    }
                }
            }else{
                //如果存在子物流单
                //计算已经发货的子物流单、总共的物流单
                //1,查询实际的发货数量，和总物流数量
                if(($logi_number > $delivery_logi_number)){
                    $data = array(
                        'status'=>'1',
                        'weight'=>$weight,
                        'delivery_cost_actual'=>$delivery_cost_actual,
                        'delivery_time'=>time(),
                        'type' => 1,
                    );
                    $filter = array('logi_no' =>$logi_no);
                    $dlyBillObj->update($data,$filter);

                    $data = array(
                        'delivery_logi_number'=>$delivery_logi_number+1,
                        'weight'=>$dly['weight']+$weight,
                        'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual,
                    );
                    $filter = array('delivery_id'=>$dly['delivery_id']);
                    $dlyObj->update($data,$filter);

                    if($logi_number==($delivery_logi_number+1)){
                        if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                            return array(
                                'rsp' => 'succ',
                                'msg' => $dly['delivery_bn'] . '发货处理完成',
                            );
                        }else {
                            return array(
                                'rsp' => 'fail',
                                'msg' => $dly['delivery_bn'] . '发货未完成,',
                            );
                        }
                    }
                    $this->end(true, '发货处理完成');
                //加入如果$logi_number==$delivery_logi_number 但是发货状态没有改变的判断
                }elseif(($delivery_logi_number == $logi_number) && $dly['status'] != 3){
                    if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                        return array(
                            'rsp' => 'succ',
                            'msg' => $dly['delivery_bn'] . '发货处理完成',
                        );
                    }else {
                        return array(
                            'rsp' => 'fail',
                            'msg' => $dly['delivery_bn'] . '发货未完成,',
                        );
                    }
                }else{
                    return array(
                        'rsp' => 'fail',
                        'msg' => $dly['delivery_bn'] . '此物流运单已发货',
                    );
                }
            }
        }
    }
}