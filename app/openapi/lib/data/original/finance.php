<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 财务账单
 */
class openapi_data_original_finance
{
    /**
     * 获取账单列表
     * 
     * @param array $filter
     * @param string $start_time
     * @param string $end_time
     * @return array
     */

    public function getList($params, $offset = 0, $limit = 100)
    {
        $billObj = app::get('financebase')->model('bill');
        $mdlBillBase = app::get('financebase')->model("bill_base");
        $orderMdl = app::get('ome')->model('orders');

        //创建时间范围
        if (empty($params['create_time'][0]) || empty($params['create_time'][1])) {
            return false;
        }

        //所有店铺列表
        $shopList = $this->getShopList();

        //filter
        $filter = array();

        //所属店铺
        $shop_ids = array();
        if ($params['shop_bns']) {
            foreach ($shopList as $value) {
                if (in_array($value['shop_bn'], $params['shop_bns'])) {
                    $shop_ids[] = $value['shop_id'];
                }
            }
        }

        if ($shop_ids) {
            $filter['shop_id'] = $shop_ids;
        }

        //平台类型
        if ($params['platform_type']) {
            $filter['platform_type'] = $params['platform_type'];
        }
        //创建时间范围
        if ($params['create_time'][0] && $params['create_time'][1]) {
            $filter['create_time|between'] = array($params['create_time'][0], $params['create_time'][1]);
        }
        //账单时间范围
        if ($params['trade_time'][0] && $params['trade_time'][1]) {
            $filter['trade_time|between'] = array($params['trade_time'][0], $params['trade_time'][1]);
        }

        //订单创建时间范围
        if ($params['order_create_date'][0] && $params['order_create_date'][1]) {
            $filter['order_create_date|between'] = array($params['trade_time'][0], $params['trade_time'][1]);
        }

        //count
        $countNum = $billObj->count($filter);
        if (empty($countNum)) {
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }

        //list
        $fields   = 'id,unique_id,order_bn,trade_no,out_trade_no,financial_no,money,member,trade_type,platform_type,shop_id,trade_time,order_create_date,create_time';
        $tempList = $billObj->getList($fields, $filter, $offset, $limit);
        if (empty($tempList)) {
            return array(
                'lists' => array(),
                'count' => $countNum,
            );
        }
        
        //获取订单列表
        $orderBns = array_column($tempList, 'order_bn');
        $orderBns = array_filter($orderBns);
        
        $orderList = array();
        if($orderBns){
            $tempOrder = $orderMdl->getList('order_id,order_bn,shop_id,order_type,order_bool_type', array('order_bn'=>$orderBns));
            foreach ((array)$tempOrder as $key => $val)
            {
                $shop_id = $val['shop_id'];
                $order_bn = $val['order_bn'];
                
                $orderList[$shop_id][$order_bn] = $val;
            }
            
            unset($tempOrder);
        }
        
        //list
        $dataList    = array();
        foreach ($tempList as $key => $val)
        {
            $shop_id = $val['shop_id'];
            $order_bn = $val['order_bn'];
            
            //店铺信息
            $shopInfo      = $shopList[$shop_id];
            $base_bill_row = $mdlBillBase->getList('content', array('shop_id' => $val['shop_id'], 'unique_id' => $val['unique_id']));
            $array_content = json_decode($base_bill_row[0]['content'], 1);
        
            $val['trade_desc']         = (string)$array_content['trade_desc'];
            $val['trade_order_bn']     = (string)str_replace('"',"'",$array_content['trade_base_order_bn']);
            $val['channel_name']       = (string)$array_content['channel_name'];
            $val['remarks']            = (string)$array_content['remarks'];
            $val['goods_name']         = (string)$array_content['goods_name'];
            $val['amount']             = $array_content['amount'];
            $val['bill_source']        = (string)$array_content['bill_source'];
            $val['bill_type']          = (string)$array_content['order_type'];
            $val['shop_bn']            = $shopInfo['shop_bn'];
            $val['shop_type']          = kernel::single('ome_shop_type')->shop_name($shopInfo['shop_type']);
            $val['shop_name']          = $shopInfo['name'];
            // $val['remarks']            = (string)$base_bill_row['remarks'];
            $val['settlement_remarks'] = (string)$array_content['settlement_remarks'];
            $val['goods_bn']           = (string)$array_content['goods_bn'];
            $val['goods_number']       = $array_content['goods_number'];
            $val['basic_material_bn']  = $this->getBasicMaterialBn($val);
            
            //关联订单类型
            $val['order_type'] = $orderList[$shop_id][$order_bn]['order_type'];
            
            //是否分销订单
            $order_bool_type = $orderList[$shop_id][$order_bn]['order_bool_type'];
            $val['is_daixiao'] = 'false';
            if($order_bool_type){
                $val['is_daixiao'] = $order_bool_type & ome_order_bool_type::__DAIXIAO_CODE ? 'true' : 'false';
            }
            
            $dataList[] = $val;
        }

        return array('lists' => $dataList, 'count' => $countNum);
    }

