<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 微信账单导入
 * Class financebase_data_bill_wechatpay
 */
class financebase_data_bill_wechatpay extends financebase_abstract_bill
{
    public $column_to_row = [
        'charged_price',
    ];
    
    /**
     * batchTransferData
     * @param mixed $data 数据
     * @param mixed $title title
     * @return mixed 返回值
     */

    public function batchTransferData($data, $title)
    {
        if (!$this->ioTitle) {
            $this->ioTitle = $this->getTitle();
        }
        
        $titleKey = array();
        foreach ($title as $k => $t) {
            $titleKey[$k] = array_search($t, $this->getTitle());
        }
        $reData = [];
        foreach ($data as $row) {
            $row = array_map('trim', $row);
            if ($row[0] == '交易时间' || $row[0] == "\u{FEFF}交易时间") {
                continue;
            }
            $tmpRow = [];
            foreach ($row as $k => $v) {
                if ($titleKey[$k]) {
                    $tmpRow[$titleKey[$k]] = trim($v, '\`');
                }
            }
            $reData[] = $tmpRow;
            foreach ($this->column_to_row as $crv) {
                if ($tmpRow[$crv] != 0) {
                    $tmp = $tmpRow;
                    if ($crv == 'charged_price') {
                        $tmp['trade_status'] = $tmpRow['trade_status'] == 'SUCCESS' ? 'SUCCESS_CHARGED' : 'REFUND_CHARGED';
                    }
                    $reData[] = $tmp;
                }
            }
        }
        return $reData;
    }
    
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
        $res = array('status'=>true,'data'=>array(),'msg'=>'');
    
        $tmp = [];
        //判断参数不能为空
        foreach ($row as $k => $v) {
            $tmp[$k] = trim($v, '\'');
            
            if(in_array($k, array('channel_name', 'trade_no', 'trade_status')))
            {
                if(!$v)
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 不能为空！", $row['trade_no'], $this->ioTitle[$k]);
                    return $res;
                }
    
                if ($k == 'trade_status') {
                    $bill_category = $this->getBillCategory($tmp);
                    if (empty($bill_category)) {
                        $errmsg[] = "未识别的类型：" . $v;
                    } elseif ($bill_category == '货款' && empty($tmp['out_trade_no'])) {
                        $errmsg[] = "商户订单号不能为空！";
                    }
                }
            }
    
    
    
    
            if(in_array($k, array('trade_time')))
            {
                $result = finance_io_bill_verify::isDate($v);
                if ($result['status'] == 'fail')
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 时间(%s)格式错误！", $row['trade_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
            
            
    
        }
    
        $tmp['trade_type'] = $this->getFeeType($tmp);
        if (empty($tmp['trade_type'])) {
            $errmsg[] = '无法识别财务类型：' . $tmp['trade_status'];
        }
        
        $tmp['amount'] = $this->_getMoney($tmp);
        $result = finance_io_bill_verify::isPrice($tmp['amount']);
        if ($result['status'] == 'fail')
        {
            $res['status'] = false;
            $res['msg'] =  sprintf("%s : %s金额(%s)格式错误！", $row['trade_no'],$this->ioTitle['trade_status'],$tmp['amount']);
            return $res;
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
    
        $title = array(
            'trade_time' => '交易时间',
            'mp_id' => '公众账号ID',
            'business_no' => '商户号',
            'spec_business_no' => '特约商户号',
            'device_no' => '设备号',
            'trade_no' => '微信订单号',
            'out_trade_no' => '商户订单号',
            'member' => '用户标识',
            'channel_name' => '交易类型',
            'trade_status' => '交易状态',
            'bank' => '付款银行',
            'current' => '货币种类',
            'trade_amount' => '应结订单金额',
            'discount' => '代金券金额',
            'refund_no' => '微信退款单号',
            'out_refund_no' => '商户退款单号',
            'real_refund_amount' => '退款金额',
            'refund_discount' => '充值券退款金额',
            'refund_type' => '退款类型',
            'refund_status' => '退款状态',
            'goods_name' => '商品名称',
            'package' => '商户数据包',
            'charged_price' => '手续费',
            'rate' => '费率',
            'real_trade_amount' => '订单金额',
            'refund_amount' => '申请退款金额',
            'rate_memo' => '费率备注',
        );
        
        return $title;
    }
    
