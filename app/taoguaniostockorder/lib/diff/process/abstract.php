<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class taoguaniostockorder_diff_process_abstract
{
    /**
     * 仓加库存
     * @param $data
     * @return array
     */
    protected function branchAddStock($data)
    {
        //生成入库单 type_id = 70 bill_type = adjustment
        $diffMdl = app::get('taoguaniostockorder')->model('diff');
        $isoDiffItemsMdl = app::get('taoguaniostockorder')->model('diff_items');
        $isoDiffItemsDetailMdl = app::get('taoguaniostockorder')->model('diff_items_detail');
        $diff_id = $data['diff_id'];
        $detailItems = $data['items'];
        $data['type_id'] = '921';//直接入库
        $filter = array(
            'diff_id' => $diff_id,
        );
        // 检测单据是否有效
        $info = $diffMdl->db_dump($filter);
        if (!$info) {
            return [false,'单据无效'];
        }
    
        if (in_array($info['diff_status'], array('2','3','4'))) {
            return [false,'未处理的单据才能审核'];
        }
    
        if ($data['check_status'] == '2') {
            return [false,'单据已经审核，不能再审核'];
        }
    
        $shift_data = $this->formatData($data);
        $iostock_instance = kernel::single('console_adjust');
        list($res,$msg) = $iostock_instance->createAdjustDiff($shift_data);
        if(!$res){
            //入库单处理失败返回
            return [false,$msg];
        }
        //修改差异单状态
        $diff_items_ids = array_column($detailItems,'diff_items_id');
        $items_detail_ids = array_column($detailItems,'items_detail_id');
        $isoDiffItemsDetailMdl->update(array('diff_status'=>'3'),array('items_detail_id'=>$items_detail_ids));
        $isoDiffItemsMdl->update(array('diff_status'=>'3'),array('diff_items_id'=>$diff_items_ids));
        
        //邮件发送
        $this->sendMail($shift_data);
        
        return [true,'创建入库单成功'];
    }
    
    /**
     * 仓减库存
     * @param $data
     * @return array
     */
    protected function branchSubStock($data)
    {
        //生成出库单 type_id = 7 bill_type = adjustment
        $diffMdl = app::get('taoguaniostockorder')->model('diff');
        $isoDiffItemsMdl = app::get('taoguaniostockorder')->model('diff_items');
        $isoDiffItemsDetailMdl = app::get('taoguaniostockorder')->model('diff_items_detail');
    
        $diff_id = $data['diff_id'];
        $detailItems = $data['items'];
        $data['type_id'] = '922';//直接出库
    
        $filter = array(
            'diff_id' => $diff_id,
        );
        // 检测单据是否有效
        $info = $diffMdl->db_dump($filter);
        if (!$info) {
            return [false,'单据无效'];
        }
    
        if (in_array($info['diff_status'], array('2','3','4'))) {
            return [false,'未处理的单据才能审核'];
        }
    
        if ($data['check_status'] == '2') {
            return [false,'单据已经审核，不能再审核'];
        }
    
        $shift_data = $this->formatData($data);
    
        $iostock_instance = kernel::single('console_adjust');
    
        list($res,$msg) = $iostock_instance->createAdjustDiff($shift_data);
        if(!$res){
            //出库单处理失败返回
            return [false,$msg];
        }
        //修改差异单状态
        $diff_items_ids = array_column($detailItems,'diff_items_id');
        $items_detail_ids = array_column($detailItems,'items_detail_id');
        $isoDiffItemsDetailMdl->update(array('diff_status'=>'3'),array('items_detail_id'=>$items_detail_ids));
        $isoDiffItemsMdl->update(array('diff_status'=>'3'),array('diff_items_id'=>$diff_items_ids));
    
        //邮件发送
        $this->sendMail($shift_data);
        
        return [true,'创建入库单成功'];
    }
    
    /**
     * 店加库存
     * @param $data
     * @return array
     */
    protected function storeAddStock($data)
    {
        //生成入库单 type_id = 70 bill_type = adjustment
        $diffMdl = app::get('taoguaniostockorder')->model('diff');
        $isoDiffItemsMdl = app::get('taoguaniostockorder')->model('diff_items');
        $isoDiffItemsDetailMdl = app::get('taoguaniostockorder')->model('diff_items_detail');
        
        $diff_id = $data['diff_id'];
        $detailItems = $data['items'];
        $data['type_id'] = '921';//直接入库
        
        $filter = array(
            'diff_id' => $diff_id,
        );
        // 检测单据是否有效
        $info = $diffMdl->db_dump($filter);
        if (!$info) {
            return [false,'单据无效'];
        }

        if (in_array($info['diff_status'], array('2','3','4'))) {
            return [false,'未处理的单据才能审核'];
        }

        if ($data['check_status'] == '2') {
            return [false,'单据已经审核，不能再审核'];
        }
    
        //return
        $shift_data = $this->formatData($data);
    
        $iostock_instance = kernel::single('console_adjust');
        
        list($res,$msg) = $iostock_instance->createAdjustDiff($shift_data);
        if(!$res){
            //入库单处理失败返回
            return [false,$msg];
        }
        //修改差异单状态
        $diff_items_ids = array_column($detailItems,'diff_items_id');
        $items_detail_ids = array_column($detailItems,'items_detail_id');
        $isoDiffItemsDetailMdl->update(array('diff_status'=>'3'),array('items_detail_id'=>$items_detail_ids));
        $isoDiffItemsMdl->update(array('diff_status'=>'3'),array('diff_items_id'=>$diff_items_ids));
        
        //邮件发送
        $this->sendMail($shift_data);
    
        return [true,'创建入库单成功'];
    }
    
    /**
     * 店减库存
     * @param $data
     * @return array
     */
    protected function storeSubStock($data)
    {
        //生成出库单 type_id = 7 bill_type = adjustment
        $diffMdl = app::get('taoguaniostockorder')->model('diff');
        $isoDiffItemsMdl = app::get('taoguaniostockorder')->model('diff_items');
        $isoDiffItemsDetailMdl = app::get('taoguaniostockorder')->model('diff_items_detail');
    
        $diff_id = $data['diff_id'];
        $detailItems = $data['items'];
        $data['type_id'] = '922';//直接出库
    
        $filter = array(
            'diff_id' => $diff_id,
        );
        // 检测单据是否有效
        $info = $diffMdl->db_dump($filter);
        if (!$info) {
            return [false,'单据无效'];
        }
    
        if (in_array($info['diff_status'], array('2','3','4'))) {
            return [false,'未处理的单据才能审核'];
        }
    
        if ($data['check_status'] == '2') {
            return [false,'单据已经审核，不能再审核'];
        }
    
        $shift_data = $this->formatData($data);
    
        $iostock_instance = kernel::single('console_adjust');
    
        list($res,$msg) = $iostock_instance->createAdjustDiff($shift_data);
        if(!$res){
            //出库单处理失败返回
            return [false,$msg];
        }
        //修改差异单状态
        $diff_items_ids = array_column($detailItems,'diff_items_id');
        $items_detail_ids = array_column($detailItems,'items_detail_id');
        $isoDiffItemsDetailMdl->update(array('diff_status'=>'3'),array('items_detail_id'=>$items_detail_ids));
        $isoDiffItemsMdl->update(array('diff_status'=>'3'),array('diff_items_id'=>$diff_items_ids));
    
        //邮件发送
        $this->sendMail($shift_data);
        
        return [true,'创建入库单成功'];
    }

    /**
     * 物流店铺加库存
     * @param $data
     * @return array
     */
    protected function logisticsAddStock($data)
    {
        //生成入库单 type_id = 70 bill_type = adjustment
        $diffMdl = app::get('taoguaniostockorder')->model('diff');
        $isoDiffItemsMdl = app::get('taoguaniostockorder')->model('diff_items');
        $isoDiffItemsDetailMdl = app::get('taoguaniostockorder')->model('diff_items_detail');
        $diff_id = $data['diff_id'];
        $detailItems = $data['items'];
        $data['type_id'] = '921';//直接入库
        $filter = array(
            'diff_id' => $diff_id,
        );
        // 检测单据是否有效
        $info = $diffMdl->db_dump($filter);
        if (!$info) {
            return [false,'单据无效'];
        }
    
        if (in_array($info['diff_status'], array('2','3','4'))) {
            return [false,'未处理的单据才能审核'];
        }
    
        if ($data['check_status'] == '2') {
            return [false,'单据已经审核，不能再审核'];
        }
    
        $shift_data = $this->formatData($data);
        $iostock_instance = kernel::single('console_adjust');
        list($res,$msg) = $iostock_instance->createAdjustDiff($shift_data);
        if(!$res){
            //入库单处理失败返回
            return [false,$msg];
        }
        //修改差异单状态
        $diff_items_ids = array_column($detailItems,'diff_items_id');
        $items_detail_ids = array_column($detailItems,'items_detail_id');
        $isoDiffItemsDetailMdl->update(array('diff_status'=>'3'),array('items_detail_id'=>$items_detail_ids));
        $isoDiffItemsMdl->update(array('diff_status'=>'3'),array('diff_items_id'=>$diff_items_ids));
        
        //邮件发送
        // $this->sendMail($shift_data);
        
        return [true,'创建入库单成功'];
    }

    protected function sendMail($data)
    {
        $this->send_email($data);
    
    }

    /**
     * 仓发店短发
     * 
     * @return void
     * @author 
     * */
    abstract public function b2sLess($data);


    /**
     * 仓发店超发
     * 
     * @return void
     * @author 
     * */
    abstract public function b2sMore($data);

    /**
     * 仓发店错发（错发逻辑拆分成超发和短发处理）
     * 
     * @return void
     * @author 
     * */
//    abstract public function b2sWrong($data);

    /**
     * 店退仓短发
     * 
     * @return void
     * @author 
     * */
    abstract public function s2bLess($data);


    /**
     * 店退仓超发
     * 
     * @return void
     * @author 
     * */
    abstract public function s2bMore($data);

    /**
     * 店退仓错发（）
     * 
     * @return void
     * @author 
     * */
//    abstract public function s2bWrong($data);

    /**
     * 店转店短发
     * 
     * @return void
     * @author 
     * */
    abstract public function s2sLess($data);


    /**
     * 店转店超发
     * 
     * @return void
     * @author 
     * */
    abstract public function s2sMore($data);
    
    /**
     * 仓转仓短发
     * 
     * @return void
     * @author
     * */
    abstract public function b2bLess($data);
    
    /**
     * 仓转仓超发
     * 
     * @return void
     * @author
     * */
    abstract public function b2bMore($data);
    /**
     * 店转店错发
     * 
     * @return void
     * @author 
     * */
//    abstract public function s2sWrong($data);
    
    /**
     * 直接出入库单数据组装
     * @param $sdf
     * @return array
     */
    protected function formatData($sdf)
    {
        #组织明细
        $items = array();
        foreach ($sdf['items'] as $key => $value) {
            if($value['nums'] > 0){
                $items[$value['product_id']] = array(
                    'bn'    =>  (string)$value['bn'],
                    'nums'  =>  $value['nums'],
                    'unit'  =>  $value['unit'],
                    'name'  =>  $value['product_name'],
                    'price' =>  $value['price'],
                );
            }
        }
        
        $op_name          = kernel::single('desktop_user')->get_name();
        $iostockorder_name = $sdf['type_id'] == '921' ? '差异单入库' : '差异单出库';
        $shift_data       = array(
            'iostockorder_name' => $iostockorder_name.$sdf['diff_bn'],
            'branch'            => $sdf['branch_id'],
//            'extrabranch_id'    => $from_branch_id,
            'type_id'           => $sdf['type_id'],
            'iso_price'         => 0,
            'operator'          => $op_name,
            'products'          => $items,
            'original_bn'       => $sdf['diff_bn'],
            'original_id'       => $sdf['diff_id'],
            'confirm'           => 'Y',
            'bill_type'         => 'adjustment',
        );
        return $shift_data;
    }
    
    
    function send_email($data)
    {
        if (!defined('EMAIL_ACCOUNT') || !defined('EMAIL_PWD')) {
            return false;
        }
        ini_set('memory_limit', '1G');
        $end_starttime = strtotime(date('Y-m-d H:00:00', time()));
    
//        $title = '预售预提单' . date('YmdH', $stat_starttime) . '-' . date('YmdH', $end_starttime);
    
        $attachment = '';
    
        $emailLib = new console_email_email;
        $emailLib->CharSet = "UTF-8";
        $emailLib->IsSMTP();
        $emailLib->SMTPAuth = true;
    
        $emailLib->SMTPSecure = 'ssl';
    
        $emailLib->isHTML(true);
    
        $emailLib->Host = 'smtp.exmail.qq.com';
        $emailLib->Port = 465;
        $emailLib->Username = EMAIL_ACCOUNT;
        $emailLib->From = EMAIL_ACCOUNT;
        $emailLib->FromName = 'GANT-业务中台';
        $emailLib->Password = EMAIL_PWD;
    
        //接收邮件人邮箱配置
        $receivers = array(
//            'dengbin@shopex.cn',
        );
//        $receivers = app::get('console')->getConf('take_stock_daily_email_list');
        foreach ($receivers as $value) {
            list($receive_mail, $receive_name) = explode('#', $value);
        
            $emailLib->AddAddress($receive_mail, $receive_name);
        }
    
        if ($attachment) {
            $emailLib->AddAttachment($attachment);
        }
    
        $subject = $data['iostockorder_name'].'--' . date('YmdH', $end_starttime);
        $emailLib->Subject = $subject;
        $emailLib->Body = $subject;
    
        try {
            $emailLib->Send();
        
            unlink($attachment);
        
            app::get('presale')->setConf('presale.orders.exporttime', $end_starttime);
            return true;
        } catch (Exception $e) {
            $err_msg = $emailLib->ErrorInfo;
        
            unlink($attachment);
            return false;
        }
    }
}