    /**
     * 获取BasicMaterialBn
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function getBasicMaterialBn($val) {
        if (in_array($val['shop_type'],['京东','京东厂直'])) {
            if(empty($val['goods_bn'])) {
                return [];
            }
            $order = app::get('ome')->model('orders')->db_dump(['order_bn'=>$val['order_bn'], 'shop_id'=>$val['shop_id']], 'order_id');
            if(!$order) {
                return [];
            }
            $objRow = app::get('ome')->model('order_objects')->db_dump(['order_id'=>$order['order_id'], 'oid'=>$val['goods_bn']], 'obj_id');
            if(empty($objRow)) {
                return [];
            }
            $itemRows = app::get('ome')->model('order_items')->getList('bn,nums', ['obj_id'=>$objRow['obj_id']]);
            if(empty($itemRows)) {
                return [];
            }
        }else{
            $order = app::get('ome')->model('orders')->db_dump(['order_bn'=>$val['order_bn'], 'shop_id'=>$val['shop_id']], 'order_id');
            if(!$order) {
                return [];
            }
            $itemRows = app::get('ome')->model('order_items')->getList('bn,nums', ['order_id'=>$order['order_id']]);
            if(empty($itemRows)) {
                return [];
            }
        }
        
        return $itemRows;
    }

    /**
     * 获取店铺列表
     */
    public function getShopList()
    {
        $shopObj = app::get('ome')->model('shop');

        $tempData = $shopObj->getlist('shop_id,shop_bn,name,shop_type', array());
        if (empty($tempData)) {
            return array();
        }

        $shopList = array();
        foreach ($tempData as $value) {
            $shop_id = $value['shop_id'];

            $shopList[$shop_id] = $value;
        }

        return $shopList;
    }
    
