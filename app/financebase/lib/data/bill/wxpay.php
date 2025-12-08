<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_data_bill_wxpay extends financebase_abstract_bill
{
    
    // 处理数据
    /**
     * 获取Sdf
     * @param mixed $row row
     * @param mixed $offset offset
     * @param mixed $title title
     * @return mixed 返回结果
     */
    public function getSdf($row,$offset=1, $title)
    {
        $row = array_map('trim', $row);
        
        if (!$this->ioTitle) {
            $this->ioTitle = $this->getTitle();
        }
        $res = array('status'=>true,'data'=>array(),'msg'=>'');
        if($row[0] == '流水单号' || $row[0] == "\u{FEFF}流水单号") {
            return $res;
        }
        $tmpRow = [];
        foreach ($title as $k => $t) {
            $i = array_search($t, $this->getTitle());
            
            if ($i) {
                $tmpRow[$i] = $row[$k];
            }
        }
        
        $tmp = [];
        //判断参数不能为空
        foreach ($tmpRow as $k => $v) {
            $tmp[$k] = trim($v);
            
            if(in_array($k, array('out_trade_no','trade_no','trade_type','trade_status')))
            {
                if(!$v)
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 不能为空！", $tmpRow['trade_no'], $this->ioTitle[$k]);
                    return $res;
                }
            }
            
            if(in_array($k, array('trade_time')))
            {
                $result = finance_io_bill_verify::isDate($v);
                if ($result['status'] == 'fail')
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 时间(%s)格式错误！", $tmpRow['trade_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
            
            if(in_array($k, array('money')))
            {
                $result = finance_io_bill_verify::isPrice($v);
                if ($result['status'] == 'fail')
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 金额(%s)格式错误！", $tmpRow['trade_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
    
            if($k == 'trade_status'){
                if(!in_array($v,['收入','支出'])){
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 收支类型(%s)错误！", $tmpRow['trade_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
        }
        
        $tmp['money'] = $tmp['trade_status'] == '支出' ? -$tmp['money'] : $tmp['money'];
        $tmp['amount'] = $tmp['money'];
        $res['data'] = $tmp;
        
        
        return $res;
    }
    
    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle()
    {
        $title = array(
            'trade_no'      => '流水单号',
            'trade_time'    => '记账时间',
            'trade_type'    => '动帐类型',
            'trade_status'  => '收支类型',
            'money'         => '收支金额',
            'total_amount'  => '账户余额',
            'out_trade_no'  => '关联订单号',
            'out_refund_no' => '关联售后单号',
            'out_tixian_no' => '关联提现单号',
            'out_baodan_no' => '关联保单号',
            'remarks'       => '详情',
        );
        return $title;
    }
    
    // 获取订单号
    /**
     * _getOrderBn
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _getOrderBn(&$params)
    {
        // $this->shopNew = [];

        $paymentModel = app::get('ome')->model('payments');
        $orderModel   = app::get('ome')->model('orders');
        //微信小店、有赞导入文件，账单关联订单号对应oms支付单号
        $paymentsInfo = $paymentModel->db_dump(['payment_bn' => $params['out_trade_no']], 'payment_id,payment_bn,order_id');
        if ($paymentsInfo) {
            $orderInfo = $orderModel->db_dump(['order_id' => $paymentsInfo['order_id']], 'order_id,order_bn,shop_id');
            if ($orderInfo && isset($orderInfo['order_bn'])) {
                $params['out_trade_no'] = $orderInfo['order_bn'];

                // 重新定位店铺
                $shop = app::get('ome')->model('shop')->db_dump(['shop_id' => $orderInfo['shop_id']], 'shop_id,name,shop_bn');

                $params['shop_id']      = $shop['shop_id'];
                $params['shop_name']    = $shop['name'];
            }
        }
        return $params['out_trade_no'];
    }
    
    /**
     * 获取具体类别
     * @param $params
     * @return mixed|string
     * @date 2024-11-26 4:23 下午
     */
    public function getBillCategory($params)
    {
        if(!$this->rules) $this->getRules('wx');
        
        $this->verified_data = $params;
        
        if($this->rules)
        {
            foreach ($this->rules as $item)
            {
                foreach ($item['rule_content'] as $rule) {
                    if($this->checkRule($rule)) return $item['bill_category'];
                }
            }
        }
        
        return '';
    }
    
    /**
     * 检查文件是否有效
     * @Date   2019-06-25
     * @param  String     $file_name 文件名
     * @param  String     $file_type 文件类型
     * @return Boolean
     */
    public function checkFile($file_name,$file_type){
        $ioType = kernel::single('financebase_io_' . $file_type);
        $row    = $ioType->getData($file_name, 0, 5);
        
        $title = array_values($this->getTitle());
        $row[0][0] = str_replace("\u{FEFF}", "", $row[0][0]);
        $plateTitle = $row[0];
        foreach($title as $v) {
            if(array_search($v, $plateTitle) === false) {
                return array(false, '文件模板错误：列【'.$v.'】未包含在'.implode('、', $plateTitle) );
            }
        }
        return array(true, '文件模板匹配', $row[0]);
    }
    
    
    /**
     * 同步到对账表
     * @Date   2019-06-25
     * @param  Array   原始数据    $data
     * @param  String  具体类别    $bill_category
     * @return Boolean
     */
    public function syncToBill($data,$bill_category='')
    {
        
        $data['content'] = json_decode(stripslashes($data['content']),1);
        if(!$data['content']) return false;
        
        $tmp = $data['content'];
        $shop_id = $data['shop_id'];
        
        $mdlBill = app::get('finance')->model('bill');
        
        $tmp['fee_obj'] = '微信小店';
        $tmp['fee_item'] = $bill_category;
        
        $res = $this->getBillType($tmp,$shop_id);
        if(!$res['status']) return false;

        $order_bn = $this->_getOrderBn($tmp);

        // 手工选的店铺
        if(!$data['shop_name']){
            $data['shop_name'] = isset($this->shop_list[$data['shop_id']]) ? $this->shop_list[$data['shop_id']]['name'] : '';
        }
        $shop_name = $data['shop_name'];

        // 通过支付单获取店铺信息
        $shop_id    = $tmp['shop_id']  ?: $shop_id;
        $shop_name  = $tmp['shop_name']?: $shop_name;
        

        $base_sdf = array(
            'order_bn'          => $order_bn,
            'channel_id'        => $shop_id,
            'channel_name'      => $shop_name,
            'trade_time'        => strtotime($tmp['trade_time']),
            'fee_obj'           => $tmp['fee_obj'],
            'money'             => $tmp['money'],
            'fee_item'          => $tmp['fee_item'],
            'fee_item_id'       => isset($this->fee_item_rules[$tmp['fee_item']]) ? $this->fee_item_rules[$tmp['fee_item']] : 0,
            'credential_number' => $tmp['trade_no'],// 单据编号
            'member'            => '',
            'memo'              => $tmp['remarks'],
            'unique_id'         => $data['unique_id'],
            'create_time'       => time(),
            'fee_type'          => $tmp['trade_type'],
            'fee_type_id'       => $res['fee_type_id'],
            'bill_type'         => $res['bill_type'],
            'charge_status'     => 1,// 流水直接设置记账成功
            'charge_time'       => time(),
        
        );
        $base_sdf['monthly_id'] = 0;
        $base_sdf['monthly_item_id']     = 0;
        $base_sdf['monthly_status'] = 0;
        
        
        $base_sdf['crc32_order_bn'] = sprintf('%u',crc32($base_sdf['order_bn']));
        $base_sdf['bill_bn'] = $mdlBill->gen_bill_bn();
        $base_sdf['unconfirm_money'] = $base_sdf['money'];
        
        if($mdlBill->insert($base_sdf)){
            kernel::single('finance_monthly_report_items')->dealBillMatchReport($base_sdf['bill_id']);
            return true;
        }
        return false;
    }
    
    /**
     * _filterData
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function _filterData(&$data)
    {
        $new_data = array();
        
        $new_data['order_bn']       = $this->_getOrderBn($data);
        $new_data['trade_no']       = $data['trade_no'];
        $new_data['financial_no']   = $data['trade_no'];
        $new_data['out_trade_no']   = $data['out_trade_no'];
        $new_data['trade_time']     = strtotime($data['trade_time']);
        $new_data['trade_type']     = $data['trade_type'];
        $new_data['money']          = $data['money'];
        $new_data['unique_id']      = md5($data['trade_no']);
        $new_data['platform_type']  = 'wxpay';
        $new_data['remarks']        = $data['remarks'];
        
        return $new_data;
    }
    
    /**
     * 获取ImportDateColunm
     * @param mixed $title title
     * @return mixed 返回结果
     */
    public function getImportDateColunm($title=null)
    {
        $timeColumn = ['记账时间'];
        $timeCol = [];
        foreach ($timeColumn as $v) {
            $k = array_search($v, $title);
            if($k !== false) {
                $timeCol[] = $k+1;
            }
        }
        $timezone = defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 0;
        return array('column'=>$timeCol,'time_diff'=>$timezone * 3600 );
    }
    
}