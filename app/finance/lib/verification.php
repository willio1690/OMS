<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_verification
{
    /*
    **保存核销日志
    **@params array $sdf = array(
    ‘op_time’=>’操作时间’,
    ‘op_name’=>’操作人’,
    ‘type=>’核销还是应收互冲’,
    ‘money’=>‘核销金额’,
    ‘content’=>’详情’,
    ‘items’=>array(
    '0'=>array(
    ‘bill_id’=>’单据id’,
    ‘bill_bn’=>’单据bn’,
    ‘type’=>’是应收单据还是实收单据’, 
    ‘money’=>’核销金额’,
    ‘trade_time’=>‘账期’,
    ),     
    ),
    );
    **@return array('status'=> 'success/fail','msg'=>'错误信息') 
    */

    public function do_save(&$sdf){
        $rs = $this->verify_data($sdf);
        if($rs['status'] == 'fail') return $rs;
        $res = array('status'=> 'success','msg'=>'');
        $veriObj = &app::get('finance')->model('verification');
        $veriitemObj = &app::get('finance')->model('verification_items');
        //开启事务
        $db = kernel::database();
        $db->beginTransaction();
        $main = array(
            'log_bn'=>$this->gen_log_bn(),
            'op_time'=>$sdf['op_time'],
            'op_name'=>$sdf['op_name'],
            'type'=>$sdf['type'],
            'money'=>$sdf['money'],
            'content'=>serialize($sdf['content']),
            );
        if(!$veriObj->save($main)){
            $res = array('status'=> 'fail','msg'=>'主数据保存失败');
            $db->rollBack();
            return ($res);
        }
        if($sdf['items']){
            foreach($sdf['items'] as $v){
              $items = array(
                'log_id'=>$main['log_id'],
                'bill_id'=>$v['bill_id'],
                'bill_bn'=>$v['bill_bn'],
                'type'=>$v['type'],
                'money'=>$v['money'],
                'trade_time'=>$v['trade_time'],
                );
              if(!$veriitemObj->save($items)){
                $res = array('status'=> 'fail','msg'=>'明细数据保存失败');
                $db->rollBack();
                return ($res);
            }
        }
    }
    $db->commit();
    return $res;
    }

    /*
    *生成销售应收单据号
    */

    public function gen_log_bn(){
        $i = rand(0,99999);
        $veriObj = &app::get('finance')->model('verification');
        do{
            if(99999==$i){
                $i=0;
            }
            $i++;
            $log_bn="LOG".date('Ymd').str_pad($i,5,'0',STR_PAD_LEFT);
            $row = $veriObj->getlist('log_id',array('log_bn'=>$log_bn));
        }while($row);
        return $log_bn;
    }


    /*
    **验证数据的正确性
    **@params array $sdf = array(
    ‘op_time’=>’操作时间’,
    ‘op_name’=>’操作人’,
    ‘type=>’核销还是应收互冲’,
    ‘money’=>‘核销金额’,
    ‘content’=>’详情’,
    ‘items’=>array(
    ‘bill_id’=>’单据id’,
    ‘bill_bn’=>’单据bn’,
    ‘type’=>’是应收单据还是实收单据’, 
    ‘money’=>’核销金额’,
    ‘trade_time’=>‘账期’,
    ),     
    );
    **@return array('status'=>'success/fail','msg'=>'');
    */

    public function verify_data(&$data){
        $res = array('status'=>'success','msg'=>'');
        if(empty($data['op_name'])){
            $res = array('status'=>'fail','msg'=>'操作人不能为空');
        }
        if($data['type'] == ''){
            $res = array('status'=>'fail','msg'=>'业务类型不能为空');
        }
        if($data['money'] ==''){
            $res = array('status'=>'fail','msg'=>'应收金额不能为空');
        }
        foreach ($data['items'] as $v) {
            if($v['bill_id'] == ''){
                $res = array('status'=>'fail','msg'=>'账单id不能为空');
            }
            if($v['bill_bn'] == ''){
                $res = array('status'=>'fail','msg'=>'账单bn不能为空');
            }
            if($v['trade_time'] == ''){
                $res = array('status'=>'fail','msg'=>'账单日不能为空');
            }
        }
        return $res;
    }

    //获取类型的名称 应收互冲核销(0)，应收实收核销(1)
    /**
     * 获取_name_by_type
     * @param mixed $type type
     * @param mixed $flag flag
     * @return mixed 返回结果
     */
    public function get_name_by_type($type='',$flag = ''){
        $data = array(
            '0'=>'应收互冲核销',
            '1'=>'应收实收核销',
            );
        if($flag) return $data;
        return $data[trim($type)];
    }

    /*
    **撤销核销
    **@params $log 核销日志id
    **@return true/false 字符窜
    */

    public function do_cancel($log_id){
    //开启事务
        $db = kernel::database();
        $db->beginTransaction();
        $veriObj = &app::get('finance')->model('verification');
        $veriitemObj = &app::get('finance')->model('verification_items');
        $arObj = &app::get('finance')->model('ar');
        $billObj = &app::get('finance')->model('bill');
        $log_data = $veriitemObj->getList('item_id,bill_id,type,money',array('log_id'=>$log_id));
        $tmp = $delelt_item = array();
        foreach($log_data as $v){
            $tmp[$v['type']][] = $v;
            $delelt_item[] = $v['item_id'];
        }
        foreach($tmp as $type=>$v){
            if($type==0){
                foreach($v as $value){
                    $rs_bill = $billObj->do_cancel($value['bill_id'],$value['money']);
                    if($rs_bill == 'false'){
                        $bill_flag = 'true';
                        break;
                    }
                }
            }else{
                foreach($v as $value){
                    $rs_ar = $arObj->do_cancel($value['bill_id'],$value['money']);
                    if($rs_ar == 'false'){
                        $ar_flag = 'true';
                        break;
                    }
                }
            }
        }
        if($ar_flag == 'true' || $bill_flag == 'true'){
            $db->rollBack();
            return 'false';
        }
        $rs = $veriObj->delete(array('log_id'=>$log_id));
        $rs_item = $veriitemObj->delete(array('item_id'=>$delelt_item));
        if(!$rs || !$rs_item){
            $db->rollBack();
            return 'false';
        }
        $db->commit();
        return 'true';
    }

    /*
    **获取下一单数据
    **@params $bill_id 时候单据id
    **
    */

    public function get_next_data($bill_id){
        $billObj = &app::get('finance')->model('bill');
        $next_data =  $billObj->getList('bill_id',array('status|noequal'=>2,'charge_status'=>1,'fee_type_id'=>'1'));        
        foreach($next_data as $k=>$v){
            if($v['bill_id'] == $bill_id){
                return $next_data[$k+1];
            }
        }
    }



    // 人工核销
    /**
     * doManVerificate
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function doManVerificate($params)
    {
        $res = $this->checkVerificate($params);

        $mdlBill = app::get('finance')->model('bill');
        $mdlAr = app::get('finance')->model('ar');
        $mdlBillVerificationRelation = app::get('finance')->model('bill_verification_relation');

        if($res['status'] != 'success') return $res;

        $bill_list = $ar_list = array();

        // 获取实收实退记录
        $params['bill_id'] and $bill_list = $mdlBill->getList('money,bill_id,bill_bn,order_bn,channel_id,monthly_id,monthly_item_id',array('bill_id|in'=>$params['bill_id'],'status|lthan'=>2));

        // 获取应收应退记录
        $params['ar_id'] and $ar_list = $mdlAr->getList('money,ar_id,ar_bn,serial_number,monthly_id,monthly_item_id',array('ar_id|in'=>$params['ar_id'],'status|lthan'=>2));
        
        // $monthly_id = max(array_column($ar_list,'monthly_id'));
        $monthly_item_id = [];
        // 开始处理
        $db = kernel::database();
        $db->beginTransaction();
        $verification_time = time();
        $op_name = kernel::single('desktop_user')->get_name();
        $verification_status_ref = array(1=>'正常核销',2=>'差异核销',3=>'强制核销');

        try {

            if($res['verification_status'] == 2)
            {
                $memo = sprintf("单据金额相差：%s 元",$res['money_error']);
            }
            elseif ($res['verification_status'] == 3) 
            {
                $memo = $params['verification_memo'];
            }
            else
            {
                $memo = '正常核销';
            }


            // 更新实收实退单据表
            foreach ($bill_list as $v) {
                $monthly_item_id[$v['monthly_item_id']] = $v['monthly_item_id'];
                $data = array();
                if(empty($params['ar_id'])) {
                    $data['verification_flag'] = 1;
                }
                $data['verification_time'] = $verification_time;
                $data['status'] = 2;
                $data['is_check'] = 2;
                $data['confirm_money'] = $v['money'];
                $data['unconfirm_money'] = 0;
                $data['verification_status'] = $res['verification_status'];
                $data['memo'] = $memo;
                if(!$mdlBill->update($data,array('bill_id'=>$v['bill_id'],'status|lthan'=>2)))
                {
                    throw new Exception('更新应收应退单据表失败');
                }

                finance_func::addOpLog($v['bill_bn'],$op_name,$verification_status_ref[$res['verification_status']],'核销');
            }


            $modify_monthly_list = array ();

            // 更新应收应退单据表
            foreach ($ar_list as $v) {
                $monthly_item_id[$v['monthly_item_id']] = $v['monthly_item_id'];
                $data = array();
                if(empty($params['bill_id'])) {
                    $data['verification_flag'] = 1;
                }
                $data['verification_time'] = $verification_time;
                $data['status'] = 2;
                $data['is_check'] = 2;
                $data['confirm_money'] = $v['money'];
                $data['unconfirm_money'] = 0;
                $data['verification_status'] = $res['verification_status'];
                $data['memo'] = $memo;
                if(!$mdlAr->update($data,array('ar_id'=>$v['ar_id'],'status'=>0)))
                {
                    throw new Exception('更新应收应退单据表失败');
                }
                finance_func::addOpLog($v['ar_bn'],$op_name,$verification_status_ref[$res['verification_status']],'核销');
            }

            $db->commit();
            
        } catch (Exception $e) {

            $res['status'] = 'fail';
            $res['msg'] = $e->getMessage();
            $db->rollBack();
            return $res;
        }
        $oMRI = app::get('finance')->model('monthly_report_items');
        foreach($monthly_item_id as $itemId) {
            if(!app::get('finance')->model('bill')->db_dump(['status|noequal'=>'2', 'monthly_item_id'=>$itemId], 'bill_id')
                && !app::get('finance')->model('ar')->db_dump(['status|noequal'=>'2', 'monthly_item_id'=>$itemId], 'ar_id')
            ) {
                $oMRI->update(['memo'=>$memo, 'verification_status'=>'2'], ['id'=>$itemId]);
            }
        }
        return $res;

    }

    /**
     * 自动核销
     * 
     * @param      Array  $bill_data            实收实退数据
     * @param      Array  $ar_data              应收应退数据
     * @param      Int    $verification_status  核销状态  正常核销(1)、差异核销(2)、强制核销(3) 
     */
    public function doAutoVerificate($bill_data=array(),$ar_data=array(),$verification_status=1)
    {
        
        if(!$bill_data && !$ar_data) return false;


        $mdlBill = app::get('finance')->model('bill');
        $mdlAr = app::get('finance')->model('ar');
        $mdlBillVerificationRelation = app::get('finance')->model('bill_verification_relation');
        $oFunc = kernel::single('financebase_func');

        // 开始处理
        $db = kernel::database();
        $db->beginTransaction();
        $verification_time = time();
        $op_name = 'system';

        try {
            $verification_status_ref = array(1=>'正常核销',2=>'差异核销',3=>'强制核销');

            // $bill_money = 0;
            foreach ($bill_data as $key => $value) {
                 // 更新核销流水表
                $data = array();
                $data['verification_time']   = $verification_time;
                $data['status']              = 2;
                $data['confirm_money']       = $value['money'];
                $data['unconfirm_money']     = 0;
                $data['verification_status'] = $verification_status;
                $data['auto_flag']           = 1;

                if($value['bill_id'] && !$mdlBill->update($data,array('bill_id'=>$value['bill_id'],'status|lthan'=>2)))
                {
                    throw new Exception('更新流水表失败');
                }

                finance_func::addOpLog($value['bill_bn'],$op_name,$verification_status_ref[$verification_status],'核销');

                // $bill_money += $value['money'];
            }

            // 更新ar单据表 和关联表
            foreach ($ar_data as $key => $value) {
                $data = array();
                $data['verification_time']   = $verification_time;
                $data['verification_status'] = $verification_status;
                $data['status']              = 2;
                $data['confirm_money']       = $value['money'];
                $data['unconfirm_money']     = 0;
                $data['memo']                ='实收实退单据号:'. ($bill_data ? implode('|', array_column($bill_data,'bill_bn')) : '') ;
                $data['auto_flag']           = 1;
                // $data['is_check']            = 2;

                if(!$mdlAr->update($data,array('ar_id'=>$value['ar_id'],'status'=>0)))
                {
                    throw new Exception('更新ar单据表失败');
                }
            }

            $db->commit();
            
        } catch (Exception $e) {
            $oFunc->writelog('自动对账关联失败原因：','verificate',$e->getMessage());
            $db->rollBack();
            return false;
        }
        return true;
    }

    /**
     * 应收应退自动核销(针对未签收直接退款的情况)
     * @param  array  $ar_outcome_data [description]
     * @param  array  $ar_data         [description]
     * @return [type]                  [description]
     */
    public function doAutoArVerificate($ar_outcome_data=array(),$ar_data=array(),$verification_status=1)
    {
        
        if(!$ar_outcome_data || !$ar_data) return false;

        $mdlAr = app::get('finance')->model('ar');
        $oFunc = kernel::single('financebase_func');

        // 开始处理
        $db = kernel::database();
        $db->beginTransaction();
        $verification_time = time();
        $op_name = 'system';

        try {

            $bill_money = 0;
            foreach ($ar_outcome_data as $key => $value) {
                $data = array ();
                $data['verification_time'] = $verification_time;
                $data['status']            = 2;
                $data['confirm_money']     = $value['money'];
                $data['unconfirm_money']   = 0;
                $data['memo']              = '应收单据号:'. $ar_data['ar_bn'];
                $data['auto_flag']         = 1;
                $data['is_check']          = 2;
                // $data['gap_type']          = '无差异';

                if(!$mdlAr->update($data,array('ar_id'=>$value['ar_id'],'status'=>0)))
                {
                    throw new Exception('更新ar单据表失败');
                }

                $bill_money += $value['money'];
            }

            // 更新ar单据表 和关联表
            $data = array();
            $data['verification_time'] = $verification_time;
            $data['status']            = 2;
            $data['confirm_money']     = abs($bill_money);
            $data['unconfirm_money']   = 0;
            $data['memo']              = '应退单据号:'. implode('|', array_column($ar_outcome_data,'ar_bn'));
            $data['auto_flag']         = 1;
            $data['is_check']          = 2;
            $data['verification_status'] = $verification_status;
            // $data['gap_type'] = $verification_status == 1 ? '无差异' : sprintf('%s元相差',abs(bcsub($bill_money,$ar_data['money'],2)));


            if(!$mdlAr->update($data,array('ar_id'=>$ar_data['ar_id'],'status'=>0)))
            {
                throw new Exception('更新ar单据表失败');
            }

            $db->commit();
            
        } catch (Exception $e) {
            $oFunc->writelog('自动对账关联失败原因：','verificate',$e->getMessage());
            $db->rollBack();
            return false;
        }
        return true;
    }

    /**
     * 检查Verificate
     * @param mixed $params 参数
     * @return mixed 返回验证结果
     */
    public function checkVerificate($params)
    {
        $res = array('status'=>'success','msg'=>'','title'=>'','money'=>0,'money_error'=>0);
        $mdlBill = app::get('finance')->model('bill');
        $mdlAr = app::get('finance')->model('ar');
        $mdlBillVerificationError = app::get('financebase')->model('bill_verification_error');
        $mdlBillVerificationRelation = app::get('finance')->model('bill_verification_relation');

        $params['is_verification'] = intval($params['is_verification']);

        $shop_id = $params['shop_id'];
        $bill_money = 0;
        $ar_money = 0;
        $bill_list = $ar_list = array();

        // 获取实收实退金额
        if($params['bill_id'])
        {
            $bill_list = $mdlBill->getList('money,bill_id,bill_bn,order_bn',array('bill_id|in'=>$params['bill_id'],'status|lthan'=>2));
            foreach ($bill_list as $v) 
            {
                $bill_money += $v['money'];
            }
        }


        // 获取应收应退记录
        if($params['ar_id'])
        {
            $ar_list = $mdlAr->getList('money,ar_id,ar_bn,serial_number',array('ar_id|in'=>$params['ar_id'],'status|lthan'=>2));
            // $ar_list = array_column($ar_list,null,'ar_bn');
            foreach ($ar_list as $v) 
            {
                $ar_money += $v['money'];
            }   
        }
        


        if($params['is_verification'])
        {

            if(!$params['verification_memo'])
            {
                $res['status']='fail';
                $res['msg'] = '填写强制核销备注';
                return $res;
            }

            $res['title'] = '确认强制核销';
            $res['msg'] = '当前实收实退是否最终确认强制核销？';
            $res['money'] = $bill_money;
            $res['ar_money'] = $ar_money;
            $res['money_error'] = 0;
            $res['verification_status'] = 3;
            
        }
        else
        {

            if(!$bill_list)
            {
                $res['status']='fail';
                $res['msg'] = '实收实退单不存在或者已核销';
                return $res;
            }

            if(!$ar_list)
            {
                
                $res['status']='fail';
                $res['msg'] = '应收应退单不存在或者已核销';
                return $res;
            }


            // 获取核销误差值
            $verificationError = $mdlBillVerificationError->getRow('money',array('shop_id'=>$shop_id));
            $verification_error_money = $verificationError ? sprintf("%.2f",$verificationError['money']) : 0;

            
            

            

            $money_diff = abs(bcsub($bill_money,$ar_money,2));

            if( $money_diff == 0 )
            {
                $res['title'] = '确认核销';
                $res['msg'] = '当前实收实退金额与应收应退金额相等，是否最终确认核销？';
                $res['money'] = $bill_money;
                $res['verification_status'] = 1;
            }
            elseif ( $money_diff <= $verification_error_money ) 
            {
                $res['title'] = '确认误差核销';
                $res['msg'] = '当前实收实退金额与应收应退金额不等，但在店铺核销误差范围内，是否最终确认核销？';
                $res['money'] = $bill_money;
                $res['money_error'] = $money_diff;
                $res['verification_status'] = 2;
            }
            else
            {
                $res['status']='fail';
                $res['msg'] = '单据金额不相等';
                return $res;
            }

        }

        return $res;
    }
}