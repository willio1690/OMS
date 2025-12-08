<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_bill{
    /*
    *费用项名称，从表中检查费用项是否已存在（添加费用项用）
    **@params varchar（255） $fee_item 费用项名称
    **return bool
    */

    public function is_exist_item_by_table($fee_item){
        $feeitemObj = &app::get('finance')->model('bill_fee_item');
        $data = $feeitemObj->getList('fee_item_id',array('fee_item'=>$fee_item,'delete'=>'false'));
        if($data){
            return true;
        }else{
            return false;
        }
    }

    /*
    *费用项名称，从KV检查费用项是否已存在（导入账单用）
    **@params varchar（255） $fee_item 费用项名称
    **return bool
    */

    public function is_exist_item_by_kv($fee_item){
        $fee_item_kv = app::get('finance')->getConf('fee_item');
        $array = array();
        foreach ($$fee_item_kv as $key => $value) {
            foreach ($value['item'] as $k => $v) {
                $array[] = $v['name'];
            }
        }
        if(in_array($fee_item, $array)){
            return true;
        }else{
            return false;
        }
    }

    /*
    *通过费用类ID，费用项名称，保存费用项数据
    **@params int $fee_type_id费用类ID
    **@params varchar（255） $fee_item 费用项名称
    **return sdf
    */

    public function add_fee_item($fee_type_id,$fee_item){
        $feeitemObj = &app::get('finance')->model('bill_fee_item');
        $data = $feeitemObj->getList('fee_item_id',array('fee_item'=>$fee_item));
        if($data){
            if($data[0]['delete'] == 'false'){
                return false;
            }
            $savedata = array(
                'fee_item_id'=>$data[0]['fee_item_id'],
                'delete'=>'false',
                );
            $rs = $feeitemObj->save($savedata);
            if(!$rs){
                return false;
            }
        }else{
            $savedata = array(
                'fee_type_id'=>$fee_type_id,
                'fee_item'=>$fee_item,
                );
            $rs = $feeitemObj->save($savedata);
            if(!$rs){
                return false;
            }
            $fee_item_kv = app::get('finance')->getConf('fee_item');
            $fee_item_kv[$fee_type_id]['item'][$savedata['fee_item_id']]['inlay'] = 'false';
            $fee_item_kv[$fee_type_id]['item'][$savedata['fee_item_id']]['name'] = $fee_item;
            app::get('finance')->setConf('fee_item',$fee_item_kv);
        }
        return $savedata;
    }

    /*
    **通过费用项获取费用项ID，费用类ID
    **@params $fee_item varchar(255) 费用项名称
    **@return sdf array('fee_type_id'=>'','fee_item_id'=>'','fee_type'=>'')
    */
    /**
     * 获取_fee_by_fee_item
     * @param mixed $fee_item fee_item
     * @return mixed 返回结果
     */
    public function get_fee_by_fee_item($fee_item){
        $is_exist = $this->is_exist_item_by_table($fee_item);
        if($is_exist == false){
            return false;
        }
        $feeitemObj = &app::get('finance')->model('bill_fee_item');
        $data_tmp = $feeitemObj->getList('fee_item_id,fee_type_id',array('fee_item'=>$fee_item,'delete'=>'false'));
        $data['fee_item_id'] = $data_tmp[0]['fee_item_id'];
        $data['fee_type_id'] = $data_tmp[0]['fee_type_id'];
        $tmp = &app::get('finance')->model('bill_fee_type')->getList('fee_type',array('fee_type_id'=>$data_tmp[0]['fee_type_id']));
        $data['fee_type'] = $tmp[0]['fee_type'];
        return $data;
    }

    /*
    **通过费用项ID获取费用类的总和
    **@params $fee_type_id int 费用类ID
    **@params $fee_item_id int 费用项ID
    **@params $time_from time 开始时间
    **@params $time_to time 结束时间
    **@return sdf array('trade'=>'交易','plat'=>'平台费用','branch'=>'仓储费用','delivery'=>'物流费用','other'=>'其他费用')
    */

    public function get_fee_type_money_by_fee_item_id($fee_type_id,$fee_item_id,$time_from,$time_to){
        $time_from = kernel::single('ome_func')->date2time($time_from);
        $time_to = kernel::single('ome_func')->date2time($time_to);
        $billObj = &app::get('finance')->model('bill');
        $data = array('trade'=>'0','plat'=>'0','branch'=>'0','delivery'=>'0','other'=>'0');
        if(empty($fee_item_id)){
            if(empty($fee_type_id)){
                //显示全部费用类的费用
                $rs = $billObj->getList('money,fee_type_id',array('trade_time|bthan'=>$time_from,'trade_time|lthan'=>($time_to + 86400)));
            }else{
                //显示选中费用类的费用
                $rs = $billObj->getList('money,fee_type_id',array('trade_time|bthan'=>$time_from,'trade_time|lthan'=>($time_to + 86400),'fee_type_id'=>$fee_type_id));     
            }
        }else{
            $rs = $billObj->getList('money,fee_type_id',array('trade_time|bthan'=>$time_from,'trade_time|lthan'=>($time_to + 86400),'fee_item_id'=>$fee_item_id));     
        }
        if($rs){
            foreach ($rs as $key => $value) {
                $data[$this->_get_pre_by_fee_type_id($value['fee_type_id'])] += $value['money'];
            }
        }
        return $data;
    }

    /*
    **保存数据
    **@params $sdf array(
        ‘order_bn’=>’业务单据号bn’,
        ‘member’=>’客户/会员或交易对方ID’,
        ‘channel_id=>’渠道ID（店铺，仓库）’,
        ‘channel_name’=>’渠道名称（店铺，仓库）’,
        ‘trade_time’=>’单据的完成日期’,
        ‘fee_obj’=>’费用对象’,
        ‘money’=>’区分正负’,
        ‘fee_item’=>’费用项名称’
        'credential_number'=>'凭据号',
        'unique_id'=>'md5()',
        ‘charge_status’=>‘记账状态  未记账(0)、已记账(1) 导入或新建为未记账 api为已记账,除去api其余可不传此字段’,
        ‘memo’=>‘备注’,
        )
    **@return array('status'=> 'success/fail','msg'=>'错误信息') 
    */

    public function do_save(&$sdf){
        $rs = $this->verify_data($sdf);
        if($rs['status'] == 'fail') return $rs;
        $res = array('status'=> 'success','msg'=>'');
        $billObj = &app::get('finance')->model('bill');
        $sdf['bill_bn'] = $this->gen_bill_bn();
        $fee_id = $sdf['fee_id'];
        unset($sdf['fee_id']);
        $sdf['fee_item_id'] = $fee_id['fee_item_id'];
        $sdf['fee_type_id'] = $fee_id['fee_type_id'];
        $sdf['fee_type'] = $fee_id['fee_type'];
        $sdf['create_time'] = time();#系统单据生成时间，必填
        $sdf['crc32_order_bn'] = sprintf('%u',crc32($sdf['order_bn']));
        $sdf['memo'] = serialize($sdf['memo']);
        $sdf['unconfirm_money'] = $sdf['money'];#未核销金额默认为应收金额
        #用费用对象名称做crc32 维护Kv 以便查询
        $fee_obj_id = $this->set_fee_obj_id($sdf['fee_obj']);
        $sdf['fee_obj_id'] = $fee_obj_id;
        if(!$billObj->save($sdf)){
            $res = array('status'=> 'fail','msg'=>'保存失败');
            return ($res);
        }
        return $res;
    }

    /**
    * 添加待确认帐单数据
    * @params $sdf 待确认账单数据
    *    $sdf = array(
    *        ‘order_bn’=>’商户订单号’,
    *        'trade_no'=>'凭据号,即交易订单号',
    *        ‘money’=>’’,
    *        ‘in_out_type’=>’in:收入 out:支出’,
    *        ‘channel_id’=>’’,
    *        ‘channel_name’=>’’,
    *        'trade_time'=>’交易时间’,
    *        ’order_type' => '订单类型',
    *        'order_status' => '订单状态',
    *        'order_title' => '订单标题描述',
    *        ‘fee_item’=>’费用项名称’
    *        ‘fee_obj’=>’费用对象’,
    *        ‘fee_obj_code’=>’费用对象编码’,
    *        'trade_account' => '交易对方’,
    *        ‘unique’ => '数据唯一性,不提供将取:md5(凭据号)
    *    )
    * @return array('status'=> 'success/fail','msg'=>'错误信息') 
    */

    public function add_confirm_bill(&$sdf){
        $rs = array('status'=> 'fail','msg'=>'');
        if (empty($sdf)){
            $rs['status'] = 'success';
            $rs['msg'] = '交易数据为空';
            return $rs;
        }
        if (empty($sdf['unique'])){
            $sdf['unique'] = $sdf['trade_no'] ? md5($sdf['trade_no']) : md5($sdf['order_bn'].$sdf['money'].$sdf['in_out_type'].$sdf['trade_time']);
        }
        $billConfirmObj = &app::get('finance')->model('bill_confirm');
        if ($billConfirmObj->count(array('unique'=>$sdf['unique']))){
            $rs['msg'] = '数据已存在';
            $rs['status'] = 'success';
            return $rs;
        }

        #处理交易时间
        $time = time();
        $trade_time = $sdf['trade_time'];
        if(empty($trade_time)){
            $sdf['trade_time'] = $time;
        }else{
            $sdf['trade_time'] = strtotime($trade_time) ? strtotime($trade_time) : $trade_time;
        }
        $sdf['create_time'] = $time;

        if($billConfirmObj->save($sdf)){
            $rs['status'] = 'success';
            $rs['msg'] = '保存成功';
        }else{
            $rs['msg'] = '保存失败';
        }
        return $rs;
    }

    /*
    * 通过凭据号获取订单号
    * @params String $credential_number 凭据号
    * @retun Array 账单信息
    */

    public function getOrderBnByNo($credential_number){
        if (empty($credential_number)) return NULL;        

        $billObj = &app::get('finance')->model('bill');
        $bills = $billObj->getList('order_bn',array('credential_number'=>$credential_number),0,1);    

        return isset($bills[0]['order_bn']) ? $bills[0]['order_bn'] : '';
    }

    /*
    **通过费用对象设置费用对象对应的crc32值
    **@params $fee_obj varchar(255) 费用对象名称
    **@return crc32的值
    */

    public function set_fee_obj_id($fee_obj){
        if(empty($fee_obj)){
            return null;
        }
        $fee_obj = trim($fee_obj);
        $fee_obj = md5($fee_obj);
        $crc32_fee_obj = sprintf('%u',crc32($fee_obj));
        $fee_obj_kv = app::get('finance')->getConf('fee_obj');
        if(isset($fee_obj_kv[$crc32_fee_obj])){
            if($fee_obj_kv[$crc32_fee_obj] != $fee_obj){
                $crc32_fee_obj_tmp = sprintf('%u',crc32($fee_obj.' '));
                $fee_obj_kv[$crc32_fee_obj_tmp] = $fee_obj;
                app::get('finance')->setConf('fee_obj',$fee_obj_kv);
                return $crc32_fee_obj_tmp;
            }
            return $crc32_fee_obj;
        }else{
            $fee_obj_kv[$crc32_fee_obj] = $fee_obj;
            app::get('finance')->setConf('fee_obj',$fee_obj_kv);
            return $crc32_fee_obj;
        }
    }

    /*
    **通过费用对象获取费用对象对应的crc32值
    **@params $fee_obj varchar(255) 费用对象名称
    **@return crc32
    */

    public function get_fee_obj_id($fee_obj){
        if(empty($fee_obj)){
            return NULL;
        }
        $fee_obj = trim($fee_obj);
        $crc32_fee_obj = sprintf('%u',crc32($fee_obj));
        $fee_obj_kv = app::get('finance')->getConf('fee_obj');
        if(isset($fee_obj_kv[$crc32_fee_obj])){
            if($fee_obj_kv[$crc32_fee_obj] != $fee_obj){
                $crc32_fee_obj_tmp = sprintf('%u',crc32($fee_obj.' '));
                if(isset($fee_obj_kv[$crc32_fee_obj_tmp])){
                    return $crc32_fee_obj_tmp;
                }
            }else{
                return $crc32_fee_obj;
            }
        }else{
            return $crc32_fee_obj;
        }
    }

    /*
    **通过费用类获取该分类下边的费用项
    **@params $fee_type_id int 费用类id
    **@return array array('费用项ID'=>'费用项名称')
    */
    /**
     * 获取_fee_item_by_fee_type_id
     * @param mixed $fee_type_id ID
     * @return mixed 返回结果
     */
    public function get_fee_item_by_fee_type_id($fee_type_id){
        $fee_item_kv = app::get('finance')->getConf('fee_item');
        $data = array();
        if(empty($fee_type_id)){
            foreach ($fee_item_kv as $key => $value) {
                foreach ($value['item'] as $k => $v) {
                    $data[$k] = $v['name'];
                }
            }
        }else{
            foreach($fee_item_kv[$fee_type_id]['item'] as $key=>$value){
                $data[$key] = $value['name'];
            }
        }
        return $data;
    }

    /*
    **通过费用类ID获取前缀值
    **@params $fee_type_id int 费用类id
    **@return 自定义前缀
    */

    public function _get_pre_by_fee_type_id($fee_type_id){
        $data = array('1'=>'trade','2'=>'plat','3'=>'branch','4'=>'delivery','5'=>'other');
        return $data[$fee_type_id];
    }

    /*
    **通过订单bn获取各个费用类的金额总和
    **@params $order_bn varchar(32) 订单bn
    **@params $time_from 开始时间
    **@params $time_to 借书时间
    **@return 自定义前缀
    */

    public function get_total_money_order_bn($order_bn,$time_from='',$time_to=''){
        $billObj = &app::get('finance')->model('bill');
        $filter = array('order_bn'=>$order_bn,'charge_status'=>1);
        if ($time_from && $time_to) {
            $filter['trade_time|between'] = array($time_from,$time_to);
        }else if ($time_from && !$time_to) {
            $filter['trade_time|than'] = $time_from;
        } else if (!$time_from && $time_to) {
            $filter['trade_time|lthan'] = $time_to;
        }
        
        $data = $billObj->getList('money,fee_type_id',$filter);
        $rs = array('trade'=>'0','plat'=>'0','branch'=>'0','delivery'=>'0','other'=>'0');
        foreach ($data as $key => $value) {
            $rs[$this->_get_pre_by_fee_type_id($value['fee_type_id'])] += $value['money'];
            $rs['total'] += $value['money'];
        }
        return $rs;
    }


    /*
    **通过条件获取费用类的总和
    **@params $time_from time 开始时间
    **@params $time_to time 结束时间
    **@params $shop_id varchar(32) 约束条件
    **@return sdf array('trade'=>'交易','plat'=>'平台费用','branch'=>'仓储费用','delivery'=>'物流费用','other'=>'其他费用')
    */

    public function get_fee_type_money_by_shop_id($time_from,$time_to,$shop_id=null){
        $time_from = kernel::single('ome_func')->date2time($time_from);
        $time_to = kernel::single('ome_func')->date2time($time_to);
        $billObj = &app::get('finance')->model('bill');
        $data = array('trade'=>'0','plat'=>'0','branch'=>'0','delivery'=>'0','other'=>'0');
        if($shop_id) $filter = array('charge_status'=>1,'trade_time|between'=>array($time_from,($time_to + 86400)),'channel_id'=>$shop_id);
        else $filter = array('charge_status'=>1,'trade_time|between'=>array($time_from,($time_to + 86400)));
        $rs = $billObj->getList('money,fee_type_id',$filter);
        if($rs){
            foreach ($rs as $key => $value) {
                $data[$this->_get_pre_by_fee_type_id($value['fee_type_id'])] += $value['money'];
            }
        }
        return $data;
    }

    /*
    *生成账单bn
    */
    /**
     * gen_bill_bn
     * @return mixed 返回值
     */
    public function gen_bill_bn(){
        $prefix = "RB".date("YmdHis");
        $sign = kernel::single('eccommon_guid')->incId('finance_bill', $prefix, 7, true);
        return $sign;
        /*$i = rand(0,999999);
        $billObj = &app::get('finance')->model('bill');
        do{
            if(999999==$i){
                $i=0;
            }
            $i++;
            $bill_bn="RB".date('YmdHis').str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $billObj->getlist('bill_id',array('bill_bn'=>$bill_bn));
        }while($row);
        return $bill_bn;*/
    }

    /*
    *判断单据完成时间存在于哪个账期
    *@params $time 单据的完成时间
    *@return monthly_id
    */

    public function get_monthly_id_by_time($time){
    #获取初始化设置的值
        $int_data = app::get('finance')->getConf('finance_setting_init_time');
    }

    /*
    **验证数据的正确性
    **@params $sdf array(
    ‘order_bn’=>’业务单据号bn’,
    ‘member’=>’客户/会员或交易对方ID’,
    ‘channel_id=>’渠道ID（店铺，仓库）’,
    ‘channel_name’=>’渠道名称（店铺，仓库）’,
    ‘trade_time’=>’单据的完成日期’,
    ‘fee_obj’=>’费用对象’,
    ‘money’=>’区分正负’,
    ‘fee_item’=>’费用项名称’
    'credential_number'=>'凭据号',
    ‘charge_status’=>‘记账状态  未记账(0)、已记账(1) 导入或新建为未记账 api为已记账,除去api其余可不传此字段’,
    )
    **@return array('res'=>'success','msg'=>'');
    */

    public function verify_data(&$data){
        $res = array('status'=>'success','msg'=>'');
        if(empty($data['order_bn'])){
            $res = array('status'=>'fail','msg'=>'业务单据号不能为空');
            return ($res);
        }
        if(empty($data['fee_obj'])){
            $res = array('status'=>'fail','msg'=>'费用对象不能为空');
            return ($res);
        }
        if(empty($data['money'])){
            $data['money'] = 0;
        }else{
            if(!is_numeric($data['money'])){
                $res = array('status'=>'fail','msg'=>'请输入正确的金额');
                return $res;
            }
        }
        if(empty($data['unique_id'])){
            $res = array('status'=>'fail','msg'=>'唯一标识不能为空');
            return ($res);
        }
        #判断凭据号是否存在
        $billObj = &app::get('finance')->model('bill');
        $unique_id = $data['unique_id'];
        $bill = $billObj->getlist('bill_id',array('unique_id'=>$unique_id),0,1);
        if(!empty($bill[0]['bill_id'])){
            $res = array('status'=> 'fail','msg'=>'该单据已存在','msg_code'=>'exists');
            return ($res);
        }
        #判断费用项是否存在
        $fee_item = $data['fee_item'];
        $exist_item = $this->is_exist_item_by_table($fee_item);
        if(!$exist_item){
            $res = array('status'=> 'fail','msg'=>'费用项'.$fee_item."不存在!");
            return ($res);
        }
        #获取费用项费用类的ID
        $fee_id = $this->get_fee_by_fee_item($fee_item);
        $data['fee_id'] = $fee_id;
        if(!$fee_id){
            $res = array('status'=> 'fail','msg'=>'费用项'.$fee_item."不存在!");
            return ($res);
        }
        #处理单据时间
        $trade_time = $data['trade_time'];
        if(empty($trade_time)){
            $data['trade_time'] = time();
        }else{
            $data['trade_time'] = kernel::single('ome_func')->date2time($trade_time);
        }
        #api来源 判断是否在合法账期内，更改记账状态
        if(isset($data['charge_status']) && $data['charge_status'] == 1){
            #如果是api来源，若账单的账单日期所属的账期未结帐，则状态置为“已记账”；若账单的账单日期所属的账期为已结账，则状态置为“未记账”
            #通过单据完成时间获取月结状态
            $report_status = kernel::single('finance_monthly_report')->get_monthly_report_status_by_time($data['trade_time']);
            if($report_status == 2){
                #已月结的账单把记账状态改为未记账
                $data['charge_status'] = 0;
            }else{
                $data['charge_time'] = time();
            }
        }
        return $res;
    }

    /**
     * 通过记账状态编码获取记账状态名称
     * 
     * */
    public function get_name_by_charge_status($charge_status = '',$flag = ''){
        //记账状态  未记账(0)、已记账(1)
        $data =array(
            '0'=>'未记账',
            '1'=>'已记账',
            );
        if($flag) return $data;
        return $data[trim($charge_status)];
    }

    /**
     * 通过核销状态编码获取核销状态名称
     * 
     * */
    public function get_name_by_status($status = '',$flag = ''){
        //核销状态  未核销(0)、部分核销(1)、已核销(2)
        $data =array(
            '0'=>'未核销',
            '1'=>'部分核销',
            '2'=>'已核销',
            );
        if($flag) return $data;
        return $data[trim($status)];
    }

    // 根据状态给出核销名称
    public function get_name_by_verification_status($status = ''){
        //核销状态  等待核销(0)、正常核销(1)、差异核销(2)、强制核销(3)
        $data =array(
            '0'=>'等待核销',
            '1'=>'正常核销',
            '2'=>'差异核销',
            '3'=>'强制核销',
            );
        return $data[trim($status)];
    }

    /**
     * 通过单据类型编码获取单据类型名称
     * 
     * */
    public function get_name_by_bill_type($status = '',$flag = ''){
        //核销状态  未核销(0)、部分核销(1)、已核销(2)
        $data =array(
            '0'=>'实收单',
            '1'=>'实退单',
            );
        if($flag) return $data;
        return $data[trim($status)];
    }

    /**
     * 通过核销状态编码获取核销状态名称
     * 
     * */
    public function get_name_by_monthly_status($status='',$flag = ''){
        //月结状态 未结帐（0），已结账（1）
        $data =array(
            '0'=>'未结帐',
            '1'=>'已结账',
            );
        if($flag) return $data;
        return $data[trim($status)];
    }

    /*
     * *批量记账
     * *@params $ids array('bill_id'=>array('0'=>'','1'=>''));
     * *@return 'succ/fail  字符串'
     */
    public function do_charge($ids){
        $billObj = app::get('finance')->model('bill');
        $res = array('status'=>'succ','msg'=>'');
        //100个一个组成一个组
        if(count($ids) > 100){
            $ids_tmp = array_chunk($ids,100);
            $count = ceil(count($ids)/100);
            for($i=0;$i<$count;$i++){
                foreach((array)$ids_tmp[$i]['bill_id'] as $id){
                    $tmp = $billObj->getList('trade_time,charge_status',array('bill_id'=>$id));
                    if ($tmp[0]['charge_status'] == 0){
                        $rs = kernel::single('finance_monthly_report')->get_monthly_report_status_by_time($tmp[0]['trade_time']);
                        if($rs == 2){
                            $res = array('status'=>'fail','msg'=>'单据所属账期已月结，批量记账失败!');
                            return $res;
                        }else{
                            $sdf = array('charge_status'=>1,'charge_time'=>time());
                            $filter = array('ar_id'=>$id);
                            if(!$billObj->update($sdf,$filter)){
                                $res = array('status'=>'fail','msg'=>'更改记账状态失败，批量记账失败!');
                                return $res;
                            }
                        }
                    }
                }
            }
        }else{
            if ($ids){
                foreach((array)$ids['bill_id'] as $id){
                    $tmp = $billObj->getList('trade_time,charge_status',array('bill_id'=>$id));
                    if ($tmp[0]['charge_status'] == 0){
                        $rs = kernel::single('finance_monthly_report')->get_monthly_report_status_by_time($tmp[0]['trade_time']);
                        if($rs == 2){
                            $res = array('status'=>'fail','msg'=>'单据所属账期已月结，批量记账失败!');
                            return $res;
                        }else{
                            $sdf = array('charge_status'=>1,'charge_time'=>time());
                            $filter = array('bill_id'=>$id);
                            if(!$billObj->update($sdf,$filter)){
                                $res = array('status'=>'fail','msg'=>'更改记账状态失败，批量记账失败!');
                                return $res;
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }

    /*
    **获取费用类费用项的关联关系
    **@params $flag 标识，all或空为所有关系，sale是销售相关，unsale为非销售相关
    **@return array('fee_type_id'=>array('name'=>'费用类的名称','fee_item'=>array('fee_item_id'=>'费用项名称')));
    */

    public function get_fee_type_item_relation($flag = 'all'){
        $billObj = &app::get('finance')->model('bill');
        $res = $billObj->get_fee_type_item_relation($flag);
        return $res;
    }

    /*
    **通过实收账单id获取单号相同，或关联单号等于单号，或交易对方（member）相同的应收单据
    **@params $bill_id 实收账单单据ID
    **@params $flag all 表示三种单据  order_bn表示只有单据号相同 relate_order_bn表示关联单号等于单号 member 表示交易对方相同
    **@return array() 应收单据的相关信息
    */

    public function get_ar_by_bill_id($bill_id,$flag='all'){
        $arObj = &app::get('finance')->model('ar');
        $res = $arObj->get_ar_by_bill_id($bill_id,$flag);
        $data = array();
        foreach($res as $k=>$v){
            $data[$k] = $v;
            $data[$k]['trade_time'] = date('Y-m-d',$v['trade_time']);
        }
        return $data;
    }

    /*
    **通过实收账单id获取单号相同，或交易对方（member）相同的实收单据
    **@params $bill_id 实收账单单据ID
    **@params $flag all 表示三种单据  order_bn表示只有单据号相同  member 表示交易对方相同
    **@return array() 应收单据的相关信息
    */

    public function get_bill_by_bill_id($bill_id,$flag='all'){
        $billObj = &app::get('finance')->model('bill');
        $res = $billObj->get_bill_by_bill_id($bill_id,$flag);
        $data = array();
        foreach($res as $k=>$v){
            $data[$k] = $v;
            $data[$k]['trade_time'] = date('Y-m-d',$v['trade_time']);
        }
        return $data;
    }

    /*
    **单据核销
    **@params $bill_ids array('0'=>'','1'=>'');
    **@params $ar_ids array('0'=>'','1'=>'');
    **@params $trade_time 空取默认值 
    **@params $show 是否返回信息
    **@return array('status'=>'success/fail 字符串','msg'=>'','msg_code'=>1表示完全核销，2表示实收金额小于应收金额，3表示实收金额大于应收金额);
    */
    /**
     * do_verificate
     * @param mixed $bill_id ID
     * @param mixed $ar_id ID
     * @param mixed $trade_time trade_time
     * @param mixed $show show
     * @return mixed 返回值
     */
    public function do_verificate($bill_id,$ar_id,$trade_time='',$show=''){
        $res = array('status'=>'success','msg'=>'','msg_code'=>'');
        $billObj = &app::get('finance')->model('bill');
        $arObj = &app::get('finance')->model('ar');
        $bill_data = $billObj->getList('bill_id,bill_bn,money,confirm_money,unconfirm_money,trade_time,charge_status',array('bill_id'=>$bill_id));
        $ar_data = $arObj->getList('ar_id,ar_bn,money,confirm_money,unconfirm_money,trade_time,charge_status',array('ar_id'=>$ar_id));
        $data = array();$time_data = '0';
        $bill_minus_flag = 'false';#实收单据是否存在负核销金额标识
        $bill_plus_flag = 'false';#实收单据是否存在正核销金额标识
        $charge_status_flag = false;
        foreach ($bill_data as $value) {
            if($value['unconfirm_money'] < 0){
                $bill_minus_flag = 'true';
            }else{
                $bill_plus_flag = 'true';
            }
            $data['bill'] += $value['unconfirm_money']; 
            if($time_data == '0'){
                $time_data = $value['trade_time'];
            }else{
                $time_data = ($time_data < $value['trade_time']) ? $time_data : $value['trade_time'];
            }
            if($value['charge_status'] == 0){
                $charge_status_flag = true;
            }
        }
        $ar_minus_flag = 'false';#应收单据是否存在负核销金额标识
        $ar_plus_flag = 'false';#应收单据是否存在正核销金额标识
        foreach ($ar_data as $value) {
            if($value['unconfirm_money'] < 0){
                $ar_minus_flag = 'true';
            }else{
                $ar_plus_flag = 'true';
            }
            $data['ar'] += $value['unconfirm_money']; 
            if($time_data == '0'){
                $time_data = $value['trade_time'];
            }else{
                $time_data = ($time_data < $value['trade_time']) ? $time_data : $value['trade_time'];
            }
            if($value['charge_status'] == 0){
                $charge_status_flag = true;
            }
        }
        if(empty($trade_time)){
            $trade_time = $time_data;
        }else{
            $trade_time = kernel::single('ome_func')->date2time($trade_time);
        }
        if($trade_time < $time_data){
            return array('status'=>'fail','msg'=>'核销时间不能早于所选实收或者应收账单日的最早时间，请重新选择！');
        }
        if($charge_status_flag){
            return array('status'=>'fail','msg'=>'存在未记账的账单，请记账后操作');
        }
        if(($ar_plus_flag == 'true' && $bill_minus_flag == 'true') || ($ar_minus_flag == 'true' &&  $bill_plus_flag == 'true')){
            if($data['bill'] != $data['ar']){
                return array('status'=>'fail','msg'=>'应收单据或实收单据任何一方金额存在正负时，不能完全核销的不允许操作');
            }
        }

        #----------------------返回信息开始-----------------------------
        if($show){
            if($data['bill'] == $data['ar']){
                return array('status'=>'success','msg_code'=>'1');
            }else if(abs($data['bill']) < abs($data['ar'])){
                return array('status'=>'success','msg_code'=>'2');
            }else{
                return array('status'=>'success','msg_code'=>'3');
            }
        }
        #----------------------返回信息结束-----------------------------
        #----------------------处理逻辑开始-----------------------------

        $db = kernel::database();
        $db->beginTransaction();

        if($data['bill'] == $data['ar']){
            #实收金额=应收金额 完全核销
            foreach ($ar_data as $key=>$value) {
                $ar_filter = array('ar_id'=>$value['ar_id']);
                $update_ar = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_ar = $arObj->update($update_ar,$ar_filter);
                if(!$rs_ar){
                    $update_ar_flag = true;
                    break;
                }
            }
            foreach ($bill_data as $key=>$value) {
                $bill_filter = array('bill_id'=>$value['bill_id']);
                $update_bill = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2);
                $rs_bill = $billObj->update($update_bill,$bill_filter);
                if(!$rs_bill){
                    $update_bill_flag = true;
                    break;
                }
            }
            if($update_bill_flag == true || $update_ar_flag == true ){
                $db->rollBack();
                return array('status'=>'fail','msg'=>'更改应收实收数据失败');
            }else{
                $db->commit();
            }
        }else if(abs($data['bill']) > abs($data['ar'])){

            #实收金额 > 应收金额 应收完全核销 实收部分核销
            $update_bill_flag = $billObj->do_verificate($bill_data,$data['ar']);

            foreach ($ar_data as $key=>$value) {
                $ar_filter = array('ar_id'=>$value['ar_id']);
                $update_ar = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_ar = $arObj->update($update_ar,$ar_filter);
                if(!$rs_ar){
                    $update_ar_flag = true;
                    break;
                }
            }
            if($update_bill_flag == false || $update_ar_flag == true ){
                $db->rollBack();
                return array('status'=>'fail','msg'=>'更改应收实收数据失败');
            }else{
                $db->commit();
            }
        }else{
            #实收金额 < 应收金额 实收完全核销 应收部分核销
            $update_ar_flag = $arObj->do_verificate($ar_data,$data['bill']);
            foreach ($bill_data as $key=>$value) {
                $bill_filter = array('bill_id'=>$value['bill_id']);
                $update_bill = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_bill = $billObj->update($update_bill,$bill_filter);
                if(!$rs_bill){
                    $update_bill_flag = true;
                }
            }
            if($update_bill_flag == true || $update_ar_flag == false ){
                $db->rollBack();
                return array('status'=>'fail','msg'=>'更改应收实收数据失败');
            }else{
                $db->commit();
            }
        }
        $this->write_verification_log($bill_data,$ar_data,$trade_time,$ids='');
        return $res;

    }

    /*
    **组织核销日志数据,插入核销日志
    **@params $bill_data array('bill_id'=>'','bill_bn'=>'','money'=>'','unconfirm_money'=>'','confirm_money'=>'')
    **@params $ar_data array('ar_id'=>'','ar_bn'=>'','money'=>'','unconfirm_money'=>'','confirm_money'=>'')
    **@params $trade_time  账期
    **@params $auto_flag  是否自动核销
    **@params $ids  
    */

    public function write_verification_log($bill_data,$ar_data,$trade_time,$auto_flag = '',$ids=''){
        $log_data = array(
            'op_time'=>time(),
            'op_name'=>$auto_flag ? 'system' : kernel::single('desktop_user')->get_name(),
            'type'=>'1',#应收互冲核销(0)，应收实收核销(1)
            'content'=>$ids ? $ids : '',
        );
        foreach ($bill_data as $value) {
            $bill_money += $value['unconfirm_money']; 
        }
        foreach ($ar_data as $value) {
            $ar_money += $value['unconfirm_money']; 
        }
        if($bill_money == $ar_money){
            $log_data['money'] = $bill_money;
            #实收金额=应收金额 完全核销
            $i = 0;
            foreach ($bill_data as $key=>$value) {
                $log_data['items'][$i]['bill_id'] = $value['bill_id'];
                $log_data['items'][$i]['bill_bn'] = $value['bill_bn'];
                $log_data['items'][$i]['type'] = 0;#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
            foreach ($ar_data as $value) {
                $log_data['items'][$i]['bill_id'] = $value['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $value['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
        }else if(abs($bill_money) > abs($ar_money)){
            #实收金额 > 应收金额 实收完全核销 应收部分核销
            $log_data['money'] = $ar_money;
            $i = 0;
            #应收完全核销
            foreach ($ar_data as $value) {
                $log_data['items'][$i]['bill_id'] = $value['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $value['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
            #实收部分核销
            $stand_money = $ar_money;
            $tmp_data = array();
            foreach ($bill_data as $key=>$value) {
                $tmp_data[abs($value['unconfirm_money']).$value['bill_id']] = $value;
            }
            ksort($tmp_data);
            foreach($tmp_data as $v){
                $log_data['items'][$i]['bill_id'] = $v['bill_id'];
                $log_data['items'][$i]['bill_bn'] = $v['bill_bn'];
                $log_data['items'][$i]['type'] = '0';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['trade_time'] = $trade_time;
                if(abs($v['unconfirm_money']) > abs($stand_money)){
                    $log_data['items'][$i]['money'] = $stand_money;
                    $i++;
                    break;
                }else{
                    $log_data['items'][$i]['money'] = $v['unconfirm_money'];
                    $stand_money = ($stand_money - $v['unconfirm_money']);
                    $i++;
                }
            }
        }else{
            #实收金额 < 应收金额 实收完全核销 应收部分核销
            $log_data['money'] = $bill_money;
            $i = 0;
            #实收完全核销
            foreach ($bill_data as $value) {
                $log_data['items'][$i]['bill_id'] = $value['bill_id'];
                $log_data['items'][$i]['bill_bn'] = $value['bill_bn'];
                $log_data['items'][$i]['type'] = 0;#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
            #应收部分核销
            $stand_money = $bill_money;
            $tmp_data = array();
            foreach ($ar_data as $key=>$value) {
                $tmp_data[abs($value['unconfirm_money']).$value['bill_id']] = $value;
            }
            ksort($tmp_data);
            foreach($tmp_data as $v){
                $log_data['items'][$i]['bill_id'] = $v['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $v['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['trade_time'] = $trade_time;
                if(abs($v['unconfirm_money']) > abs($stand_money)){
                    $log_data['items'][$i]['money'] = $stand_money;
                    $i++;
                    break;
                }else{
                    $log_data['items'][$i]['money'] = $v['unconfirm_money'];
                    $stand_money = ($stand_money - $v['unconfirm_money']);
                    $i++;
                }
            }
        }
        $a = kernel::single('finance_verification')->do_save($log_data);
    }





    // 更加订单获取实收实退单
    /**
     * 获取ListByOrderBn
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */
    public function getListByOrderBn($order_bn)
    {
        $mdlBill = app::get('finance')->model('bill');
        $res = $mdlBill->getList('credential_number,fee_type,order_bn,member,fee_type_id,money,trade_time,unique_id,channel_id,fee_item,bill_id,monthly_id,monthly_status,status,verification_status,charge_status,bill_type',array('order_bn'=>$order_bn),0,-1,'trade_time');
        $data = array();
        $monthlyReport = finance_monthly_report::getAll('monthly_id');
        foreach($res as $k=>$v){
            $data[$k] = $v;
            $data[$k]['trade_time'] = date('Y-m-d',$v['trade_time']);
            $data[$k]['monthly_date'] = $monthlyReport[$v['monthly_id']]['monthly_date'];
            $data[$k]['_monthly_status'] = finance_monthly_report::getMonthlyStatus($v['monthly_status']);
            $data[$k]['_status'] = $this->get_name_by_status($v['status'],'');
            $data[$k]['_verification_status'] = $this->get_name_by_verification_status($v['verification_status']);
        }
        return $data;
    }

    /**
     * 获取_name_by_shop
     * @return mixed 返回结果
     */
    public function get_name_by_shop(){
        $shop_list = financebase_func::getShopList(financebase_func::getShopType());
        $res = array();
        foreach ($shop_list as $v) {
            $res[$v['shop_id']] = $v['name'];
        }
        return $res;
    }
}