    /**
     * 获取精准通账单列表
     * 
     * @param array $filter
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function getJztList($params, $offset = 0, $limit = 100)
    {
        $billJztObj = app::get('financebase')->model('bill_import_jzt');
        
        //创建日期范围
        if (empty($params['at_time'][0]) || empty($params['at_time'][1])) {
            return false;
        }

        //创建时间范围
        if ($params['at_time'][0] && $params['at_time'][1]) {
            $filter['at_time|between'] = array($params['at_time'][0], $params['at_time'][1]);
        }
        //账单时间范围
        if ($params['launchtime'][0] && $params['launchtime'][1]) {
            $filter['launchtime|between'] = array($params['launchtime'][0], $params['launchtime'][1]);
        }
        if($params['pay_serial_number']){
            $filter['pay_serial_number'] = $params['pay_serial_number'];
        }
        
        if($params['account']){
            $filter['account'] = $params['account'];
        }
        
        if($params['trade_type']){
            $filter['trade_type'] = $params['trade_type'];
        }
        
        if($params['plan_id']){
            $filter['plan_id'] = $params['plan_id'];
        }
        
        //count
        $countNum = $billJztObj->count($filter);
        if (empty($countNum)) {
            return array(
                    'lists' => array(),
                    'count' => 0,
            );
        }
        
        //list
        $fields   = '*';
        $tempList = $billJztObj->getList($fields, $filter, $offset, $limit);
        if (empty($tempList)) {
            return array(
                    'lists' => array(),
                    'count' => $countNum,
            );
        }
        
        $dataList = array();
        foreach ($tempList as $key => $val)
        {
            $dataList[] = array(
                    'pay_serial_number' => $val['pay_serial_number'],
                    'account' => $val['account'],
                    'trade_type' => $val['trade_type'],
                    'plan_id' => $val['plan_id'],
                    'amount' => $val['amount'],
                    'launchtime' => $val['launchtime'],
                    'at_time' => $val['at_time'],
                    'up_time' => $val['up_time'],
                    'crc_unique' => $val['crc_unique'], //唯一编号
            );
        }
        
        //unset
        unset($filter, $tempList);
        
        return array('lists'=>$dataList, 'count'=>$countNum);
    }
    
    /**
     * 获取京东钱包流水列表
     * 
     * @param array $filter
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function getJdBillList($params, $offset = 0, $limit = 100)
    {
        $jdBillObj = app::get('financebase')->model('bill_import_jdbill');
        
        //投放日期范围
        if (empty($params['at_time'][0]) || empty($params['at_time'][1])) {
            return false;
        }
        
        //创建时间范围
        if ($params['at_time'][0] && $params['at_time'][1]) {
            $filter['at_time|between'] = array($params['at_time'][0], $params['at_time'][1]);
        }
        //账单时间范围
        if ($params['bill_time'][0] && $params['bill_time'][1]) {
            $filter['bill_time|between'] = array($params['bill_time'][0], $params['bill_time'][1]);
        }
        //交易日期范围
        if($params['trade_start_time'] && $params['trade_end_time']){
            $filter['trade_time|between'] = array($params['trade_start_time'], $params['trade_end_time']);
        }
        
        if($params['member_id']){
            $filter['member_id'] = $params['member_id'];
        }
        
        if($params['account_no']){
            $filter['account_no'] = $params['account_no'];
        }
        
        if($params['trade_no']){
            $filter['trade_no'] = $params['trade_no'];
        }
        
        //count
        $countNum = $jdBillObj->count($filter);
        if (empty($countNum)) {
            return array(
                    'lists' => array(),
                    'count' => 0,
            );
        }
        
        //list
        $fields   = '*';
        $tempList = $jdBillObj->getList($fields, $filter, $offset, $limit);
        if (empty($tempList)) {
            return array(
                    'lists' => array(),
                    'count' => $countNum,
            );
        }
        
        $dataList = array();
        foreach ($tempList as $key => $val)
        {
            $dataList[] = array(
                    'member_id' => $val['member_id'],
                    'account_no' => $val['account_no'],
                    'account_name' => $val['account_name'],
                    'trade_time' => $val['trade_time'],
                    'trade_no' => $val['trade_no'],
                    'account_balance' => $val['account_balance'],
                    'income_fee' => $val['income_fee'],
                    'outgo_fee' => $val['outgo_fee'],
                    'bill_time' => $val['bill_time'],
                    'remark' => $val['remark'],
                    'at_time' => $val['at_time'],
                    'up_time' => $val['up_time'],
                    'crc_unique' => $val['crc_unique'], //唯一编号
            );
        }
        
        //unset
        unset($filter, $tempList);
        
        return array('lists'=>$dataList, 'count'=>$countNum);
    }

    #获取账期明细
    /**
     * 获取ReportItems
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getReportItems($filter, $offset, $limit) {
        $itemModel = app::get('finance')->model('monthly_report_items');
        $return = ['count'=>0, 'lists'=>[]];
        $count = $itemModel->count($filter);
        if($count < 1) {
            return $return;
        }
        $return['count'] = $count;
        $shop = $this->getShopList();
        $lists = $itemModel->getList('*', $filter, $offset, $limit);
        if(empty($lists)) {
            return $return;
        }
        $reportRows = app::get('finance')->model('monthly_report')->getList('monthly_id,shop_id,monthly_date', ['monthly_id'=>array_column($lists, 'monthly_id')]);
        $reportRows = array_column($reportRows, null, 'monthly_id');
        
        $itemIds = array_column($lists, 'id');
        $arItems = $this->_getArItems($itemIds);
        $goodsItems = $this->_getArGoodsItems($itemIds);
        $billItems = $this->_getBillItems($itemIds);

        foreach($lists as $v) {
            $shop_id = $reportRows[$v['monthly_id']] ? $reportRows[$v['monthly_id']]['shop_id'] : '-1';
            $return['lists'][] = [
                'gap_id' => $v['id'],
                'monthly_date' => $reportRows[$v['monthly_id']] ? $reportRows[$v['monthly_id']]['monthly_date'] : '',
                'order_bn' => $v['order_bn'],
                'shop_code' => $shop[$shop_id] ? $shop[$shop_id]['shop_bn'] : '',
                'shop_name' => $shop[$shop_id] ? $shop[$shop_id]['name'] : '',
                'ship_time' => $v['ship_time'] > 0 ? date('Y-m-d H:i:s', $v['ship_time']) : '',
                'reship_time' => $v['reship_time'] > 0 ? date('Y-m-d H:i:s', $v['reship_time']) : '',
                'shishou_trade_time' => $v['shishou_trade_time'] > 0 ? date('Y-m-d H:i:s', $v['shishou_trade_time']) : '',
                'shitui_trade_time' => $v['shitui_trade_time'] > 0 ? date('Y-m-d H:i:s', $v['shitui_trade_time']) : '',
                'yingshou_money' => $v['yingshou_money'],
                'yingtui_money' => $v['yingtui_money'],
                'xiaotui_total' => $v['xiaotui_total'],
                'shishou_money' => $v['shishou_money'],
                'shitui_money' => $v['shitui_money'],
                'shouzhi_total' => $v['shouzhi_total'],
                'gap' => $v['gap'],
                'gap_type' => $v['gap_type'],
                'verification_status' => $itemModel->schema['columns']['verification_status']['type'][$v['verification_status']],
                'memo' => $v['memo'],
                'ar_items' => $arItems[$v['id']],
                'goods_items' => $goodsItems[$v['id']],
                'bill_items' => $billItems[$v['id']],
            ];
        }
        return $return;
    }

    protected function _getArItems($itemIds) {
        $arRows = app::get('finance')->model('ar')->getList('*', ['monthly_item_id'=>$itemIds]);
        $arItems = [];
        foreach($arRows as $v) {
            $arItems[$v['monthly_item_id']][] = [
                'ar_id' => $v['ar_id'],
                'ar_bn' => $v['ar_bn'],
                'order_bn' => $v['order_bn'],
                'relate_order_bn' => $v['relate_order_bn'],
                'member' => $v['member'],
                'status' => kernel::single('finance_ar')->get_name_by_status($v['status']),
                'verification_time' => $v['verification_time'] > 0 ? date('Y-m-d H:i:s', $v['verification_time']) : '',
                'type' => kernel::single('finance_ar')->get_name_by_type($v['type']),
                'trade_time' => $v['trade_time'] > 0 ? date('Y-m-d H:i:s', $v['trade_time']) : '',
                'create_time' => $v['create_time'] > 0 ? date('Y-m-d H:i:s', $v['create_time']) : '',
                'delivery_time' => $v['delivery_time'] > 0 ? date('Y-m-d H:i:s', $v['delivery_time']) : '',
                'money' => $v['money'],
                'actually_money' => $v['actually_money'],
                'verification_status' => kernel::single('finance_ar')->get_name_by_verification_status($v['verification_status']),
            ];
        }
        return $arItems;
    }

    protected function _getArGoodsItems($itemIds) {
        $arRows = app::get('finance')->model('ar')->getList('ar_id, ar_bn, type, monthly_item_id', ['monthly_item_id'=>$itemIds]);
        if(empty($arRows)) {
            return [];
        }
        $arRows = array_column($arRows, null, 'ar_id');
        $arIds = array_column($arRows, 'ar_id');
        $items = app::get('finance')->model('ar_items')->getList('*', ['ar_id'=>$arIds]);
        $bmList = app::get('material')->model('basic_material')->getList('bm_id,material_bn', ['material_bn'=>array_column($items, 'bn')]);
        $bmList = array_column($bmList, null, 'material_bn');
        $bmExt = app::get('material')->model('basic_material_ext')->getList('bm_id,retail_price', ['bm_id'=>array_column($bmList, 'bm_id')]);
        $bmExt = array_column($bmExt, null, 'bm_id');
        $goodsItems = [];
        foreach($items as $v) {
            $ar = $arRows[$v['ar_id']];
            if($bmList[$v['bn']] && $bmExt[$bmList[$v['bn']]['bm_id']]) {
                $retail_price = $bmExt[$bmList[$v['bn']]['bm_id']]['retail_price'];
            } else {
                $retail_price = sprintf('%.2f', $v['money'] / $v['num']);
            }
            $goodsItems[$ar['monthly_item_id']][] = [
                'ar_item_id' => $v['item_id'],
                'ar_id' => $ar['ar_id'],
                'ar_bn' => $ar['ar_bn'],
                'type' => kernel::single('finance_ar')->get_name_by_type($ar['type']),
                'bn' => $v['bn'],
                'name' => $v['name'],
                'num' => $v['num'],
                'retail_price' => $retail_price,
                'money' => $v['money'],
                'actually_money' => $v['actually_money'],
            ];
        }
        return $goodsItems;
    }

    protected function _getBillItems($itemIds) {
        $rows = app::get('finance')->model('bill')->getList('*', ['monthly_item_id'=>$itemIds]);
        $items = [];
        foreach($rows as $v) {
            $items[$v['monthly_item_id']][] = [
                'bill_id' => $v['bill_id'],
                'bill_bn' => $v['bill_bn'],
                'order_bn' => $v['order_bn'],
                'member' => $v['member'],
                'status' => kernel::single('finance_bill')->get_name_by_status($v['status']),
                'verification_time' => $v['verification_time'] > 0 ? date('Y-m-d H:i:s', $v['verification_time']) : '',
                'trade_time' => $v['trade_time'] > 0 ? date('Y-m-d H:i:s', $v['trade_time']) : '',
                'create_time' => $v['create_time'] > 0 ? date('Y-m-d H:i:s', $v['create_time']) : '',
                'fee_type' => $v['fee_type'],
                'fee_item' => $v['fee_item'],
                'money' => $v['money'],
                'memo' => $v['memo'],
                'verification_status' => kernel::single('finance_bill')->get_name_by_verification_status($v['verification_status']),
            ];
        }
        return $items;
    }

    /**
     * 获取拆分结果明细
     * 
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getExpensesSplitList($filter, $offset = 0, $limit = 100)
    {
        $split_type = isset($filter['split_type']) ? $filter['split_type'] : 'split';
        unset($filter['split_type']);

        // 根据 split_type 选择不同的 model
        if ($split_type === 'unsplit') {
            $mdl = app::get('financebase')->model('expenses_unsplit');
            $filter['split_status'] = '2';  // 不拆仅呈现
        } else {
            $mdl = app::get('financebase')->model('expenses_split');
        }

        // 账单时间范围
        if (empty($filter['trade_time|between'][0]) || empty($filter['trade_time|between'][1])) {
            return false;
        }

        // 获取数据列表
        $countNum = $mdl->count($filter);
        if (empty($countNum)) {
            return array('lists' => array(), 'count' => 0);
        }

        $fields = '*';
        $dataList = $mdl->getList($fields, $filter, $offset, $limit);

        return array('lists' => $dataList, 'count' => $countNum);
    }
}
