<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_rpc_response_func_billtaobao{

    /**
     * 接口交易数据分析
     * @access public
     * @param Array $record 单条交易数据
     *  $record = array(
     *    'alipay_order_no' => '凭据号',
     *    'merchant_order_no' => '商户订单号',
     *    'order_type' => '订单类型',
     *    'order_from' => '订单来源',
     *    'order_status' => 订单状态',
     *    'order_title' => '订单标题描述',
     *    'total_amount' => '金额(正)',
     *    'balance' => '余额',
     *    'in_out_type' => 'in:收入 out:支出',
     *    'modified_time' => '交易最后修改时间,2012-12-21 12:21:21',
     *    'opposite_user_id' => '交易对方帐号',
     *  );
     * @param Array $shop_detail 店铺信息
     * @return Array
     */
    function trade_add($record,$shop_detail=array()){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (empty($record)){
            $rs['rsp'] = 'succ';
            $rs['msg'] = '交易数据不能为空';
            return $rs;
        }

        $trade_status = strtoupper(trim($record['order_status']));
        $order_type = strtoupper(trim($record['order_type']));
        $alipay_trade_bn = trim($record['alipay_order_no']);
        $order_bn = trim($record['merchant_order_no']);
        $io_type = strtolower(trim($record['in_out_type']));
        $money = abs($record['total_amount']);
        $order_title = $record['order_title'];
        $trade_account = $record['opposite_user_id'];
        $node_id = $shop_detail['node_id'];
        $trade_time = $record['modified_time'];
        $balance = $record['balance'];
        
        $order_type_arr = array('TRADE','CAE','CAR_R','CHARGE','_OTHERS');
        $trade_status_arr = array('ACC_FINISHED','TRADE_FINISHED','CHARGE_FINISHED','_OTHERS');
        if (!in_array($order_type,$order_type_arr) || !in_array($trade_status,$trade_status_arr)){
            $rs['rsp'] = 'succ';
            $rs['msg'] = '不处理交易状态:'.$trade_status.'交易数据';
            return $rs;
        }else{
            #组织账单数据
            $billObj = kernel::single('finance_bill');
            $matching_flag = false;
            $bill_sdf = array(
                'money' => $io_type == 'out' ? -$money : $money,
                'fee_obj' => '淘宝',
                'trade_time' => $record['modified_time'],
                'member' => $record['opposite_user_id'],
                'channel_id' => $shop_detail['shop_id'],
                'channel_name' => $shop_detail['shop_name'],
                'credential_number' => $alipay_trade_bn,
                'charge_status' => '1',//记账状态
                'memo' => $order_title,
                'unique_id' => md5($alipay_trade_bn.$trade_time.'-'.$money.'-'.$balance)
            );
            $confirm_bill_sdf = $bill_sdf;

            if ($order_type == 'TRADE' && $trade_status == 'TRADE_FINISHED'){
                #------------销售-->销售收款
                $bill_sdf['order_bn'] = str_replace('T200P','',$order_bn);
                $bill_sdf['fee_item'] = $io_type == 'in' ? '销售收款' : '销售退款';
                $matching_flag = true;
            }elseif (in_array($order_type,array('CAE','CAR_R')) && $trade_status == 'ACC_FINISHED' && substr($order_bn,0,5) == 'HJCOM'){
                #平台费用-->佣金
                $tmp_order_bn = explode('=',$order_bn);
                if (empty($tmp_order_bn[3])){
                    preg_match('/\{(\d+)\}/',$order_title,$tmp_order_title);
                    $oid = $tmp_order_title[1];
                }else{
                    $oid = $tmp_order_bn[3];
                }
                #单号可能为淘宝子订单号，需要转换订单号
                $merchant_order_bn = '';
                if ($instance = kernel::service('service.order')){
                    if (method_exists($instance,'getOrderBnByoid')){
                        $merchant_order_bn = $instance->getOrderBnByoid($oid,$node_id);
                    }
                }
                if ($merchant_order_bn){
                    $bill_sdf['order_bn'] = $merchant_order_bn;
                    $matching_flag = true;
                }
                $bill_sdf['fee_item'] = '佣金';
            }elseif ($order_type == 'CAE' && $trade_status == 'ACC_FINISHED' && (substr($order_bn,0,9) == 'CAE_POINT' || strstr($order_title,'代扣返点积分'))){
                #-----------平台费用-->积分
                preg_match('/\d+/',$order_title,$tmp_order_title);
                $bill_sdf['order_bn'] = $tmp_order_title[0];
                $bill_sdf['fee_item'] = '积分';
                $matching_flag = true;
            }elseif ($order_type == 'CAE' && $trade_status == 'ACC_FINISHED' && strstr($order_title,'淘宝客佣金代扣')){
                #-----------其他费用-->营销
                preg_match('/\d+/',$order_title,$tmp_order_title);
                $merchant_order_bn = $tmp_order_title[0];
                #单号可能为淘宝子订单号，需要转换订单号
                if($merchant_order_bn = kernel::single('finance_func')->getOrderBnByoid($merchant_order_bn,$node_id)){
                    $bill_sdf['order_bn'] = $merchant_order_bn;
                    $matching_flag = true;
                }
                $bill_sdf['fee_item'] = '营销';
            }elseif ($order_type == 'CHARGE' && $trade_status == 'CHARGE_FINISHED'){
                #-----------其他费用-->信用卡手续费:对于无法获取到订单号的，则归入到无归属账单
                if ($bill_order_bn = $billObj->getOrderBnByNo($order_bn)){
                    $bill_sdf['order_bn'] = $bill_order_bn;
                    $matching_flag = true;
                }
                $bill_sdf['fee_item'] = '信用卡手续费';
                $bill_sdf['fee_obj'] = '支付宝';
            }elseif (in_array($order_type,array('CAE','CAR_R')) && $trade_status == 'ACC_FINISHED' && substr($order_bn,0,14) == 'BTC_BIZORDERID'){
                #-----------其他费用-->运费险
                $tmp_title = explode('INSD',$order_title);
                $bill_sdf['order_bn'] = $tmp_title[1];
                $bill_sdf['fee_item'] = '运费险';
                $matching_flag = true;
            }
           
            #匹配上的单据，需判断订单号是否存在
            if($matching_flag == true){
                $funcObj = kernel::single('finance_func');
                if(!$funcObj->order_is_exists($bill_sdf['order_bn'],$node_id)){
                    $matching_flag = false;
                }
            }

            #存储帐单
            if ($matching_flag == true && isset($bill_sdf['fee_item']) && $billObj->is_exist_item_by_table($bill_sdf['fee_item'])){
                #正式账单
                $save_rs = $billObj->do_save($bill_sdf);
                $save_rs['status'] = $save_rs['status'] == 'fail' && $save_rs['msg_code'] == 'exists' ? 'success' : $save_rs['status'];
            }else{
                #无归属账单
                $sdf_temp = array(
                    'order_bn' => $order_bn,
                    'fee_item' => $bill_sdf['fee_item'],
                    'fee_obj' => $bill_sdf['fee_obj'],
                    'fee_obj_code' => $record['order_from'],
                    'order_type' => $record['order_type'],
                    'order_status' => $record['order_status'],
                    'order_title' => $order_title,
                    'money' => $money,
                    'balance' => $balance,
                    'in_out_type' => $io_type,
                    'trade_no' => $alipay_trade_bn,
                    'trade_account' => $trade_account,
                    'memo' => '',
                    'unique' => $confirm_bill_sdf['unique_id']
                );
                $confirm_bill_sdf = array_merge($confirm_bill_sdf,$sdf_temp);
                $save_rs = $billObj->add_confirm_bill($confirm_bill_sdf);
            }
            $rs['rsp'] = $save_rs['status'] == 'success' ? 'succ' : 'fail';
            $rs['msg'] = $save_rs['msg'];
        }
        return $rs;
    }

}