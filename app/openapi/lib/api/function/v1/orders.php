<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_orders extends openapi_api_function_abstract implements openapi_api_function_interface
{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params, &$code, &$sub_msg)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $start_time            = strtotime($params['start_time']);
        $end_time              = strtotime($params['end_time']);
        $page_no               = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit                 = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $filter['shop_bn']     = $params['shop_bn'];
        $filter['pay_status']  = $params['pay_status'];
        $filter['status']      = $params['status'];
        $filter['ship_status'] = $params['ship_status'];
        $filter['close_item_req'] = $params['close_item_req'];
        
        //按订单号搜索
        if($params['order_bn']){
            $filter['order_bn'] = trim($params['order_bn']);
            $filter['order_bn'] = str_replace(array('"', "'"), '', $filter['order_bn']);
        }
    
        //游标ID
        if (isset($params['cursor_id']) && !is_null($params['cursor_id']) && $params['cursor_id'] != '') {
            $filter['cursor_id'] = (int)$params['cursor_id'];
        }
        //page
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        // 支持以createtime搜索订单
        if (isset($params['time_select']) && $params['time_select'] == 'createtime') {
            $time_select = 'createtime';
        }

        $orderList            = array();
        $original_orders_data = kernel::single('openapi_data_original_orders')->getList($filter, $start_time, $end_time,
            $offset, $limit, $time_select);
        
        // 获取当前API的隐私脱敏配置
        $is_data_mask = 0;
        if (isset($GLOBALS['openapi_current_setting'])) {
            $is_data_mask = $GLOBALS['openapi_current_setting']['is_data_mask'] ? $GLOBALS['openapi_current_setting']['is_data_mask'] : 0;
        }
        
        foreach ($original_orders_data['lists'] as $k => $order) {
            $shop_detail = $this->get_shop_detail($order['shop_id']);
            //订单信息
            $orderList[$k] = array(
                'shop_code' => $this->charFilter($order['shop_bn']),
                'shop_name' => $this->charFilter($order['shop_name']),
                'shop_type' => $shop_detail['shop_type'],
                
                'order_id' => $this->charFilter($order['order_id']),
                'order_bn' => $this->charFilter($order['order_bn']),
                'platform_order_bn' => $this->charFilter($order['platform_order_bn']),
                'relate_order_bn' => $this->charFilter($order['relate_order_bn']),
                
                'process_status' => $this->get_process_status($order['process_status']),//确认状态
                'status'         => $this->get_status($order['status']),//订单状态
                'pay_status'     => $this->get_pay_status($order['pay_status']),//付款状态
                'ship_status'    => $this->get_ship_status($order['ship_status']),//发货状态
                
                'shipping' => $this->charFilter($order['shipping']),//配送方式
                'is_cod'   => ($order['is_cod'] == 'false' ? '否' : '是'),//货到付款
                'pay_bn'   => $this->charFilter($order['pay_bn']),//支付编号
                'payment'  => $this->charFilter($order['payment']),//支付方式
                
                'ship_name'   => $is_data_mask ? '' : $this->charFilter($order['ship_name']),//收货人
                'ship_area'   => $is_data_mask ? '' : $this->format_ship_area($order['ship_area']),//收货地区
                'ship_addr'   => $is_data_mask ? '' : $this->charFilter($order['ship_addr']),//收货地址
                'ship_zip'    => $is_data_mask ? '' : $this->charFilter($order['ship_zip']),//收货邮编
                'ship_tel'    => $is_data_mask ? '' : $this->charFilter($order['ship_tel']),//收货人电话
                'ship_mobile' => $is_data_mask ? '' : $this->charFilter($order['ship_mobile']),//收货人手机
                'ship_email'  => $is_data_mask ? '' : $this->charFilter($order['ship_email']),//收货人邮箱
                
                'is_tax'      => ($order['is_tax'] == 'false' ? '否' : '是'),//是否开发票
                'cost_tax'    => $order['cost_tax'],//税金
                'tax_company' => $this->charFilter($order['tax_company']),//发票抬头
                
                'currency'     => $this->charFilter($order['currency']),//CNY
                'cost_item'    => $this->charFilter($order['cost_item']),//商品金额
                'cost_freight' => $order['cost_freight'],//配送费用
                'cost_protect' => $order['cost_protect'],//保价费用
                'cost_payment' => $order['cost_payment'],//支付费用
                'discount'     => $order['discount'],//折扣(负的代表优惠,正的代表多收)
                'pmt_goods'    => $order['pmt_goods'],//商品促销优惠
                'pmt_order'    => $order['pmt_order'],//订单促销优惠
                'total_amount' => $order['total_amount'],//订单总额
                'final_amount' => $order['final_amount'],//实际货币所需付款
                'payed'        => $order['payed'],//已付金额
                'source_status' => (string)$order['source_status'],//平台状态
                    
                'createway'  => $this->get_createway($order['createway']),
                'order_type' => $this->get_order_type($order['order_type']),
                'order_type_en' => $order['order_type'],

                'last_modified'    => date('Y-m-d H:i:s', $order['last_modified']), //订单最后更新时间
                'paytime'          => date('Y-m-d H:i:s', $order['paytime']), //付款时间
                'order_createtime' => $order['createtime'] ? date('Y-m-d H:i:s', $order['createtime']) : '', //下单时间
                'download_time'    => date('Y-m-d H:i:s', $order['download_time']), //订单下载时间
                'member_id'        => $order['member_id'], //会员ID
                'member_name'      => $this->charFilter($order['member_name']), //会员名
                'buyer_open_uid'   => $this->charFilter($order['buyer_open_uid']), //买家open_uid
                'pmts'             => $order['pmts'] ? $order['pmts'] : array(), //订单优惠方案
                'is_modify'        => $order['is_modify'], //订单商品是否编辑
                'refund'           => $order['refund'] ? $order['refund'] : array(),
                'end_time'         => date('Y-m-d H:i:s', $order['end_time']),
            );

            //订单明细
            foreach ($order['items'] as $itemKey => $item) {
                $item_id = $item['item_id'];

                // 处理 addon 字段中的 spen_info 数据
                $spen_info = '';
                if (!empty($item['addon'])) {
                    // 使用 format_order_items_addon 函数格式化 addon 数据
                    $spen_info = ome_order_func::format_order_items_addon($item['addon']);
                }

                $itemInfo = array(
                    'obj_id'       => $item['obj_id'],
                    'bn'           => $this->charFilter($item['bn']),
                    'barcode'      => $this->charFilter($item['barcode']),
                    'name'         => $this->charFilter($item['name']),
                    'nums'         => $item['nums'],
                    'sendnum'      => $item['sendnum'],
                    'item_type'    => $this->charFilter($item['item_type']),
                    'weight'       => intval($item['weight']), //重量
                    'cost'         => ($item['cost'] ? $item['cost'] : 0), //成本价
                    'price'        => $item['price'], //购买单价
                    'pmt_price'    => $item['pmt_price'], //商品优惠金额
                    'sale_price'   => $item['sale_price'], //销售单价(销售总价)
                    'sales_amount' => $item['amount'], //商品小计 = 单价 x 数量
                    'divide_order_fee' => $item['divide_order_fee'], //分摊之后的实付金额
                    'part_mjz_discount' => $item['part_mjz_discount'], //优惠分摊
                    'shop_goods_id' => $item['shop_goods_id'], //平台商品ID
                    'shop_product_id' => $item['shop_product_id'], //平台SkuID
                    'spen_info'    => $this->charFilter($spen_info), //特殊信息
                );
                $itemInfo = array_map(function($i_v) {return is_null($i_v) ? '' : $i_v;}, $itemInfo);
                $orderList[$k]['order_items'][$item_id] = $itemInfo;
            }

            //订单明细
            foreach ($order['order_objects'] as $itemKey => $item) {
                $obj_id = $item['obj_id'];
    
                $objectInfo = array(
                    'bn'                => $this->charFilter($item['bn']),
                    'name'              => $this->charFilter($item['name']),
                    'amount'            => ($item['cost'] ? $item['amount'] : 0), //成本价
                    'price'             => $item['price'], //购买单价
                    'quantity'          => $item['quantity'],
                    'pmt_price'         => $item['pmt_price'], //商品优惠金额
                    'sale_price'        => $item['sale_price'], //销售单价(销售总价)
                    'oid'               => $item['oid'],
                    'divide_order_fee'  => $item['divide_order_fee'],
                    'part_mjz_discount' => $item['part_mjz_discount'],
                    'delete'            => $item['delete'],
                    'obj_type'          => $item['obj_type'],
                    'obj_id'            => $item['obj_id'],
                    'store_code'        => $this->charFilter($item['store_code']),
                    'shop_goods_id'     => $item['shop_goods_id'],//平台商品ID
                );
    
                $objectInfo = array_map(function($i_v) {return is_null($i_v) ? '' : $i_v;}, $objectInfo);
                $orderList[$k]['order_objects'][] = $objectInfo;
            }
            // 如果 $v 是 null，则返回空字符串；否则返回原始值
            $orderList[$k] = array_map(function($v) {return is_null($v) ? '' : $v;}, $orderList[$k]);
        }
        unset($original_orders_data['lists']);

        $original_orders_data['lists'] = $orderList;
        return $original_orders_data;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params, &$code, &$sub_msg)
    {

    }

    /**
     * 获取_order_type
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function get_order_type($val)
    {
        $ordersMdl = app::get('ome')->model('orders');
        $types = $ordersMdl->schema['columns']['order_type']['type'];
        return $this->charFilter($types[$val]);
    }

    /**
     * 获取_process_status
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function get_process_status($val)
    {
        $types = array(
            'unconfirmed'   => '未确认',
            'confirmed'     => '已确认',
            'splitting'     => '部分拆分',
            'splited'       => '已拆分完',
            'cancel'        => '取消',
            'remain_cancel' => '余单撤销',
            'is_retrial'    => '复审订单',
            'is_declare'    => '跨境申报订单',
        );

        return $this->charFilter($types[$val]);
    }

    /**
     * 获取_status
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function get_status($val)
    {
        $types = array(
            'active' => '活动订单',
            'dead'   => '已作废',
            'finish' => '已完成',
        );

        return $this->charFilter($types[$val]);
    }

    /**
     * 获取_pay_status
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function get_pay_status($val)
    {
        $types = array(
            0 => '未支付',
            1 => '已支付',
            2 => '处理中',
            3 => '部分付款',
            4 => '部分退款',
            5 => '全额退款',
            6 => '退款申请中',
            7 => '退款中',
            8 => '支付中',
        );

        return $this->charFilter($types[$val]);
    }

    /**
     * 获取_ship_status
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function get_ship_status($val)
    {
        $types = array(
            0 => '未发货',
            1 => '已发货',
            2 => '部分发货',
            3 => '部分退货',
            4 => '已退货',
        );

        return $this->charFilter($types[$val]);
    }

    /**
     * 获取_createway
     * @param mixed $val val
     * @return mixed 返回结果
     */
    public function get_createway($val)
    {
        $types = array(
            'matrix' => '平台获取',
            'local'  => '手工新建',
            'import' => '批量导入',
            'after'  => '售后自建',
        );

        return $this->charFilter($types[$val]);
    }

    /**
     * 格式化地区
     * @param unknown $val
     */
    public function format_ship_area($val)
    {
        $areaArr   = explode(':', $val);
        $ship_area = str_replace('/', '-', $areaArr[1]);

        return $this->charFilter($ship_area);
    }

    public function get_shop_detail($shop_id)
    {
        static $shops;

        if ($shops[$shop_id]) {
            return $shops[$shop_id];
        }

        $shop = app::get('ome')->model('shop')->getList('*', array('shop_id' => $shop_id));

        $shops[$shop_id] = $shop[0];

        return $shops[$shop_id];

    }
    public function decrypt($params,&$code,&$sub_msg){
        $sub_msg = '该接口不再支持';
        return false;
        if(!$params['order_bn']){
            $sub_msg = '订单编号未填写';
            return false;
        }
        if(!$params['shop_bn']){
            $sub_msg = '店铺编号为空';
            return false;
        }
        $shop = app::get('ome')->model('shop')->db_dump(['shop_bn'=>trim($params['shop_bn'])], 'shop_id');
        if(empty($shop)) {
            $sub_msg = '没有该店铺';
            return false;
        }
        $data = app::get('ome')->model('orders')->db_dump(['shop_id'=>$shop['shop_id'], 'order_bn'=>trim($params['order_bn'])]);
        if(empty($data)) {
            $sub_msg = '订单不存在';
            return false;
        }
        $decrypt_data = kernel::single('ome_security_router',$data['shop_type'])->decrypt(array (
            'ship_tel'    => $data['ship_tel'],
            'ship_mobile' => $data['ship_mobile'],
            'ship_addr'   => $data['ship_addr'],
            'shop_id'     => $data['shop_id'],
            'order_bn'    => $data['order_bn'],
            'ship_name' => $data['ship_name'],
        ), 'order');
        return ['rsp'=>'succ', 'data'=>['name'=>$decrypt_data['ship_name'], 'tel'=>$decrypt_data['ship_tel'], 'mobile'=>$decrypt_data['ship_mobile'], 'detailAddress'=>$decrypt_data['ship_addr']]];
    }

    /**
     * 获取CouponList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getCouponList($params, &$code, &$sub_msg)
    {
        $start_time = strtotime($params['start_time']);
        $end_time   = strtotime($params['end_time']);
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit      = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $filter     = array();
        //按订单号搜索
        if($params['order_bn']){
            $filter['order_bn'] = trim($params['order_bn']);
            $filter['order_bn'] = str_replace(array('"', "'"), '', $filter['order_bn']);
        }
        $filter['create_time|between'] = array($start_time, $end_time);
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        //get list
        $dataList = kernel::single('openapi_data_original_orders')->getCouponList($filter, $offset, $limit);
        
        return $dataList;
    }

    /**
     * 获取PmtList
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getPmtList($params, &$code, &$sub_msg)
    {
        $start_time = date('Y-m-d H:i:s', strtotime($params['start_time']));
        $end_time   = date('Y-m-d H:i:s', strtotime($params['end_time']));
        $page_no    = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit      = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $filter     = array();
        //按订单号搜索
        if($params['order_bn']){
            $filter['order_bn'] = trim($params['order_bn']);
            $filter['order_bn'] = str_replace(array('"', "'"), '', $filter['order_bn']);
        }
        $filter['up_time|betweenstr'] = array($start_time, $end_time);
        if ($page_no == 1) {
            $offset = 0;
        } else {
            $offset = ($page_no - 1) * $limit;
        }
        
        //get list
        $dataList = kernel::single('openapi_data_original_orders')->getPmtList($filter, $offset, $limit);
        
        return $dataList;
    }
}
