<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 处理京东下载
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_data_bill_360buy extends financebase_abstract_bill
{
    public $order_bn_prefix = '';

    public $column_num = 21;

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
        $row = array_map('trim',$row);

        if(!$this->ioTitle){
            $this->ioTitle = $this->getTitle();
            $this->ioTitleKey = array_keys($this->ioTitle);
        }

        $titleKey = array();
        foreach ($title as $k => $t) {
            $titleKey[$k] = array_search($t, $this->getTitle());

            if ($titleKey[$k] === false) {
                return array('status' => false, 'msg' => '未定义字段`' . $t . '`');
            }
        }

        $res = array('status'=>true,'data'=>array(),'msg'=>'');

        if( $this->column_num <= count($row) and $row[0] != '订单编号' )
        {
            
            $tmp = array_combine($titleKey , $row);
            //判断参数不能为空
            foreach ($tmp as $k => $v) {


                if(in_array($k, array('order_no','financial_no','trade_settlement_time')))
                {
                    if(!$v)
                    {
                        $res['status'] = false;
                        $res['msg'] =  sprintf("LINE %d : %s 不能为空！", $offset, $this->ioTitle[$k]);
                        return $res;
                    }
                }

                if(in_array($k, array('trade_create_time','trade_pay_time','trade_settlement_time')))
                {
                    $result = finance_io_bill_verify::isDate($v);
                    if ($result['status'] == 'fail') 
                    {
                        $res['status'] = false;
                        $res['msg'] =  sprintf("LINE %d : %s 时间(%s)格式错误！", $offset, $this->ioTitle[$k], $v);
                        return $res;
                    }
                }

                if(in_array($k, array('amount')))
                {
                    $result = finance_io_bill_verify::isPrice($v);
                    if ($result['status'] == 'fail') 
                    {
                        $res['status'] = false;
                        $res['msg'] =  sprintf("LINE %d : %s 金额(%s)格式错误！", $offset, $this->ioTitle[$k], $v);
                        return $res;
                    }
                }

                if(in_array($k, array('order_no','financial_no','out_trade_no','goods_bn'))){
                    $tmp[$k]=trim($v,'=\"');
                }
            }


            $res['data'] = $tmp;

        }

        return $res;
    }

    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle()
    {

        $title = array(
            'order_no'              => '订单编号',
            'financial_no'          => '单据编号',
            'order_type'            => '单据类型',
            'goods_bn'              => '商品编号',
            'out_trade_no'          => '商户订单号',
            'goods_name'            => '商品名称',
            'settlement_status'     => '结算状态',
            'trade_create_time'     => '费用发生时间',
            'trade_pay_time'        => '费用计费时间',
            'trade_settlement_time' => '费用结算时间',
            'trade_type'            => '费用项',
            'amount'                => '金额',
            'currency'              => '币种',
            'bill_type'             => '商家应收/应付',
            'settlement_remarks'    => '钱包结算备注',
            'shop_bn'               => '店铺号',
            'jd_store_bn'           => '京东门店编号',
            'brand_store_bn'        => '品牌门店编号',
            'store_name'            => '门店名称',
            'remarks'               => '备注',
            'fee_direction'         => '收支方向',
            'goods_number'          => '商品数量',
            'jd_trade_time'         => '对账日期',
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
        if(!$this->rules) $this->getRules('360buy');

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
        $ioType = kernel::single('financebase_io_'.$file_type);
        $row = $ioType->getData($file_name,0,1);
        $title = array_values($this->getTitle());
        sort($title);

        $jdTitle = $row[0];
        sort($jdTitle);
        if ($title == $jdTitle) {
            return array(true, '文件模板匹配', $row[0]);
        }
        if (!array_diff($jdTitle, $title)) {
            return array(true, '文件模板匹配', $row[0]);
        }

        return array(false, '文件模板错误：' . var_export($row[0], true).'，正确的为：' . var_export($title, 1));
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

        $tmp['fee_obj'] = '京东';
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
            'trade_time'        => strtotime($tmp['trade_settlement_time']),
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
        
        $new_data['order_bn'] = $this->_getOrderBn($data);
        $new_data['trade_no'] = '';
        $new_data['financial_no'] = $data['financial_no'];// 京东把订单号存在财务流水号里，只作为生成唯一标识用
        $new_data['out_trade_no'] = $data['out_trade_no'];
        $new_data['trade_time'] = $data['trade_settlement_time'] ? strtotime($data['trade_settlement_time']) : 0;
        $new_data['trade_type'] = $data['trade_type'];
        $new_data['money'] = $data['amount'];
        $new_data['member'] = '';
        $new_data['unique_id'] = $data['financial_no'];

        $new_data['platform_type'] = '360buy';
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
        $timeColumn = ['费用结算时间','费用计费时间','费用发生时间'];
        $timeCol = [];
        foreach ($timeColumn as $v) {
             if($k = array_search($v, $title)) {
                $timeCol[] = $k+1;
             }
         } 
        $timezone = defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 0;
        return array('column'=>$timeCol,'time_diff'=>$timezone * 3600 );
    }

}