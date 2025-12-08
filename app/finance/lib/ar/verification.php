<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ar_verification extends eccommon_analysis_abstract implements eccommon_analysis_interface{

    public $detail_options = array(
        'hidden' => true,
        );
    public $graph_options = array(
        'hidden' => true,
        );
    public $type_options = array(
        'display' => 'true',
        );

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

        for($i=0;$i<=5;$i++){
            if ($i == 1) continue;
            $val = $i+1;
            $this->_render->pagedata['time_shortcut'][$i] = $val;
        }

        $shopObj = &app::get('ome')->model('shop');
        $shopdata = $shopObj->getList('name,shop_id');

        $this->_render->pagedata['shopdata']= $shopdata;
        $this->_render->pagedata['shop_id']= $_POST['shop_id'] ? $_POST['shop_id'] : '0';
        $this->_extra_view = array('finance' => 'ar/verification_top.html');

    }

    /**
     * 设置_params
     * @param mixed $post post
     * @return mixed 返回操作结果
     */
    public function set_params($post){
        $post['verification_flag'] = 1;
        $post['status|noequal'] = 2;
        $post['charge_status'] = 1;
        return parent::set_params($post);
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        return array(
            'model' => 'finance_mdl_ar_verification',
            'params' => array(
                'title'=>'应收对冲',
                'actions'=>array(
                    array(
                        'label' => '应收对冲',
                        'href' => 'index.php?app=finance&ctl=ar_verification&act=verificate&finder_id='.$_GET['finder_id'],
                        'target' => "dialog::{width:900,height:400,title:'应收对冲'}",
                        ),
                    ),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'finder_aliasname'=>'ar_verification',
                'finder_cols'=>'column_edit,ar_bn,channel_name,trade_time,member,type,order_bn,money,confirm_money,unconfirm_money,charge_time',
                ),
            );
    }

    /*
    **通过订单号获取能够应收互冲的数据
    **@params $order_bn 订单号
    */

    public function get_ar_by_order_bn($order_bn){
        $crc32_order_bn = sprintf('%u',crc32($order_bn));
        $arObj = &app::get('finance')->model('ar');
        $cols = 'ar_id,ar_bn,member,order_bn,trade_time,serial_number,channel_name,type,money,unconfirm_money,confirm_money,charge_status';
        $tmp = $arObj->getList($cols,array('crc32_order_bn'=>$crc32_order_bn,'status|noequal'=>2));
        $data = array();
        foreach($tmp as $v){
            if($v['order_bn'] == $order_bn){
                $v['type'] = kernel::single('finance_ar')->get_name_by_type($v['type']);
                if($v['money'] > 0){
                    $data['plus'][] = $v;
                }else{
                    $data['minus'][] = $v;
                }
            }
        }
        return $data;
    }

    /*
    **单据核销
    **@params $plus array('0'=>'','1'=>'');
    **@params $minus array('0'=>'','1'=>'');
    **@params $trade_time 空取默认值
    **@params $show 是否返回信息
    **@return array('status'=>'success/fail 字符串','msg'=>'','msg_code'=>1表示完全对冲，2表示正金额小于负金额，3表示正金额大于负金额);
    */

    public function do_verificate($plus,$minus,$trade_time='',$show=''){
        $res = array('status'=>'success','msg'=>'','msg_code'=>'');
        $arObj = &app::get('finance')->model('ar');
        $arveObj = &app::get('finance')->model('ar_verification');
        $plus_data = $arObj->getList('ar_id,ar_bn,money,confirm_money,unconfirm_money,trade_time,charge_status',array('ar_id'=>$plus));
        $minus_data = $arObj->getList('ar_id,ar_bn,money,confirm_money,unconfirm_money,trade_time,charge_status',array('ar_id'=>$minus));
        $data = array();$time_data = 0;
        $charge_status_flag = false;
        foreach ($plus_data as $value) {
            $data['plus'] += $value['unconfirm_money'];
            if($value['charge_status'] == 0){
                $charge_status_flag = true;
                break;
            }
            if($time_data == 0){
                $time_data = $value['trade_time'];
            }else{
                $time_data = ($time_data < $value['trade_time']) ? $time_data : $value['trade_time'];
            }
        }
        foreach ($minus_data as $value) {
            $data['minus'] += $value['unconfirm_money'];
            if($value['charge_status'] == 0){
                $charge_status_flag = true;
                break;
            }
            if($time_data == 0){
                $time_data = $value['trade_time'];
            }else{
                $time_data = ($time_data < $value['trade_time']) ? $time_data : $value['trade_time'];
            }
        }
        if(empty($trade_time)){
            $trade_time = $time_data;
        }else{
            $trade_time = strtotime($trade_time);
        }
        if($trade_time < $time_data){
            return array('status'=>'fail','msg'=>'对冲时间不能早于所选实收或者应收账单日的最早时间，请重新选择！');
        }
        if($charge_status_flag){
            return array('status'=>'fail','msg'=>'存在未记账的账单，请记账后操作');
        }
        #----------------------返回信息开始-----------------------------
        if($show){
            if($data['plus'] == abs($data['minus'])){
                return array('status'=>'success','msg_code'=>1);
            }else if($data['plus'] < abs($data['minus'])){
                return array('status'=>'success','msg_code'=>2);
            }else{
                return array('status'=>'success','msg_code'=>3);
            }
        }
        #----------------------返回信息结束-----------------------------
        #----------------------处理逻辑开始-----------------------------

        $db = kernel::database();
        $db->beginTransaction();
        if(abs($data['minus']) == $data['plus']){
        #正=负 完全对冲
            foreach ($plus_data as $key=>$value) {
                $plus_filter = array('ar_id'=>$value['ar_id']);
                $update_plus = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_plus = $arObj->update($update_plus,$plus_filter);
                if(!$rs_plus){
                    $update_plus_flag = true;
                    break;
                }
            }
            foreach ($minus_data as $key=>$value) {
                $minus_filter = array('ar_id'=>$value['ar_id']);
                $update_minus = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_minus = $arObj->update($update_minus,$minus_filter);
                if(!$rs_minus){
                    $update_minus_flag = true;
                    break;
                }
            }
            if($update_minus_flag == true || $update_plus_flag == true ){
                $db->rollBack();
                return array('status'=>'fail','msg'=>'更改应收数据失败');
            }else{
                $db->commit();
            }
        }else if($data['plus'] > abs($data['minus'])){

            #正 > 负 负完全核销 正部分核销
            $update_plus_flag = $arveObj->do_plus_verificate($plus_data,$data['minus']);

            foreach ($minus_data as $key=>$value) {
                $minus_filter = array('ar_id'=>$value['ar_id']);
                $update_minus = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_minus = $arObj->update($update_minus,$minus_filter);
                if(!$rs_minus){
                    $update_minus_flag = true;
                    break;
                }
            }
            if($update_plus_flag == false || $update_minus_flag == true ){
                $db->rollBack();
                return array('status'=>'fail','msg'=>'更改应收数据失败');
            }else{
                $db->commit();
            }
        }else{
            #正 < 负 正完全核销 负部分核销
            $update_minus_flag = $arveObj->do_minus_verificate($minus_data,$data['plus']);
            foreach ($plus_data as $key=>$value) {
                $plus_filter = array('ar_id'=>$value['ar_id']);
                $update_plus = array('confirm_money'=>$value['money'],'unconfirm_money'=>0,'status'=>2,'verification_time'=>time());
                $rs_plus = $arObj->update($update_plus,$plus_filter);
                if(!$rs_plus){
                    $update_plus_flag = true;
                }
            }
            if($update_plus_flag == true || $update_minus_flag == false ){
                $db->rollBack();
                return array('status'=>'fail','msg'=>'更改应收数据失败');
            }else{
                $db->commit();
            }
        }
        $ids = array_merge($plus,$minus);
        $this->change_verification_flag($ids);
        $this->write_verification_log($plus_data,$minus_data,$trade_time,$ids='');
        return $res;

    }

    /*
    **组织核销日志数据,插入核销日志
    **@params $plus_data array('ar_id'=>'','ar_bn'=>'','money'=>'+','unconfirm_money'=>'','confirm_money'=>'')
    **@params $minus_data array('ar_id'=>'','ar_bn'=>'','money'=>'-','unconfirm_money'=>'','confirm_money'=>'')
    **@params $trade_time  账期
    **@params $ids
    */

    public function write_verification_log($plus_data,$minus_data,$trade_time,$ids=''){
        $log_data = array(
            'op_time'=>time(),
            'op_name'=>kernel::single('desktop_user')->get_name(),
            'type'=>'0',#应收互冲核销(0)，应收实收核销(1)
            'content'=>$ids ? $ids : '',
        );
        foreach ($plus_data as $value) {
            $plus_money += $value['unconfirm_money'];
        }
        foreach ($minus_data as $value) {
            $minus_money += $value['unconfirm_money'];
        }
        if($plus_money == abs($minus_money)){
            $log_data['money'] = $plus_money;
            #实收金额=应收金额 完全核销
            $i = 0;
            foreach ($plus_data as $key=>$value) {
                $log_data['items'][$i]['bill_id'] = $value['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $value['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
            foreach ($minus_data as $value) {
                $log_data['items'][$i]['bill_id'] = $value['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $value['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
        }else if($plus_money > abs($minus_money)){
            #正 > 负 负完全核销 正部分核销
            $log_data['money'] = abs($minus_money);
            $i = 0;
            #负完全核销
            foreach ($minus_data as $value) {
                $log_data['items'][$i]['bill_id'] = $value['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $value['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
            #正部分核销
            $stand_money = abs($minus_money);
            $tmp_data = array();
            foreach ($plus_data as $key=>$value) {
                $tmp_data[abs($value['unconfirm_money']).$value['ar_id']] = $value;
            }
            ksort($tmp_data);
            foreach($tmp_data as $v){
                $log_data['items'][$i]['bill_id'] = $v['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $v['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['trade_time'] = $trade_time;
                if($v['unconfirm_money'] > $stand_money){
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
            #正 < 负 正完全核销 负部分核销
            $log_data['money'] = $plus_money;
            $i = 0;
            #正完全核销
            foreach ($plus_data as $value) {
                $log_data['items'][$i]['bill_id'] = $value['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $value['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['money'] = $value['unconfirm_money'];
                $log_data['items'][$i]['trade_time'] = $trade_time;
                $i++;
            }
            #负收部分核销
            $stand_money = $plus_money;
            $tmp_data = array();
            foreach ($minus_data as $key=>$value) {
                $tmp_data[abs($value['unconfirm_money']).$value['ar_id']] = $value;
            }
            ksort($tmp_data);
            foreach($tmp_data as $v){
                $log_data['items'][$i]['bill_id'] = $v['ar_id'];
                $log_data['items'][$i]['bill_bn'] = $v['ar_bn'];
                $log_data['items'][$i]['type'] = '1';#实收单据（0） 应收单据（1）
                $log_data['items'][$i]['trade_time'] = $trade_time;
                if(abs($v['unconfirm_money']) > $stand_money){
                    $log_data['items'][$i]['money'] = -$stand_money;
                    $i++;
                    break;
                }else{
                    $log_data['items'][$i]['money'] = $v['unconfirm_money'];
                    $stand_money = ($stand_money) - abs($v['unconfirm_money']);
                    $i++;
                }
            }
        }
        $a = kernel::single('finance_verification')->do_save($log_data);
    }


    /*
    **更改应收互冲显示标识
    **@params $ids array('0'=>'','1'=>''); 应收单据Id
    */
    /**
     * change_verification_flag
     * @param mixed $ids ID
     * @return mixed 返回值
     */
    public function change_verification_flag($ids){
        $arObj = &app::get('finance')->model('ar');
        $order_bn = $arObj->getList('distinct(order_bn)',array('ar_id'=>$ids));
        foreach ($order_bn as $key => $value) {
            $rs = $arObj->getList('unconfirm_money,ar_id',array('order_bn'=>$value['order_bn'],'status|noequal'=>2));
            foreach ($rs as $k => $v) {
                if($v['unconfirm_money'] > 0){
                    $plus_flag = 'true';
                }else if($v['unconfirm_money'] < 0){
                    $minus_flag = 'true';
                }
            }
            if($plus_flag == 'true' && $minus_flag == 'true' ){
                continue;
            }else{
                $update_filter = array('order_bn'=>$value['order_bn'],'status|noequal'=>2);
                $update_cols = array('verification_flag'=>0);
                $arObj->update($update_cols,$update_filter);
            }
        }
        return true;
    }


    /*
    **获取下一单数据
    **@params $order_bn 订单号
    **@params $time_from 开始时间
    **@params $time_to 结束时间
    **
    */

    public function get_next_order_bn($order_bn,$time_from,$time_to){
        $arverObj = &app::get('finance')->model('ar_verification');
        $next_order_bn =  $arverObj->getList('distinct(order_bn)',array('verification_flag'=>1,'status|noequal'=>2,'charge_status'=>1,'trade_time|bthan'=>$time_from,'trade_time|sthan'=>$time_to));
        foreach($next_order_bn as $k=>$v){
            if($v['order_bn'] == $order_bn){
                return $next_order_bn[$k+1];
            }
        }
    }
}