<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 处理抖音下载
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_data_bill_youzan extends financebase_abstract_bill
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
        if($row[0] == '类型' || $row[0] == "\u{FEFF}类型") {
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

            if(in_array($k, array('order_no','trade_no','trade_type')))
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

            if(in_array($k, array('income_amount', 'outcome_amount')))
            {
                $result = finance_io_bill_verify::isPrice($v);
                if ($result['status'] == 'fail') 
                {
                    $res['status'] = false;
                    $res['msg'] =  sprintf("%s : %s 金额(%s)格式错误！", $tmpRow['trade_no'], $this->ioTitle[$k], $v);
                    return $res;
                }
            }

            if($k == 'outcome_amount') {
                $tmp['outcome_amount'] = -$tmp['outcome_amount'];
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
        //店铺名称	类型	名称	业务单号	支付流水号	关联单号	交易来源地	账务主体	账户类型	收入(元)	支出(元)	余额(元)	支付方式
        //	交易对手	渠道	下单时间	入账时间	操作人	附加信息	备注	来源
        $title = array(
            // 'shop_name'           => '店铺名称',
            'trade_no'            => '支付流水号',
            'out_trade_no'        => '关联单号',
            'goods_name'          => '名称',
            'trade_time'          => '入账时间',
            'member'              => '交易对手',
            'income_amount'       => '收入(元)',
            'outcome_amount'      => '支出(元)',
            'amount'              => '余额(元)',
            'channel_name'        => '渠道',
            'trade_type'          => '类型',
            'remarks'             => '备注',
            'order_no'            => '业务单号',
            'lai_yuan_di'         => '交易来源地',
            'zhang_hu_lei_xiang'  => '账户',
            'zhi_fu_fang_shi'     => '支付方式',
            'zhang_hu_zhu_ti'     => '账务主体',
            'xia_dan_shi_jian'    => '下单时间',
            'cao_zuo_ren'         => '操作人',
            'fu_jia_xin_xi'       => '附加信息',
            'lai_yuan'            => '来源'
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
        if(!$this->rules) $this->getRules('youzan');

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

        $tmp['fee_obj'] = '有赞';
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
        $new_data['trade_no'] = $data['trade_no'];
        $new_data['financial_no'] = $data['financial_no'];
        $new_data['out_trade_no'] = $data['out_trade_no'];
        $new_data['trade_time'] = strtotime($data['trade_time']);
        $new_data['trade_type'] = $data['trade_type'];
        if (0 < $data['income_amount']) {
            $new_data['money'] = $data['income_amount'];
        } else {
            $new_data['money'] = $data['outcome_amount'];
        }
        $new_data['member'] = $data['member'];
        $new_data['unique_id'] = md5($data['trade_no'] . '-' . $data['trade_type']);

        $new_data['platform_type'] = 'youzan';
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
        $timeColumn = ['入账时间','下单时间'];
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