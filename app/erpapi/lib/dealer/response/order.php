<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单接口
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_order extends erpapi_dealer_response_abstract
{
    /**
     * ERP订单
     * 
     * @var string
     * */

    public $_tgOrder = array();

    /**
     * 订单接收格式
     * 
     * @var string
     * */
    public $_ordersdf = array();
    
    /**
     * 接收的原平台订单数据
     * 
     * @var string
     * */
    public $_originalSdf = array();

    /**
     * 订单标准结构
     * 
     * @var string
     * */
    public $_newOrder = array();

    /**
     * 可接收未付款订单
     * 
     * @var string
     * */
    protected $_accept_unpayed_order = false;

    /**
     * 更新订单是否接收死单
     * 
     * @var string
     * */
    protected $_update_accept_dead_order = true;
    
    /**
     * 平台订单状态
     * 
     * @var string
     * */
    protected $_sourceStatus = array();

    /**
     * 订单obj明细唯一标识
     * 
     * @var string
     * */
    public $object_comp_key = 'bn-shop_goods_id-obj_type';

    /**
     * 订单item唯一标识
     * @todo：如果使用shop_product_id字段比较，当订单掉下来是失败订单修复后shop_product_id='0'会导致有差异；
     * 
     * @var string
     * */
    public $item_comp_key = 'bn-shop_goods_id-item_type'; //bn-shop_product_id-item_type

    /**
     * 防并发key
     * @todo：erpapi_router_response类中调用此方法;
     * 
     * @return string
     * */
    public function concurrentKey($sdf)
    {
        $this->__lastmodify = kernel::single('ome_func')->date2time($sdf['lastmodify']);
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        
        if ($sdf['method'] && $sdf['node_id'] && $sdf['order_bn']) {
            $key = $sdf['method'] . $sdf['node_id'] . $sdf['order_bn'];
        }
        
        return $key ? md5($key) : false;
    }

    public function add($sdf)
    {
        //前置处理平台数据（例如：去除抖音平台订单A字母）
        $sdf = $this->preFormatData($sdf);
        
        //接收的原平台订单数据
        $this->_originalSdf = $sdf;
        $this->_ordersdf = $sdf;
        $this->_tgOrder = $this->_newOrder = array();
        
        $this->__apilog['result']['data'] = array('order_bn' => $this->_ordersdf['order_bn']);
        $this->__apilog['original_bn']    = $this->_ordersdf['order_bn'];
        $this->__apilog['title'] = '创建经销订单[' . $this->_ordersdf['order_bn'] . ']';
        
        //数据格式化
        $this->_analysis();
        
        //检查是否接收订单
        $accept = $this->_canAccept();
        if ($accept === false) {
            return array();
        }
        
        //订单明细格式化
        $this->formatItemsSdf();
        
        //订单操作：创建 or 更新
        $this->_operationSel();
        
        //operation
        switch ($this->_operationSel) {
            case 'create':
                $this->__apilog['title'] = '创建经销订单[' . $this->_ordersdf['order_bn'] . ']';
                
                //create
                $rs = $this->_createOrder();
                if ($rs === false) {
                    return array();
                }
                
                break;
            case 'update':
                $this->__apilog['title'] = '更新经销订单[' . $this->_ordersdf['order_bn'] . ']';
                
                //生产更换sku相关参数
                $this->formatItemsUpdateSkuSdf();
                
                //是否更新订单
                $rs = $this->_updateOrder();
                if ($rs === false) {
                    return array();
                }
                
                //check
                if (!$this->_newOrder && !$this->__apilog['result']['msg']) {
                    $this->__apilog['result']['msg'] = '订单无结构变化，无需更新';
                }
                
                //data
                if ($this->_newOrder) {
                    $this->_newOrder['plat_order_id'] = $this->_tgOrder['plat_order_id'];
                    $this->_newOrder['plat_order_bn'] = $this->_tgOrder['plat_order_bn'];
                    if($this->_ordersdf['order_bool_type']){
                        $this->_newOrder['order_bool_type'] = $this->_ordersdf['order_bool_type'];
                        $this->_newOrder['change_sku'] = $this->_ordersdf['change_sku'];
                        $this->_newOrder['old_sku'] = $this->_ordersdf['old_sku'];
                    }
                }
                
                break;
            case 'close':
                $this->__apilog['title'] = '取消经销订单['.$this->_ordersdf['order_bn'].']';
                
                $this->_newOrder['plat_order_id'] = $this->_tgOrder['plat_order_id'];
                $this->_newOrder['plat_order_bn'] = $this->_tgOrder['plat_order_bn'];
                $this->_newOrder['flag'] = 'close';
                
                $rs = $this->_closeOrder();
                if ($rs === false){
                    return array();
                }
                
                break;
            default:
                $this->__apilog['title'] = '更新经销订单[' . $this->_ordersdf['order_bn'] . ']';
                $this->__apilog['result']['msg'] = '更新时间没变，无需更新';
                
                return array();
                
                break;
        }
        
        //接收的原平台订单数据(后面会保存到扩展表里)
        $this->_newOrder['originalSdf'] = $this->_originalSdf;
        
        return $this->_newOrder;
    }

    /**
     * 订单操作：创建 or 更新
     * 
     * @return void
     * @author
     * */
    protected function _operationSel()
    {
        $jxOrderMdl = app::get('dealer')->model('platform_orders');
        $jxOrderLib = kernel::single('dealer_platform_orders');
        
        $lastmodify = kernel::single('ome_func')->date2time($this->_ordersdf['lastmodify']);
        
        //获取订单信息
        $filter = array('plat_order_bn'=>$this->_ordersdf['order_bn'], 'shop_id'=>$this->__channelObj->channel['shop_id']);
        $this->_tgOrder = $jxOrderLib->getOrderDetail($filter);
        
        //check
        if (empty($this->_tgOrder)) {
            $this->_operationSel = 'create';
        } elseif ($lastmodify > $this->_tgOrder['outer_lastmodify']) {
            $upData = array('outer_lastmodify'=>$lastmodify);
            
            //平台状态
            if ($this->_ordersdf['source_status']) {
                $upData['source_status'] = $this->_ordersdf['source_status'];
            }
            
            //update
            $jxOrderMdl->update($upData, array('plat_order_id'=>$this->_tgOrder['plat_order_id'], 'outer_lastmodify|lthan'=>$lastmodify));
            $affect_row = $jxOrderMdl->db->affect_row();
            if ($affect_row > 0) {
                $this->_operationSel = 'update';
            }
        }
    }
    
    //平台自发明细is_sh_ship=true
    protected function _setPlatformDelivery()
    {
        foreach($this->_ordersdf['order_objects'] as $key=>$object)
        {
            foreach($object['order_items'] as $k=>$item)
            {
                $this->_ordersdf['order_objects'][$key]['order_items'][$k]['is_sh_ship'] = 'true';
            }
            
            $this->_ordersdf['order_objects'][$key]['is_sh_ship'] = 'true';
        }
        
        $this->_ordersdf['order_type'] = 'platform';
    }
    
    /**
     * 创建订单
     * 
     * @return boolean
     * */
    protected function _createOrder()
    {
        //检查是否接收
        if (false === $this->_canCreate()) {
            return false;
        }
        
        //组件集合
        $broker = kernel::single('erpapi_dealer_response_components_order_broker');
        $broker->clearComponents();
        
        //注册组件
        foreach ($this->get_convert_components() as $component)
        {
            $broker->registerComponent($component);
        }
        
        $broker->setPlatform($this)->convert();
        
        if($this->_ordersdf['t_type'] == 'fenxiao'){
            //分销标示
            $this->_ordersdf['order_bool_type'] = 0 | ome_order_bool_type::__DISTRIBUTION_CODE;
            $this->_newOrder['order_bool_type'] = $this->_ordersdf['order_bool_type'];
        }
        
        return true;
    }

    /**
     * [创建]订单组件
     * 
     * @return array
     * */
    protected function get_convert_components()
    {
        //删除项：'custommemo', 'markmemo', 'marktype', 'tax','booltype', 'consigner'
        $components = array('master', 'items', 'shipping', 'consignee', 'member');
        
        return $components;
    }
    
    /**
     * [更新]订单组件
     * 
     * @return void
     * @author
     * */
    protected function get_update_components()
    {
        //删除项：'custommemo', 'markmemo', 'marktype', 'tax','booltype', 'consigner', 'member'
        $components = array('master', 'items', 'shipping', 'consignee');
        
        return $components;
    }
    
    /**
     * 更新订单
     * 
     * @return void
     * @author
     * */
    protected function _updateOrder()
    {
        if (false === $this->_canUpdate()) {
            return false;
        }
        
        // 组件集合
        $broker = kernel::single('erpapi_dealer_response_components_order_broker');
        $broker->clearComponents();
        
        //注册组件
        foreach (array_unique($this->get_update_components()) as $component) {
            $broker->registerComponent($component);
        }
        
        $broker->setPlatform($this)->update();
        
        return true;
    }
    
    /**
     * 数据解析
     * 
     * @return void
     * @author
     * */
    protected function _analysis()
    {
        $ordFunLib = kernel::single('ome_order_func');
        
        //平台状态
        $source_status = $this->_sourceStatus[$this->_ordersdf['source_status']] ?: $this->_ordersdf['source_status'];
        $this->_ordersdf['source_status'] = kernel::single('ome_order_func')->get_source_status($source_status);
        
        if(in_array($this->_ordersdf['status'], ['close', 'dead'])) {
            $this->_ordersdf['source_status'] = 'TRADE_CLOSED';
        }elseif(in_array($this->_ordersdf['status'], ['finish'])) {
            $this->_ordersdf['source_status'] = 'TRADE_FINISHED';
        }
        
        // 配送信息
        if (is_string($this->_ordersdf['shipping'])) {
            $this->_ordersdf['shipping'] = json_decode($this->_ordersdf['shipping'], true);
        }
        
        // 支付信息
        if (is_string($this->_ordersdf['payinfo'])) {
            $this->_ordersdf['payinfo'] = json_decode($this->_ordersdf['payinfo'], true);
        }
        
        // 收货人信息
        if (is_string($this->_ordersdf['consignee'])) {
            $this->_ordersdf['consignee'] = json_decode($this->_ordersdf['consignee'], true);
            
            //替换表情符
            if($this->_ordersdf['consignee']['name']){
                $this->_ordersdf['consignee']['name'] = $ordFunLib->filterEmoji($this->_ordersdf['consignee']['name']);
            }
        }
        
        // 发货人信息
        if (is_string($this->_ordersdf['consigner'])) {
            $this->_ordersdf['consigner'] = json_decode($this->_ordersdf['consigner'], true);
        }
        
        // 代销人信息
        if (is_string($this->_ordersdf['selling_agent'])) {
            $this->_ordersdf['selling_agent'] = json_decode($this->_ordersdf['selling_agent'], true);
        }
        
        // 菜鸟直销订单
        if (is_string($this->_ordersdf['cn_info'])) {
            $this->_ordersdf['cn_info'] = json_decode($this->_ordersdf['cn_info'], true);
        }
        
        // 买家会员信息
        if (is_string($this->_ordersdf['member_info'])) {
            $this->_ordersdf['member_info'] = json_decode($this->_ordersdf['member_info'], true);
            
            //替换表情符
            if ($this->_ordersdf['member_info']['uname']) {
                $this->_ordersdf['member_info']['uname'] = $ordFunLib->filterEmoji($this->_ordersdf['member_info']['uname']);
            }
        }
        
        // 订单优惠方案
        if (is_string($this->_ordersdf['pmt_detail'])) {
            $this->_ordersdf['pmt_detail'] = json_decode($this->_ordersdf['pmt_detail'], true);
        }
        
        // 订单商品
        if (is_string($this->_ordersdf['order_objects'])) {
            $this->_ordersdf['order_objects'] = json_decode($this->_ordersdf['order_objects'], true);
        }
        
        // 支付单(兼容老版本)
        if (is_string($this->_ordersdf['payment_detail'])) {
            $this->_ordersdf['payment_detail'] = json_decode($this->_ordersdf['payment_detail'], true);
        }
        
        if (is_string($this->_ordersdf['payments'])) {
            $this->_ordersdf['payments'] = $this->_ordersdf['payments'] ? json_decode($this->_ordersdf['payments'], true) : array();
        }
        
        // 非可逆索引
        if (is_string($this->_ordersdf['index_field'])) {
            $this->_ordersdf['index_field'] = $this->_ordersdf['index_field'] ? json_decode($this->_ordersdf['index_field'], true) : array();
        }
        
        if (is_string($this->_ordersdf['other_list'])) {
            $this->_ordersdf['other_list'] = json_decode($this->_ordersdf['other_list'], true);
        }
        
        // 平台自发货
        foreach ($this->_ordersdf['other_list'] as $value) {
            if ($value['type'] == 'store' && $value['is_store'] == '1') {
                $this->_ordersdf['order_type'] = 'platform';
            }
        }
        
        //经销类型,平台自发明细
        if($this->__channelObj->channel['delivery_mode'] == 'jingxiao') {
            $this->_setPlatformDelivery();
        }
        
        // 去首尾空格
        self::trim($this->_ordersdf);
        
        // 如果是担保交易,订单支付状态修复成已支付
        if ($this->_ordersdf['pay_status'] == '2') {
            $this->_ordersdf['pay_status'] = '1';
        }
        
        // 如果是货到付款的，重置支付金额，支付单
        if ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_ordersdf['payed']          = '0';
            $this->_ordersdf['payments']       = array();
            $this->_ordersdf['payment_detail'] = array();
        }
        
        $this->_ordersdf['pmt_goods'] = abs((float)$this->_ordersdf['pmt_goods']);
        $this->_ordersdf['pmt_order'] = abs((float)$this->_ordersdf['pmt_order']);
        
        if ($this->_ordersdf['pay_status'] == '5') {
            $this->_ordersdf['payed'] = 0;
        }
        
        if (is_string($this->_ordersdf['service_order_objects'])) {
            $this->_ordersdf['service_order_objects'] = json_decode($this->_ordersdf['service_order_objects'], true);
        }
        
        $title=array();
        
        // 由于OBJ货号太长，导致更新的时候明细不一致
        foreach ((array) $this->_ordersdf['order_objects'] as $objkey => $object)
        {
            if ($object['bn'] && $object['bn'][40]) {
                $this->_ordersdf['order_objects'][$objkey]['bn'] = substr($object['bn'], 0, 40);
            }
            
            $title[] = array('name'=>$object['name'],'num'=>$object['quantity']);
            
            foreach ($object['order_items'] as $itemkey => $item)
            {
                if (is_string($item['extend_item_list'])) {
                    $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['extend_item_list'] = json_decode($item['extend_item_list'], true);
                }
            }
        }
        
        if($title){
            $this->_ordersdf['title'] = json_encode($title);
        }
        
        if ($this->_ordersdf['end_time']) {
            $this->_ordersdf['end_time'] = strtotime($this->_ordersdf['end_time']);
        }
        
        if (isset($this->_ordersdf['o2o_info']) && is_string($this->_ordersdf['o2o_info'])) {
            $this->_ordersdf['o2o_info'] = json_decode($this->_ordersdf['o2o_info'], true);
        }
        
        //扩展信息
        if ($this->_ordersdf['extend_field'] && is_string($this->_ordersdf['extend_field'])) {
            $this->_ordersdf['extend_field'] = json_decode($this->_ordersdf['extend_field'], true);
        }
        
        //优惠明细平台原始字段
        if ($this->_ordersdf['coupon_field'] && is_string($this->_ordersdf['coupon_field'])) {
            $this->_ordersdf['coupon_field'] = json_decode($this->_ordersdf['coupon_field'], true);
        }
        
        // 纸票转电票
        if ('on' == app::get('ome')->getConf('ome.invoice.p2e') && $this->_ordersdf['is_tax'] == 'true' && $this->_ordersdf['invoice_kind'] != '1' && !$this->_ordersdf['value_added_tax_invoice']) {
            $this->_ordersdf['invoice_kind'] = '1';
        }
        
        // 强制开票
        if ($this->_ordersdf['is_tax'] != 'true' 
            && 'on' == app::get('ome')->getConf('ome.invoice.force')) {
            $this->_ordersdf['is_tax'] = 'true';
        }
        
        // 强制开电票
        if ($this->_ordersdf['is_tax'] == 'true'
            && !is_numeric($this->_ordersdf['invoice_kind']) ) {
            $invoiceMode = app::get('ome')->getConf('ome.invoice.mode');
            
            // 开电票
            if ('electron' == $invoiceMode){
                $this->_ordersdf['invoice_kind'] = '1';
            }
            
            // 开专票
            if ('special' == $invoiceMode){
                $this->_ordersdf['invoice_kind'] = '3';
                $this->_ordersdf['value_added_tax_invoice'] = 'true';
            }
        }

        // 默认开个人抬头
        if ($this->_ordersdf['is_tax'] == 'true' && !$this->_ordersdf['tax_title']) {
            $this->_ordersdf['tax_title'] = '个人';
        }
        
        // 收货地址处理
        $this->_ordersdf['consignee']['addr'] = str_replace(array("\r\n","\r","\n","'","\"","\\"), '', htmlspecialchars($this->_ordersdf['consignee']['addr']));
    }

    /**
     * 是否接收订单
     * 
     * @return void
     * @author
     * */
    protected function _canAccept()
    {
        if (empty($this->_ordersdf) || empty($this->_ordersdf['order_bn']) || empty($this->_ordersdf['order_objects'])) {
            $this->__apilog['result']['msg'] = '接收数据不完整';
            return false;
        }
        
        $wuliubao = app::get('ome')->getConf('ome.delivery.wuliubao');
        if ($wuliubao == 'false' && strtolower($this->_ordersdf['is_force_wlb']) == 'true') {
            $this->__apilog['result']['msg'] = '物流宝发货订单不接收';
            return false;
        }
        
        if ($this->_ordersdf['order_from'] == 'omeapiplugin') {
            $this->__apilog['result']['msg'] = '来自omeapiplugin的订单不接收';
            return false;
        }
        
        return true;
    }
    
    /**
     * 创建接收
     * 
     * @return void
     * @author
     * */
    protected function _canCreate()
    {
        if($this->__channelObj->channel['config']['order_receive'] === 'no'){
            $this->__apilog['result']['msg'] = '未开启收单配置';
            return false;
        }
        
        //平台自发货
        if ($this->_ordersdf['order_type'] == 'platform') {
            $shShip = app::get('ome')->getConf('ome.platform.order.consign');
            if (!$shShip || !in_array($shShip, array ('true', 'signed', 'payed'))) {
                $this->__apilog['result']['msg'] = '未开启接收平台发货订单选项';
                return false;
            }
            
            if ($shShip == 'true' && $this->_ordersdf['ship_status'] != '1') {
                $this->__apilog['result']['msg'] = '只接收已发货平台发货订单(ship_status='.$this->_ordersdf['ship_status'].')';
                return false;
            }
            
            if ($shShip == 'signed' && $this->_ordersdf['status'] != 'finish') {
                $this->__apilog['result']['msg'] = '只接收已签收平台发货订单(status='.$this->_ordersdf['status'].')';
                return false;
            }

            if ($shShip == 'payed' && $this->_ordersdf['pay_status'] != '1') {
                $this->__apilog['result']['msg'] = '只接收已支付平台发货订单(pay_status='.$this->_ordersdf['pay_status'].')';
                return false;
            }
        } else {
            if(!in_array($this->_ordersdf['order_type'], array('offline','eticket')) && $this->_ordersdf['ship_status'] != '0') {
                $this->__apilog['result']['msg'] = '已发货的经销订单不接收';
                return false;
            }
            
            // 只接收活动订单
            if ($this->_ordersdf['status'] != 'active') {
                $this->__apilog['result']['msg'] = $this->_ordersdf['status'] == 'close' ? '取消经销订单不接收' : '完成经销订单不接收';
                return false;
            }
        }
        
        if ($this->_accept_unpayed_order !== true && $this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->__apilog['result']['msg'] = '未支付的经销订单不接收';
            return false;
        }
        
        //检查订单退款
        if (in_array($this->_ordersdf['pay_status'], array('4', '5', '7', '8'))) {
            $this->__apilog['result']['msg'] = '已退款的经销订单不接收';
            return false;
        }
        
        return true;
    }

    /**
     * 更新接收
     * 
     * @return boolean
     * */
    protected function _canUpdate()
    {
        if ($this->_ordersdf['order_type'] == 'platform') {
            //--
        }else{
            if ($this->_ordersdf['status'] == 'finish' && ($this->_ordersdf['end_time'] == '' || $this->_tgOrder['end_time'] > 0)) {
                $this->__apilog['result']['msg'] = '完成的经销订单不接收';
                return false;
            }
            
            if ($this->_update_accept_dead_order === false && $this->_ordersdf['status'] == 'dead') {
                $this->__apilog['result']['msg'] = '取消经销订单不支持接收';
                return false;
            }
        }
        
        if (!in_array($this->_ordersdf['status'], array('active', 'finish', 'close', 'dead'))) {
            $this->__apilog['result']['msg'] = '不明经销订单状态不接收';
            return false;
        }
        
        if ($this->_ordersdf['status'] == 'close') {
            $this->__apilog['result']['msg'] = '关闭经销订单不接收';
            return false;
        }
        
        if ($this->_tgOrder['status'] == 'dead') {
            $this->__apilog['result']['msg'] = '经销订单已经取消，不做更新';
            return false;
        }
        
        if (in_array($this->_tgOrder['ship_status'], array('1', '2')) || $this->_tgOrder['status'] == 'finish') {
            if ($this->_ordersdf['end_time'] <= 0 || $this->_tgOrder['end_time'] > 0) {
                $this->__apilog['result']['msg'] = 'ERP已发货订单，不做更新';
                return false;
            }
        }
        
        if (in_array($this->_tgOrder['ship_status'], array('3', '4'))) {
            $this->__apilog['result']['msg'] = 'ERP已退货订单，不做更新';
            return false;
        }
        
        return true;
    }
    
    /**
     * 更新订单状态
     * 
     * @param $sdf
     * @return array|false
     */
    public function status_update($sdf)
    {
        //前置处理平台数据（例如：去除抖音平台订单A字母）
        $sdf = $this->preFormatData($sdf);
        
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        $this->__apilog['title'] = '修改经销订单状态[' . $sdf['order_bn'] . ']';
        
        // 只接收作废订单
        if ($sdf['status'] == '') {
            $this->__apilog['result']['msg'] = '经销订单状态不能为空';
            return false;
        }
        
        if ($this->__channelObj->get_ver() > '1') {
            if ($sdf['status'] != 'dead') {
                $this->__apilog['result']['msg'] = '不接收除作废以外的其他状态';
                return false;
            }
        }
        
        //获取订单信息
        $jxOrderLib = kernel::single('dealer_platform_orders');
        $filter = array('plat_order_bn'=>$sdf['order_bn'], 'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $jxOrderLib->getOrderMainInfo($filter);
        if (empty($tgOrder)) {
            $this->__apilog['result']['msg'] = '经销订单不存在';
            return false;
        }
        
        if (in_array($tgOrder['pay_status'], array('1', '2', '3', '4'))) {
            $this->__apilog['result']['msg'] = '经销订单已经支付，不更新';
            return false;
        }
        
        if ($tgOrder['ship_status'] != 0) {
            $this->__apilog['result']['msg'] = '经销订单未发货，不更新';
            return false;
        }
        
        if ($tgOrder['status'] != 'active' || $tgOrder['process_status'] == 'cancel') {
            $this->__apilog['result']['msg'] = '经销订单已取消，不更新';
            return false;
        }
        
        $updateOrder = array();
        if (!$tgOrder['op_id']) {
            $userModel = app::get('desktop')->model('users');
            $userinfo = $userModel->getList('user_id', array('super' => '1'), 0, 1, 'user_id asc');
            $updateOrder['op_id'] = $userinfo[0]['op_id'];
        }
        
        $updateOrder['status'] = $sdf['status'];
        if ($updateOrder) {
            $updateOrder['plat_order_id'] = $tgOrder['plat_order_id'];
        }
        
        return $updateOrder;
    }
    
    /**
     * 订单明细格式化(兼容订单打下来的明细结构)
     * 
     * @param void
     * @return void
     */
    protected function formatItemsSdf()
    {
        //非新的订单明细格式，老的两层结构做格式化
        if (!isset($this->_ordersdf['new_orderobj'])) {
            $adjunctObj = array(); //ec附件
            $giftObj    = array(); //ec商品促销指定商品赠品
            
            foreach ($this->_ordersdf['order_objects'] as $k => $object)
            {
                //如果1个item对1个obj认为是普通商品,item合并到obj层,item矩阵打过来的真实信息
                if (count($object['order_items']) == 1) {
                    $tmp_obj_items = $object['order_items'][0];
                    
                    unset($object['order_items']);
                    
                    $tmp_obj                 = $object;
                    $tmp['order_object'][$k] = array_merge($tmp_obj, $tmp_obj_items);
                } else {
                    //如果是促销组合类，就直接以obj层为准
                    $adjunct_amount = 0;
                    foreach ($object['order_items'] as $item)
                    {
                        $adjunct_flag = false;
                        $gift_flag    = false;
                        
                        if ($item['item_type'] == 'adjunct') {
                            $adjunct_flag = true;
                        }
                        
                        if ($item['item_type'] == 'gift') {
                            $gift_flag = true;
                        }
                        
                        if ($item['status'] != 'close') {
                            if ($adjunct_flag) {
                                //配件对应amount pmt_price要从object里去除 另组一个object
                                $adjunct_amount += $item['amount'];
                                $adjunctObj[] = $item;
                            } elseif ($gift_flag) {
                                $giftObj[] = $item;
                            } else {
                                $object_pmt += (float) $item['pmt_price'];
                            }
                        } else {
                            $is_delete = true;
                        }
                    }
                    
                    $object_pmt += $object['pmt_price'];
                    if ($adjunct_amount > 0) {
                        $object['amount'] = $object['amount'] - $adjunct_amount;
                    }
                    
                    unset($object['order_items']);
                    
                    $tmp_obj           = $object;
                    $tmp_obj['status'] = $is_delete ? 'close' : 'active';
                    
                    $tmp['order_object'][$k] = array_merge($tmp_obj, array('pmt_price' => $object_pmt));
                }
            }
            
            //赋值重组后的数据
            $this->_ordersdf['order_objects'] = array_merge($tmp['order_object'], $adjunctObj, $giftObj);
        }
    }

    /**
     * 生产更换sku相关参数
     */
    protected function formatItemsUpdateSkuSdf()
    {
        //非新的订单明细格式，老的两层结构做格式化
        $new_bns = [];
        foreach ($this->_ordersdf['order_objects'] as $k => $object)
        {
            if(isset($object['bn'])){
                if(!$new_bns[$object['bn']]){
                    $new_bns[$object['bn']] = [];
                }
                
                $new_bns[$object['bn']][] = $object['bn'];
            }
        }
        
        $order_info = $this->_tgOrder;
        if ($order_info) {
            $order_objects = $order_info['order_objects'];
            $old_bns = array_column($order_objects, 'bn');
            
            $old_nk_nums = [];
            foreach ($old_bns as $v)
            {
                if (!$old_nk_nums[$v]) {
                    $old_nk_nums[$v] = [];
                }
                
                $old_nk_nums[$v][] = $v;
            }
            
            $change_sku = '';
            $old_sku = '';
            $is_change = false;
            foreach ($new_bns as $nk => $nv) {
                $is_hava = false;
                foreach ($old_nk_nums as $k => $v) {
                    if ($nk == $k) {
                        $is_hava = true;
                        if (count($nv) != count($v)) {
                            $is_change = true;
                            $change_sku = $nv[0];
                            break;
                        }
                    }
                }
                
                if(!$is_hava){
                    $is_change = true;
                    $change_sku = $nv[0];
                    break;
                }
            }
            
            //check
            if(!$is_change){
                return;
            }
            
            foreach ($old_nk_nums as $nk => $nv)
            {
                $is_hava = false;
                foreach ($new_bns as $k => $v) {
                    if ($nk == $k) {
                        $is_hava = true;
                        if (count($nv) != count($v)) {
                            $old_sku = $nv[0];
                            break;
                        }
                    }
                }
                
                if(!$is_hava){
                    $old_sku = $nv[0];
                    break;
                }
            }
            
            //更换商品信息
            $this->_ordersdf['order_bool_type'] = intval($order_info['order_bool_type']) | ome_order_bool_type::__UPDATEITEM_CODE;
            $this->_ordersdf['change_sku'] = $change_sku;
            $this->_ordersdf['old_sku'] = $old_sku;
        }
    }
    
    /**
     * 取消订单
     * 
     * @return boolen
     */
    protected function _closeOrder()
    {
        if ($this->_ordersdf['status'] == 'dead' &&  $this->_ordersdf['pay_status']=='5' && $this->_ordersdf['ship_status']=='0' && $this->_tgOrder['status']=='active'){
            if($this->_ordersdf['total_amount'] == $this->_tgOrder['total_amount'] && $this->_ordersdf['cost_item'] == $this->_tgOrder['cost_item']){
                if($this->_tgOrder['pay_status']=='4' || ($this->_tgOrder['pay_status']=='1' && $this->_ordersdf['payed']==0)){
                    return true;
                }
            }
        }
        
        return false;
    }
}
