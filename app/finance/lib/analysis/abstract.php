<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_analysis_abstract extends eccommon_analysis_abstract {

	public $detail_options = array(
        'hidden' => false,
    );
    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        0 => array(
            'name' => '费用账单总计',
            'flag' => array(),
            'memo' => '全部费用总和（交易费用+固定费用）注：此店铺在选定时间内此科目',
            'icon' => 'coins.gif',
        ),
        1 => array(
            'name' => '收入总计（交易金额-费用）',
            'flag' => array(),
            'memo' => '交易费用-费用账单总计  注：此店铺在选定时间内此科目',
            'icon' => 'coins.gif',
        ),
        2 => array(
            'name' => '成本收入比（总费用/交易金额）',
            'flag' => array(),
            'memo' => '费用账单总计/交易金额  注：此店铺在选定时间内此科目',
            'icon' => 'money.gif',
        ),
        3 => array(
            'name' => '平均每单费用',
            'flag' => array(),
            'memo' => '交易费用/交易订单数量   注：此店铺在选定时间内此科目',
            'icon' => 'money.gif',
            'br' => true,
        ),
        4 => array(
            'name' => '交易费用',
            'flag' => array(),
            'memo' => '订单产生的所有交易费用总计  注：此店铺在选定时间内此科目',
            'icon' => 'coins.gif',
        ),
        5 => array(
            'name' => '交易金额',
            'flag' => array(),
            'memo' => '订单产生的所有交易金额总计  注：此店铺在选定时间内此科目',
            'icon' => 'coins.gif',
        ),
        6 => array(
            'name' => '交易订单数量',
            'flag' => array(),
            'memo' => '交易产生主订单的总数量  注：此店铺在选定时间内此科目',
            'icon' => 'money.gif',
        ),
        7 => array(
            'name' => '交易子订单数量',
            'flag' => array(),
            'memo' => '交易产生子订单的总数量  注：此店铺在选定时间内此科目',
            'icon' => 'money.gif',
        ),
        8 => array(
            'name' => '客单价',
            'flag' => array(),
            'memo' => '交易费用/交易订单数量   注：此店铺在选定时间内此科目',
            'icon' => 'coins.gif',
            'br' => true,
        ),
        9 => array(
            'name' => '运营费用',
            'flag' => array(),
            'memo' => '非订单产生的费用总计，非交易产生的固定费用支出，例如：平台保证金、软件服务费、 平台技术服务费等  注：此店铺在选定时间内此科目',
            'icon' => 'coins.gif',
        ),
    );
    
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app) 
    {
        parent::__construct($app);
        $this->_extra_view = array('finance' => 'analysis/bills/extra_view.html');

        $this->_render = kernel::single('finance_ctl_analysis_bills');
    }

    /**
     * 筛选项
     * 
     * @return void
     * @author 
     * */
    public function get_type()
    {
        $inputs = array(
            array(
                'type'  => $this->shops,
                'label' => '选择店铺',
                'name'  => 'shop_id',
                'value' => $this->_params['shop_id'],
                'required' => true,
            ),
            array(
                'type' => 'table:bill_fee_item@finance',
                'label' => '选择科目',
                'name' => 'fee_item_id',
                'value' => $this->_params['fee_item_id'],
            ),
            array(
                'type' => array(
                    '0' => '未开始',
                    '1' => '成功',
                    '2' => '失败',
                ),
                'label' => '结算状态',
                'name' => 'status',
                'value' => $this->_params['status'],  
            ),
        );
        
        if('finance_analysis_bookbills' == get_class($this)){
            unset($inputs[2]);
        }

        $ui = kernel::single('base_component_ui',null,app::get('finance'));

        foreach ($inputs as &$input) {
            $input['html'] = $ui->input($input);
        }
        return $inputs;
    }

        /**
     * 设置_params
     * @param mixed $params 参数
     * @return mixed 返回操作结果
     */
    public function set_params($params) 
    {
        $return  = parent::set_params($params);

        $shops = array();$shop_id = '';
        $shopList = kernel::single('finance_func')->shop_list(array('shop_type'=>'taobao','tbbusiness_type'=>'B'));
        foreach ($shopList as $shop){
            $shops[$shop['shop_id']] = $shop['name'];

            $shop_id = $shop['shop_id'];
        }
        unset($shopList);
        $this->shops = $shops;

        if(!$this->_params['shop_id'] && $shop_id){
            $this->_params['shop_id'] = $shop_id;
        }

        return $return;
    }
    /**
     * detail
     * @return mixed 返回值
     */
    public function detail()
    {
        $billModel = app::get('finance')->model('analysis_bills');
        
        // 订单数
        $sql = 'SELECT count(distinct(tid)) AS _c FROM ' . $billModel->table_name(1) . ' WHERE ' . $billModel->_filter($this->_params);
        $count = $billModel->db->selectrow($sql);
        $this->logs_options[6]['value'] = (int) $count['_c'];

        // 子订单数
        $sql = 'SELECT count(distinct(oid)) AS _c FROM ' . $billModel->table_name(1) . ' WHERE ' . $billModel->_filter($this->_params);
        $count = $billModel->db->selectrow($sql);
        $this->logs_options[7]['value'] = (int) $count['_c'];

        $sql = 'SELECT sum(total_amount) AS total_amount,sum(amount) AS amount FROM ' . $billModel->table_name(1) . ' WHERE ' . $billModel->_filter($this->_params);
        $row1 = $billModel->db->selectrow($sql);
        
        $bookbillModel = app::get('finance')->model('analysis_book_bills');
        $sql = 'SELECT sum(amount) AS amount FROM ' . $bookbillModel->table_name(1) . ' WHERE ' . $bookbillModel->_filter($this->_params);
        $sql2 = $sql . ' AND journal_type in("102","106")';
        $row2 = $bookbillModel->db->selectrow($sql2);
        
        // 充值
        //$sql3 = $sql . ' AND journal_type in("101","105")';
        //$row3 = $bookbillModel->db->selectrow($sql3);
        
        // 冻结
        //$sql4 = $sql . ' AND journal_type="103"';
        //$row4 = $bookbillModel->db->selectrow($sql4);
        
        // 解冻
        //$sql5 = $sql . ' AND journal_type="104"';
        //$row5 = $bookbillModel->db->selectrow($sql5);

        // 交易金额
        $this->logs_options[5]['value'] = (float) $row1['total_amount'];

        // 费用账单金额
        $this->logs_options[0]['value'] = bcadd((float) $row1['amount'], (float) $row2['amount'],2);

        // 收入统计
        $this->logs_options[1]['value'] = bcsub((float) $this->logs_options[5]['value'],(float) $this->logs_options[0]['value'],2);

        // 成本收入比
        $this->logs_options[2]['value'] = $this->logs_options[0]['value'] > 0 ? bcdiv((float) $this->logs_options[0]['value'], (float) $this->logs_options[5]['value'],4) * 100 : 0;
        $this->logs_options[2]['value'] .= '%';

        // 平均每单费用
        $this->logs_options[3]['value'] = $this->logs_options[6]['value'] > 0 ? bcdiv((float) $row1['amount'], $this->logs_options[6]['value'],2) : 0;

        // 订单产生的费用总计
        $this->logs_options[4]['value'] = (float) $row1['amount'];
        
        // 客单价
        $this->logs_options[8]['value'] = $this->logs_options[6]['value'] > 0 ? bcdiv((float) $this->logs_options[5]['value'] , $this->logs_options[6]['value'] ,2 ) : 0;

        // 非订单产生费用总计
        $this->logs_options[9]['value'] = (float) $row2['amount'];
        //$this->logs_options[9]['name'] .= '(充值:+' . (float)$row3['amount'] . ',扣除:-' . (float)$row2['amount'] . ',冻结:' . (float)$row4['amount'] . ',解冻:' . (float)$row5['amount'] . ')';

        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = $option['value'];
            $detail[$option['name']]['memo'] = $option['memo'];
            $detail[$option['name']]['icon'] = $option['icon'];
            $detail[$option['name']]['br'] = $option['br'] == true ? true : false;
        }
        $this->_render->pagedata['detail'] = $detail;
    }
}