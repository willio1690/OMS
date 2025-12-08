<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 拼多多账单导入处理
 * @version 0.1
 */
class financebase_data_bill_pinduoduo extends financebase_abstract_bill
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
        if($row[0] == '商户订单号' || $row[0] == "\u{FEFF}商户订单号") {
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
            
            if(in_array($k, array('order_no','trade_type')))
            {
                if(!$v)
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 不能为空！", $tmpRow['order_no'], $this->ioTitle[$k]);
                    return $res;
                }
            }
            
            if(in_array($k, array('trade_time')))
            {
                $result = finance_io_bill_verify::isDate($v);
                if ($result['status'] == 'fail')
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 时间(%s)格式错误！", $tmpRow['order_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
            
            if(in_array($k, array('income_amount', 'outcome_amount')))
            {
                $result = finance_io_bill_verify::isPrice($v);
                if ($result['status'] == 'fail')
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 金额(%s)格式错误！", $tmpRow['order_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
    
//            if($k == 'outcome_amount') {
//                $tmp['outcome_amount'] = -$tmp['outcome_amount'];
//            }
        }
        
        
        $res['data'] = $tmp;
        
        
        return $res;
    }
    
    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle()
    {
        //商户订单号	发生时间	收入金额（+元）	支出金额（-元）	账务类型	备注	 业务描述
        $title = array(
            'order_no'            => '商户订单号',
            'trade_time'          => '发生时间',
            'income_amount'       => '收入金额（+元）',
            'outcome_amount'      => '支出金额（-元）',
            'trade_type'          => '账务类型',
            'remarks'             => '备注',
            'trade_describe'      => '业务描述',

        );
        
        return $title;
    }
    
    // 获取订单号
    /**
     * _getOrderBn
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _getOrderBn($params)
    {
        return $params['order_no'];
    }
    
    
    
    /**
     * 获取具体类别
     * @Date   2019-06-03
     * @param  [Array]     $params    参数
     * @return [String]                 具体类别
     */
    public function getBillCategory($params)
    {
        if(!$this->rules) $this->getRules('pinduoduo');
        
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
     * @param  String     $file_name 文件名
     * @param  String     $file_type 文件类型
     * @return array|bool
     * @date 2024-09-19 10:25 上午
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
     * @param array $data 原始数据
     * @param string $bill_category 具体类别
     * @return bool
     * @date 2024-09-19 10:26 上午
     */
    public function syncToBill($data,$bill_category='')
    {
        
        $data['content'] = json_decode(stripslashes($data['content']),1);
        if(!$data['content']) return false;
        
        $tmp = $data['content'];
        $shop_id = $data['shop_id'];
        
        $mdlBill = app::get('finance')->model('bill');
        $oMonthlyReport = kernel::single('finance_monthly_report');
        
        $tmp['fee_obj'] = '拼多多';
        $tmp['fee_item'] = $bill_category;
        
        $res = $this->getBillType($tmp,$shop_id);
        if(!$res['status']) return false;
        
        if(!$data['shop_name']){
            $data['shop_name'] = isset($this->shop_list[$data['shop_id']]) ? $this->shop_list[$data['shop_id']]['name'] : '';
        }
        
        $base_sdf = array(
            'order_bn'          => $this->_getOrderBn($tmp),
            'channel_id'        => $data['shop_id'],
            'channel_name'      => $data['shop_name'],
            'trade_time'        => strtotime($tmp['trade_time']),
            'fee_obj'           => $tmp['fee_obj'],
            'money'             => $res['bill_type'] ? round($tmp['outcome_amount'], 2) : round($tmp['income_amount'], 2),
            'fee_item'          => $tmp['fee_item'],
            'fee_item_id'       => isset($this->fee_item_rules[$tmp['fee_item']]) ? $this->fee_item_rules[$tmp['fee_item']] : 0,
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
    public function _filterData($data)
    {
        $new_data = array();
        $order_bn = $this->_getOrderBn($data);
        $new_data['order_bn'] = $order_bn;
        $new_data['trade_no'] = $order_bn;
        $new_data['trade_time'] = strtotime($data['trade_time']);
        $new_data['trade_type'] = $data['trade_type'];
        if (0 < $data['income_amount']) {
            $new_data['money'] = $data['income_amount'];
        } else {
            $new_data['money'] = $data['outcome_amount'];
        }
        $new_data['member'] = $data['member'];
        $new_data['unique_id'] = md5($order_bn . '-' . $new_data['money'] . '-' . $data['trade_type']);
        $new_data['platform_type'] = 'pinduoduo';
        $new_data['remarks'] = $data['remarks'];
        
        return $new_data;
    }
    
    /**
     * 获取ImportDateColunm
     * @param mixed $title title
     * @return mixed 返回结果
     */
    public function getImportDateColunm($title=null)
    {
        $timeColumn = ['发生时间'];
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