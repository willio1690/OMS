<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 处理vop
 *
 * @author
 * @version 0.1
 */
class financebase_data_bill_vop extends financebase_abstract_bill
{
   
    /**
     * batchTransferData
     * @param mixed $data 数据
     * @param mixed $title title
     * @return mixed 返回值
     */

    public function batchTransferData($data, $title) {
       

       if (!$this->ioTitle) {
            $this->ioTitle = $this->getTitle();
        }

        $titleKey = array();
        foreach ($title as $k => $t) {
            $titleKey[$k] = array_search($t, $this->getTitle());
        }

        
        $reData = [];

        foreach($data as $row) {
            $row = array_map('trim', $row);
            
            $tmpRow = [];


            foreach($row as $k => $v) {
                if($titleKey[$k]) {
                    $tmpRow[$titleKey[$k]] = $v;
                }
            }
            $reData[] = $tmpRow;
           
            
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

            if(in_array($k, array('order_no','amount','trade_type')))
            {
                if(!$v)
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 不能为空！", $row['trade_no'], $this->ioTitle[$k]);
                    return $res;
                }
            }

            if(in_array($k, array('signtime')))
            {
                $result = finance_io_bill_verify::isDate($v);
                if ($result['status'] == 'fail') 
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 时间(%s)格式错误！", $row['order_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }

            if(in_array($k, array('amount')))
            {
                
                $result = finance_io_bill_verify::isPrice($v);
                if ($result['status'] == 'fail') 
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 金额(%s)格式错误！", $row['order_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }
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
            'order_no'          => 'PO编号',
            'trade_type'        => '业务类型',
            'amount'            => '销售金额',
            'trade_time'        => '签收时间',
            'financial_no'      =>  '账单号',
            'unique_id'         =>  '单据唯一值',

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
     * @Author YangYiChao
     * @Date   2019-06-03
     * @param  [Array]     $params    参数
     * @return [String]                 具体类别
     */
    public function getBillCategory($params)
    {
        if(!$this->rules) $this->getRules('vop');

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

        $tmp['fee_obj'] = '唯品会';
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
            'money'             => round($tmp['amount'],2),
            'fee_item'          => $tmp['fee_item'],
            'fee_item_id'       => isset($this->fee_item_rules[$tmp['fee_item']]) ? $this->fee_item_rules[$tmp['fee_item']] : 0,
            'credential_number' => $tmp['financial_no'],// 单据编号
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
        
        $new_data['order_bn'] = $this->_getOrderBn($data);
        $new_data['trade_no'] = $data['trade_no'];
        $new_data['financial_no'] = $data['financial_no'];
        $new_data['out_trade_no'] = $data['out_trade_no'];
        $new_data['trade_time'] = strtotime($data['trade_time']);
        $new_data['trade_type'] = $data['trade_type'];
        $new_data['money'] = $data['amount'];
        $new_data['member'] = $data['member'];
        $new_data['unique_id'] = md5($data['unique_id'] . '-' . $data['trade_type']);

        $new_data['platform_type'] = 'vop';
        $new_data['remarks'] = $data['remarks'];

        return $new_data;
    }

   
}