<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 账单核算自动对账任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_verificationProcess extends financebase_autotask_task_init
{
    private function cmp_money($a,$b){
        if(0 == bccomp((float) $a['money'],(float) $b['money'],2) ){
            return 0;
        }

        return (bccomp((float) $a['money'],(float) $b['money'],2) == -1) ? 1 : -1;
    }

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info,&$error_msg)
    {
        //修改后不需要单独核销
        return true;
    	$errmsg = array();
        $status = $this->auto_bill($task_info,$errmsg);
        if(!$status)
        {
            $error_msg = $errmsg;
            $this->oFunc->writelog('对账单核算任务-失败','settlement','任务ID:'.$task_info['queue_id']);
            return false;
        }
        
        $this->oFunc->writelog('对账单核算任务-完成','settlement','任务ID:'.$task_info['queue_id']);        
        return true;
    }


    /**
     * auto_bill
     * @param mixed $params 参数
     * @param mixed $errmsg errmsg
     * @return mixed 返回值
     */
    public function auto_bill($params,&$errmsg)
    {

        $storageLib                  = kernel::single('taskmgr_interface_storage');
        $mdlAr                       = app::get('finance')->model('ar');
        $mdlBill                     = app::get('finance')->model('bill');
        $mdlBillVerificationRelation = app::get('finance')->model('bill_verification_relation');
        $mdlBillVerificationError    = app::get('financebase')->model('bill_verification_error');
        $oBill                       = kernel::single('finance_bill');
        $oVerification               = kernel::single('finance_verification');


        $remote_url = $params['queue_data']['remote_url'];

        $local_file = DATA_DIR.'/financebase/tmp_local/'.basename($remote_url);

        $getfile_res = $storageLib->get($remote_url,$local_file);

        $data = json_decode(file_get_contents($local_file),1);

        //核算误差
        $gap_list = array ();
        foreach ($mdlBillVerificationError->getList('rule,is_verify,shop_id,name',array (),0,-1,'priority desc') as $key => $value) {
            $gap_list[$value['shop_id']][] = $value;
        }

        if($data)
        {
            foreach ($data as $row) 
            {
                $order_bn       = $row['order_bn'];
                // $crc32_order_bn = sprintf('%u',crc32($order_bn));
                $shop_id        = $row['shop_id'];

                //实收实退数组
                $bill_order_list = $mdlBill->getList('bill_id,bill_bn,money,bill_type,credential_number,order_bn,monthly_id,trade_time',array(
                    'order_bn'       =>$order_bn,
                    'channel_id'     =>$shop_id,
                    'is_check'       =>1,
                    'status|lthan'   =>2
                ),0,-1,'trade_time');

                // 更新状态为已检查
                $bill_ids = array_column((array) $bill_order_list,'bill_id');
                if ($bill_ids) $mdlBill->update(array('is_check'=>2),array('bill_id|in'=>$bill_ids));

                //应收应退数组
                $ar_order_list = $mdlAr->getList('ar_id,ar_bn,money,ar_type,serial_number,monthly_id,trade_time',array(
                    'order_bn'       =>$order_bn,
                    'channel_id'     =>$shop_id,
                    'status|lthan'   =>2
                ),0,-1,'trade_time');
                $ar_ids = array_column((array) $ar_order_list,'ar_id');
                if ($ar_ids) $mdlAr->update(array('is_check'=>2),array('ar_id|in'=>$ar_ids));

                $monthly_list = array ();
                foreach ($ar_order_list as $key => $value) {
                    $monthly_list[$value['monthly_id']]['ar'][] = $value;
                }
                foreach ($bill_order_list as $key => $value) {
                    $monthly_list[$value['monthly_id']]['bill'][] = $value;
                }
                unset($ar_order_list, $bill_order_list);

                foreach ($monthly_list as $monthly_id => $value) {
                    // 记录GAP
                    $this->_cal_gap($value['ar'], $value['bill'], $gap_list[$shop_id],$monthly_id);
                }
            }
        }
 
        unlink($local_file);
        $storageLib->delete($remote_url);

        return true;        
    }

    private function _cal_gap($ar_list, $bill_list, $gap_list,$monthly_id)
    {
        $mdlAr         = app::get('finance')->model('ar');
        $mdlBill       = app::get('finance')->model('bill');
        $oVerification = kernel::single('finance_verification');

        // $init_time = app::get('finance')->getConf('finance_setting_init_time');
        // $init_time = mktime(0,0,0,date('m'),$init_time['day'],date('Y'));

        $monthly = app::get('finance')->model('monthly_report')->dump($monthly_id,'end_time');

        $salear_id = array ();

        // 判断是否存在还没到账的
        foreach ($ar_list as $key => $value) {
            if ($value['money'] <= 0) {
                unset($salear_id);break;
            }

            if ($value['trade_time'] >= strtotime('-10 day',$monthly['end_time']) && !$bill_list) {
                $salear_id[] = $value['ar_id'];
            }
        }

        if ($salear_id) {
            $mdlAr->update(array ('gap_type' => '已发货未收款'), array ('ar_id' => $salear_id));

            return true;
        }

        $ar_money   = array_sum(array_column((array)$ar_list, 'money'));
        $bill_money = array_sum(array_column((array)$bill_list, 'money'));

        $diff_money =  sprintf('%.3f', $bill_money-$ar_money);

        // if ($diff_money == '0') {
        //     $ar_list && $mdlAr->update(array ('gap_type' => '无误差'), array ('ar_id' => array_column($ar_list, 'ar_id')));
        //     return ;
        // }

        foreach ($gap_list as $gap) {
            $expression = array ();
            foreach ($gap['rule'] as $rule) {
                $expression[] = str_replace('{field}', $diff_money, $this->get_comp($rule['operator'],$rule['operand']));
            }

            $expression = implode(' && ', array_filter($expression));

            eval("\$result=($expression);");

            if ($result) {
                $ar_list   && $mdlAr->update(array ('gap_type' => $gap['name']), array ('ar_id' => array_column($ar_list, 'ar_id')));
                $bill_list && $mdlBill->update(array ('gap_type' => $gap['name']), array ('bill_id' => array_column($bill_list, 'bill_id')));

                // 强制核销
                if ($gap['is_verify'] == 1) {
                    // if ($ar_list && $bill_list) {

                    $oVerification->doAutoVerificate($bill_list, $ar_list, $diff_money == 0 ? 1 : 2);

                    // } 
                    // elseif ($ar_list) {
                    //     $oVerification->doAutoArVerificate($bill_list, $ar_list, 2);
                    // }
                }

                break;
            }
        }
    }

    /**
     * 与平台收支做核销
     * 
     * @return void
     * @author 
     * */
    private function _verificate_shouzhi($ar_order_list, $bill_order_list,$gap_list)
    {
        $is_verify = false;

        $oVerification = kernel::single('finance_verification');
        $mdlAr = app::get('finance')->model('ar');

        // 组织数据 实收分应收 应退
        $bill_check_list = array('income'=>array(),'outcome'=>array());
        foreach ($bill_order_list as $key => $value) {
            $bill_type = $value['bill_type'] ? 'outcome' : 'income';

            $bill_check_list[$bill_type][] = $value;
        }


        foreach ($ar_order_list as $ar) 
        {
            $ar_type = $ar['ar_type'] ? 'outcome' : 'income';

            $verificate_bill = array (); $bill_money = 0;
            foreach ($bill_check_list[$ar_type] as $bill) {

                $verificate_bill[] = $bill; $bill_money += $bill['money'];

                // 如果一单就匹配
                if (0 == bccomp($ar['money'], $bill['money'], 2)) {
                    $verificate_bill = array (0 => $bill); $bill_money = $bill['money'];
                }

                if (0 == bccomp($ar['money'], $bill_money, 2)) {
                    $oVerification->doAutoVerificate($verificate_bill, $ar,1);

                    $is_verify = true;

                    unset($bill_check_list[$ar_type]); break;
                }
            }
        }

        return $is_verify;
    }

        /**
     * 获取_comp
     * @param mixed $type type
     * @param mixed $var var
     * @return mixed 返回结果
     */
    public function get_comp($type,$var)
    {
        $comp = array (
            'nequal'  => '{field}=='.$var, 
            'than'    => '{field}> '.$var, 
            'lthan'   => '{field}< '.$var, 
            'bthan'   => '{field}>='.$var, 
            'sthan'   => '{field}<='.$var,
            'between' => '{field}>='.$var[0].' && '.' {field}<='.$var[1],
        );

        return $comp[$type];
    }

    /**
     * 销退进行核销
     *
     * @return void
     * @author 
     **/
    private function _verificate_xiaotui($ar_order_list)
    {
        $is_verify = false;

        $oVerification = kernel::single('finance_verification');

        // 组织数据 实收分应收 应退
        $income = $outcome = array ();
        foreach ($ar_order_list as $key => $value) {
            // if (date('m') == date('m', $value['trade_time'])) continue;

            if ($value['ar_type']) {
                $outcome[] = $value;
            } else {
                $income[] = $value;
            }
        }

        if (!$outcome || !$income) return false;

        $bill_money = 0; $verificate_bill = array ();
        foreach ($income as $key => $value) {
            foreach ($outcome as $k => $v) {
                $verificate_bill[] = $v; $bill_money += $v['money'];

                // 如果一单就匹配
                if (0 == bccomp($value['money'], abs($v['money']), 2)) {
                    $verificate_bill = array (0 => $v); $bill_money = $v['money'];
                }

                if (0 == bccomp($value['money'], abs($bill_money), 2)) {
                    $oVerification->doAutoArVerificate($verificate_bill, $value);

                    $is_verify = true;

                    unset($outcome); break;
                }
            }
        }

        return $is_verify;
    }
}