    // 获取订单号
//    public function _getOrderBn($params)
//    {
//
//        return $params['out_trade_no'];
//    }
    // 获取订单号
    /**
     * _getOrderBn
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _getOrderBn($params)
    {
        $trade_status = $params['trade_status'];
        
        switch ($trade_status) {
            case 'SUCCESS':
                $order_bn = $params['out_trade_no'];
                break;
            case 'REFUND':
                $order_bn = $params['out_refund_no'];
                break;
            case 'REFUND_CHARGED':
                $order_bn = $params['out_refund_no'];
                break;
            case 'SUCCESS_CHARGED':
                $order_bn = $params['out_trade_no'];
                break;
        }
        return $order_bn;
    }
    
    
    
    /**
     * 获取具体类别
     * @Author YangYiChao
     * @Date   2019-06-03
     * @param  [Array]     $params    参数
     * @return [String]                 具体类别
     */
    public function getBillCategory($params)
    {
        if(!$this->rules) $this->getRules('wechatpay');
        
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
     * @Author YangYiChao
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
     * @Author YangYiChao
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
        $oMonthlyReport = kernel::single('finance_monthly_report');
        
        $tmp['fee_obj'] = '微信';
        $tmp['fee_item'] = $bill_category;
        
        $res = $this->getBillType($tmp,$shop_id);
        if(!$res['status']) return false;
        
        if(!$data['shop_name']){
            $data['shop_name'] = isset($this->shop_list[$data['shop_id']]) ? $this->shop_list[$data['shop_id']]['name'] : '';
        }
        
        $base_sdf = array(
            'order_bn'          => $tmp['out_trade_no'],
            'channel_id'        => $data['shop_id'],
            'channel_name'      => $data['shop_name'],
            'trade_time'        => strtotime($tmp['trade_time']),
            'fee_obj'           => $tmp['fee_obj'],
            'money'             => round($this->_getMoney($tmp),2),
            'fee_item'          => $tmp['fee_item'],
            'fee_item_id'       => isset($this->fee_item_rules[$tmp['fee_item']]) ? $this->fee_item_rules[$tmp['fee_item']] : 0,
            'credential_number' => $this->_getOrderBn($tmp),// 单据编号#
            'member'            => $tmp['member'],
            'memo'              => $tmp['rate_memo'],
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
    
    // 更新订单号
    /**
     * 更新OrderBn
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function updateOrderBn($data)
    {
        $this->_formatData($data);
        $mdlBill = app::get('finance')->model('bill');
        if(!$this->shop_list_by_name)
        {
            $this->shop_list_by_name = financebase_func::getShopList(financebase_func::getShopType());
            $this->shop_list_by_name = array_column($this->shop_list_by_name,null,'name');
        }
        
        foreach ($data as $v)
        {
            if('订单编号' == $v[0]) continue;
            
            if(!$v[21] || !$v[22] || !$v[23]) continue;
            
            $shop_id = isset($this->shop_list_by_name[$v[21]]) ? $this->shop_list_by_name[$v[21]]['shop_id'] : 0;
            if(!$shop_id) continue;
            
            $filter = array('bill_bn'=>$v[22],'shop_id'=>$shop_id);
            
            
            // 找到unique_id
            $bill_info = $mdlBill->getList('unique_id,bill_id',$filter,0,1);
            if(!$bill_info) continue;
            $bill_info = $bill_info[0];
            
            
            if($mdlBill->update(array('order_bn'=>$v[23]),array('bill_id'=>$bill_info['bill_id'])))
            {
                app::get('financebase')->model('bill')->update(array('order_bn'=>$v[23]),array('unique_id'=>$bill_info['unique_id'],'shop_id'=>$shop_id));
                $op_name = kernel::single('desktop_user')->get_name();
                $content = sprintf("订单号改成：%s",$v[23]);
                finance_func::addOpLog($v[22],$op_name,$content,'更新订单号');
                
            }
            
        }
        
    }
    
    /**
     * _filterData
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function _filterData($data)
    {
        $new_data = array();
        
        $new_data['order_bn'] = $data['order_bn'] ? $data['order_bn'] : $this->_getOrderBn($data);
        $new_data['trade_no'] = $data['trade_no'];
        $new_data['financial_no'] = $this->_getOrderBn($data);
        $new_data['out_trade_no'] = $data['out_trade_no'];
        $new_data['trade_time'] = strtotime($data['trade_time']);
        
        $new_data['trade_type'] = $data['trade_type'];
        $new_data['money'] = $this->_getMoney($data);
        $new_data['member'] = $data['member'];
        $new_data['unique_id'] = $this->getUniqueId($data);
        
        $new_data['platform_type'] = 'wechatpay';
        $new_data['remarks'] = $data['rate_memo'];
        
        return $new_data;
    }
    
    /**
     * 获取ImportDateColunm
     * @param mixed $title title
     * @return mixed 返回结果
     */
    public function getImportDateColunm($title=null)
    {
        $timeColumn = ['交易时间'];
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
    
    /**
     * 获取FeeType
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getFeeType($params)
    {
        $trade_type = $params['trade_status'];

        switch ($trade_type) {
            case 'SUCCESS':
                $category = 'SUCCESS';//交易付款
                break;
            case 'REFUND':
                $category = 'REFUND';//交易退款
                break;
            case 'REFUND_CHARGED':
                $category = 'REFUND-手续费';//银行手续费
                break;
            case 'SUCCESS_CHARGED':
                $category = 'SUCCESS-手续费';//银行手续费
                break;
        }

        return $category;
    }
    
    /**
     * _getMoney
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _getMoney($params)
    {
        $trade_type = $params['trade_status'];
        
        switch ($trade_type) {
            case 'SUCCESS':
                $money = $params['real_trade_amount'];
                break;
            case 'REFUND':
                $money = -$params['real_refund_amount'];
                break;
            case 'REFUND_CHARGED':
            case 'SUCCESS_CHARGED':
                $money = floatval($params['charged_price']) <= 0 ? abs($params['charged_price']) : -$params['charged_price'];
                break;
        }
        return $money;
    }
    
    // 获取唯一值
    /**
     * _getUniqueNo
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _getUniqueNo($params)
    {
        $trade_type = $params['trade_status'];
        
        switch ($trade_type) {
            case 'SUCCESS':
                $order_bn = $trade_type . $params['trade_no'] . $params['out_trade_no'];
                break;
            case 'REFUND':
                $order_bn = $trade_type . $params['refund_no'] . $params['out_refund_no'];
                break;
            case 'REFUND_CHARGED':
                $order_bn = $trade_type . $params['refund_no'] . $params['out_refund_no'];
                break;
            case 'SUCCESS_CHARGED':
                $order_bn = $trade_type . $params['trade_no'] . $params['out_trade_no'];
                break;
        }
        return $order_bn;
    }
    
    /**
     * 获取unique_id
     * @param $data
     * @return string
     */
    public function getUniqueId($data)
    {
        $result = $this->_getUniqueNo($data);
        return md5($result);
    }
    
}