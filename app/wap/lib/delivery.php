<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店自提发货单处理Lib类
 * 
 * @author: wangbiao@shopex.cn
 */
class wap_delivery
{
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 通过外部发货单号找到关联的订单号
     *
     * @param $outer_delivery_bn    ome发货单号
     * @return Array
     */
    public function getList($filter, $offset=0, $limit=2, $orderby='', $current_page_type="")
    {
        $wapDeliveryObj    = app::get('wap')->model('delivery');
        
        //门店订单的配送超时时间
        if($filter['branch_id'])
        {
            $storeObj       = app::get('o2o')->model('store');
            $storeInfo      = $storeObj->dump(array('branch_id'=>$filter['branch_id']), 'store_id');
        }
        
        //发货单列表
        $fields            = 'delivery_id, delivery_bn, itemNum, status, print_status, process_status, is_received, shop_id, 
                              outer_delivery_bn, ship_name, ship_area, ship_addr, ship_mobile, ship_tel, 
                              branch_id, confirm, is_cod, order_bn, total_amount, logi_name, create_time';
        $dataList          = $wapDeliveryObj->getList($fields, $filter, $offset, $limit, $orderby);
        foreach ($dataList as $key => $val)
        {
            $order_info = $this->get_order_info($val['order_bn'], $val['is_cod']);
            #订单信息
            $dataList[$key]['order_info']    = $order_info;
            $shop_type = $order_info['shop_type'];
            
            if (strpos($val['ship_name'], '>>')) {
                $dataList[$key]['ship_name'] = substr($val['ship_name'], 0, strpos($val['ship_name'], '>>'));
            }
            if (strpos($val['ship_mobile'], '>>')) {
                $dataList[$key]['ship_mobile'] = substr($val['ship_mobile'], 0, strpos($val['ship_mobile'], '>>'));
            }
            if (strpos($val['ship_addr'], '>>')) {
                $dataList[$key]['ship_addr'] = substr($val['ship_addr'], 0, strpos($val['ship_addr'], '>>'));
            }

            $ship_area_str = '';
            if ($val['ship_area']) {
                $ship_area = explode(":", $val['ship_area']);
                $ship_area_str = str_replace("/", "", $ship_area[1]);
               
                if(strpos($dataList[$key]['ship_addr'], $ship_area_str) === false ){
                    $dataList[$key]['ship_addr'] = $ship_area_str . $dataList[$key]['ship_addr'];
                }
                
            }
            // 店铺渠道名称
            $dataList[$key]['shop_type_name'] = ome_shop_type::shop_name($shop_type);
            #发货单明细
            if($val['outer_delivery_bn'])
            {
                $count_num    = 0;
                $dataList[$key]['delivery_items']    = $this->getDeliveryItemList($val['outer_delivery_bn']);
                $dataList[$key]['dlyItemCount']      = $count_num;
            }
            $dataList[$key]['dly_status']    = $this->formatDeliveryStatus('status', $val['status']);
            $dataList[$key]['dly_confirm']   = $this->formatDeliveryStatus('confirm', $val['confirm']);
            #已签收
            if($val['status']=='0' && $val['print_status']=='1')
            {
                $dataList[$key]['dly_status']    = '已打印';
            }
            #已签收
            if($val['status']=='3' && $val['is_received']=='2')
            {
                $dataList[$key]['dly_status']    = '已签收';
            }
            
            #门店自提仓库对应店铺信息
            $dataList[$key]['shop_info']     = $this->getBranchShopInfo($val['branch_id']);
            
            #付款方式
            $dataList[$key]['pay_name']    = ($val['is_cod']=='true' ? '货到付款' : '款到发货');
            
            #履约超时时间
            $dataList[$key]['dly_overtime']    = $this->getDeliveryOvertime($val['create_time']);
            
            //获取page_type用来控制操作按钮显示
            if($current_page_type){
                $dataList[$key]['current_page_type'] = $current_page_type;
            }

            // 处理签收照片
            $deliveryImages = kernel::single('wap_deliveryimg')->getwapdeliveryImages($val['delivery_id']);
            if($deliveryImages){
                $dataList[$key]['count_signimage'] = count($deliveryImages);
                $dataList[$key]['signimage_attachment_url'] = json_encode($deliveryImages);
            }
        }

        unset($filter, $fields, $count_num, $storeInfo, $mark_text);
        
        return $dataList;
    }
    
    /**
     * 通过外部发货单号找到关联的订单号
     * 
     * @param $outer_delivery_bn    ome发货单号
     * @return Array
     */
    public function getDeliverBnByOrderId($outer_delivery_bn)
    {
        $deliveryObj    = app::get('ome')->model('delivery');
        
        $sql            = "SELECT dord.order_id FROM sdb_ome_delivery_order AS dord 
                           LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                           WHERE d.delivery_bn='". $outer_delivery_bn ."' AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' 
                           AND d.status NOT IN('failed','cancel','back','return_back')";
        $order_ids      = $deliveryObj->db->selectrow($sql);
        
        return $order_ids['order_id'];
    }
    
    /**
     * 通过外部发货单号找到ome发货单号详细信息
     *
     * @param $outer_delivery_bn    ome发货单号
     * @return $delivery_bn
     */
    public function getOmeDeliveryInfo($outer_delivery_bn)
    {
        $deliveryObj    = app::get('ome')->model('delivery');
        
        $sql            = "SELECT delivery_id, delivery_bn FROM sdb_ome_delivery WHERE delivery_bn='". $outer_delivery_bn ."' 
                           AND (parent_id=0 OR is_bind='true') AND disabled='false' AND status NOT IN('failed','cancel','back','return_back')";
        $delivery_info  = $deliveryObj->db->selectrow($sql);
        
        return $delivery_info;
    }
    
    /**
     * 通过外部发货单号找到订单详细信息
     *
     * @param $outer_delivery_bn    ome发货单号
     * @return $orderInfo
     */
    public function getDeliverBnByOrderInfo($outer_delivery_bn)
    {
        $ordersObj         = app::get('ome')->model('orders');
        
        $order_id    = $this->getDeliverBnByOrderId($outer_delivery_bn);
        if(empty($order_id))
        {
            return '';
        }
        
        $orerInfo    = $ordersObj->dump(array('order_id'=>$order_id), 'order_id, order_bn, process_status, pay_status, ship_status');
        return $orerInfo;
    }
    
    /**
     * Wap发货单状态
     *
     * @param $outer_delivery_bn    ome发货单号
     * @return $orderInfo
     */
    public function formatDeliveryStatus($type='', $suffix='')
    {
        $data    = array();
        
        $data['status']          = array('处理中', '已取消', '暂停', '已发货');
        $data['print_status']    = array('未打印', '已打印');
        $data['process_status']  = array('未打印', '已打印', '已校验', 4=>'已称重打包', 8=>'已物流交接');
        $data['confirm']         = array(1=>'已确认', 2=>'已拒绝', 3=>'未确认');
        $data['is_cod']         = array("true"=>'货到付款', "false"=>'款到发货');
        
        if($type)
        {
            return $suffix || $suffix === '0' ? $data[$type][$suffix] : $data[$type];
        }
        
        return $data;
    }
    
    /**
     * 通过门店自提仓库获取店铺详细信息
     *
     * @param $outer_delivery_bn    ome发货单号
     * @return $orderInfo
     */
    public function getBranchShopInfo($branch_id)
    {
        if(empty($branch_id)) {
            return [];
        }
        //$oBranchObj     = app::get('ome')->model('branch');

        $storeObj    = app::get('o2o')->model('store');

        $branch_info    = $storeObj->db->selectrow("SELECT branch_id, wms_id, branch_bn, name,store_id FROM sdb_ome_branch WHERE branch_id=" . $branch_id);
        
        $storeInfo    = array();
        if($branch_info)
        {
            $branch_info['branch_name']    = $branch_info['name'];
            unset($branch_info['name']);

            $storeInfo = $storeObj->dump(array('store_id'=>$branch_info['store_id']),'store_id, store_bn, name, area, shop_id, addr, contacter, mobile, tel');


            $storeInfo['store_name']    = $storeInfo['name'];
            $area    = explode(':', $storeInfo['area']);
            $storeInfo['store_addr']    = str_replace('/', '-', $area[1]) . $storeInfo['addr'];
            unset($storeInfo['name']);
        }
        
        return array_merge($branch_info, $storeInfo);
    }
    
    /**
     * 获取订单明细列表
     * 
     * @param int $order_id 订单id
     * @param bool $sort 是否要排序，默认不要。排序后的结果会按照普通商品、捆绑商品、赠品、配件等排列
     */
    function getOrderItemList($order_id, $sort=false)
    {
        $oOrder        = app::get('ome')->model('orders');
        $order_items   = array();
        
        if($sort)
        {
            $items    = $oOrder->dump($order_id, 'order_id', array("order_objects"=>array("*")));
            foreach($items['order_objects'] as $k=>$v)
            {
                $order_items[$v['obj_type']][$k] = $v;
                
                $sql    = "SELECT *,nums AS quantity FROM sdb_ome_order_items WHERE obj_id=".$v['obj_id']." AND item_type='product' ORDER BY item_type";
                foreach($oOrder->db->select($sql) as $it)
                {
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
                
                $sql    = "SELECT *,nums AS quantity FROM sdb_ome_order_items WHERE obj_id=".$v['obj_id']." AND item_type<>'product' ORDER BY item_type";
                foreach($oOrder->db->select($sql) as $it)
                {
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
            }
        }
        else 
        {
            $items    = $oOrder->dump($order_id, 'order_id', array("order_objects"=>array("*",array("order_items"=>array("*")))));
            foreach($items['order_objects'] as $oneobj)
            {
                foreach ($oneobj['order_items'] as $objitems)
                    $order_items[] = $objitems;
            }
        }
        
        return $order_items;
    }
    
    /**
     * 获取发货单明细
     */
    function getDeliveryItemList($delivery_bn, $offset=0, $limit=-1)
    {
       $basicMaterialObj = app::get('material')->model('basic_material');
        $bmeMdl = app::get("material")->model("basic_material_ext");
        $deliveryObj = app::get('ome')->model('delivery');
        $dlyItemObj = app::get('ome')->model('delivery_items');
        $dlyItemsDetailObj = app::get('ome')->model('delivery_items_detail');
        $orderItemObj = app::get('ome')->model('order_items');
        $orderObjectsObj = app::get('ome')->model('order_objects');
        $materialLib = kernel::single('material_basic_material');
        $deliveryInfo    = $deliveryObj->dump(array('delivery_bn'=>$delivery_bn), 'delivery_id');
        if(empty($deliveryInfo))
        {
            return '';
        }
        
        $delivery_id   = $deliveryInfo['delivery_id'];
        $items         = $dlyItemObj->getList('*', array('delivery_id' => $delivery_id), 0, -1);
        
        if ($items)
        {
            foreach ($items as $key => $item) {
                // delivery_items_detail
                $orderItemRel = $dlyItemsDetailObj->dump(array('delivery_item_id' => $item['item_id']), 'order_item_id,oid');
                $orderItem = $orderItemObj->dump(array('item_id' => $orderItemRel['order_item_id']), '*');
                $items[$key]['order_item_id'] = $orderItemRel['order_item_id'];

                $items[$key]['order_item_del'] = $orderItem['delete'];
                $items[$key]['oid'] = $orderItemRel['oid'];

                $orderObjects = $orderObjectsObj->dump(array('obj_id' => $orderItem['obj_id']), 'name');
                //$items[$key]['product_name'] = $orderObjects['name'];

                # 商城直接取订单明细里的实付金额和单价
                if($deliveryInfo['shop_type'] == 'ecos.ecshopx'){
                    // 单价
                    $items[$key]['divide_order_price'] = $orderItem['price'];
                    // 总价
                    $items[$key]['divide_order_fee'] = $orderItem['divide_order_fee'];
                }else{
                    // 单价
                    $items[$key]['divide_order_price'] = bcdiv($orderItem['divide_order_fee'], $orderItem['quantity'], 2);
                    // 总价
                    $items[$key]['divide_order_fee'] = bcmul(bcdiv($orderItem['divide_order_fee'], $orderItem['quantity'], 3), $item['number'], 2);
                }

                $addon = unserialize($orderItem['addon']);
                $product_attr = [];
                if ($addon['product_attr']) {
                    foreach ($addon['product_attr'] as $attr) {
                        $product_attr[] = $attr['label'] . ":" . $attr['value'];
                    }
                }

                if ($product_attr) {
                    $items[$key]['product_attr'] = implode("; ", $product_attr);
                } else {
                    $items[$key]['product_attr'] = '';
                }
                $bmeInfo = $bmeMdl->dump(array("bm_id" => $item['product_id']), "specifications,unit");
                if(empty($product_attr)){
                    $items[$key]['product_attr'] = $bmeInfo['specifications'];
                }
                $items[$key]['unit'] = $bmeInfo['unit'];
                $mainImage = $materialLib->getBasicMaterialMainImage($item['product_id']);
                if ($mainImage) {
                    $items[$key]['default_img_url'] = $mainImage['full_url'];
                }

                // 显示图片
               
                if (empty($items[$key]['default_img_url'])) {
                    $items[$key]['default_img_url'] = kernel::base_url() . '/app/wap/statics/img/nopic.jpg';
                }

                //将商品的显示名称改为后台的显示名称
                $productInfo = $basicMaterialObj->dump(array('material_bn' => $items[$key]['bn']), 'material_name');
                $items[$key]['busness_material_bn'] = $productInfo['material_name'];
            }
        }
        
        return $items;
    }
    
    /**
     * 获取履约超时时间
     * 
     * @param $delivery_time 发货时间
     */
    function getDeliveryOvertime($delivery_time)
    {
        //履约超时时间设置(分钟)
        $minute    = app::get('o2o')->getConf('o2o.delivery.dly_overtime');
        $minute    = intval($minute);
        
        if(empty($minute) || empty($delivery_time))
        {
            return false;
        }
        
        $second    = $minute * 60;
        
        //履约超时时间
        $diff_time    = time() - $delivery_time;
        
        return ($diff_time > $second ? $diff_time : 0);
    }
    
    /**
     * 读取缓存数据
     * 
     */
    function fetchDataFromCache()
    {
        $branchObj     = kernel::single('o2o_store_branch');
        $branch_ids    = $branchObj->getO2OBranchByUser(true);
        if(empty($branch_ids))
        {
            return false;
        }
        $branch_id    = $branch_ids[0];
        
        $statistic    = cachecore::fetch('wap_statistic_'. $branch_id);
        
        //更新今日订单总数(15分钟更新一次)
        if($statistic)
        {
            $today_order    = cachecore::fetch('wap_today_order_'. $branch_id);
            if(empty($today_order))
            {
                $this->taskmgr_statistic('today');
                
                cachecore::store('wap_today_order_'. $branch_id, '1', 900);
                
                //重新读取数据
                $statistic    = cachecore::fetch('wap_statistic_'. $branch_id);
            }
        }
        
        return $statistic;
    }
    
    /**
     * 任务列表执行统计订单数据
     */
    function taskmgr_statistic($type)
    {
       return true;
        $branchObj     = kernel::single('o2o_store_branch');
        $branch_ids    = $branchObj->getO2OBranchByUser(true);
        $branch_id     = $branch_ids[0];
        
        if(empty($branch_id) || empty($type))
        {
            return false;
        }
        
        //任务队列
        $push_params = array(
                'data' => array(
                        'branch_id' => $branch_id,
                        'type' => $type,
                        'task_type' => 'autowapdly',
                ),
                'url' => kernel::openapi_url('openapi.autotask','service')
        );
        kernel::single('taskmgr_interface_connecter')->push($push_params);
        
        return true;
    }
    
    /**
     * 获取订单信息(合单发货单有多个订单号order以|分隔)
     */
    function get_order_info($order_bns, $is_cod='')
    {
        if(empty($order_bns))
        {
            return false;
        }
        
        $ordersObj   = app::get('ome')->model('orders');
        $order_bn    = explode('|', $order_bns);
        
        $order_info        = array();
        $order_remark      = array();
        $receivable        = 0;
        
        //订单列表
        $dataList    = $ordersObj->getList('order_id, createtime, mark_text, is_tax, tax_company, paytime,shop_type', array('order_bn'=>$order_bn));
        foreach ($dataList as $key => $val)
        {
            //只取第一条订单信息
            if(empty($order_info))
            {
                $order_info    = $val;
            }
            
            //备注
            if($val['mark_text'])
            {
                $mark_text    = unserialize($val['mark_text']);
                if ($mark_text) {
                    foreach ($mark_text as $k => $v) {
                        if (!strstr($v['op_time'], "-"))
                        {
                            $v['op_time']    = date('Y-m-d H:i:s',$v['op_time']);
                            $mark_text[$k]['op_time']    = $v['op_time'];
                        }
                    }
                }
                $order_remark[$val['order_id']]    = $mark_text;
            }
            
            //货到付款订单的应收费用
            if($is_cod == 'true')
            {
                $temp_price    = app::get('ome')->model('order_extend')->dump(array('order_id'=>$val['order_id']), '*');
                $receivable    += $temp_price['receivable'];
            }
        }
        
        $order_info['mark_text']    = $order_remark;
        $order_info['receivable']   = $receivable;
        
        return $order_info;
    }

    /**
     * 获取首页统计数据
     */
    public function getStoreStatistic($storeInfo) {
        //今日订单总数
        //待发货
        //待退货
        //今日发货取消
        //今日退货取消
        $branch_id = $storeInfo['branch_id'];
        $store_id = $storeInfo['store_id'];
        $db = kernel::database();
        $today  = strtotime(date('Y-m-d', time()) .'00:00');
        

        // 交易笔数
        $sql = "SELECT count(*) as _count FROM sdb_wap_delivery WHERE branch_id=". $branch_id ." AND create_time>=". $today ."";
        $num = $db->selectrow($sql);
        $today_num = $num['_count'] ? $num['_count'] : 0;

    
        // 订单列表
        $sql = "SELECT count(*) as _count from sdb_wap_delivery WHERE branch_id=". $branch_id . " and status in ('0') ";
        $orderList = $db->selectrow($sql);
        $today_delivery_num = $orderList['_count'] ? $orderList['_count'] : 0;

        $sql = "SELECT count(*) as _count from sdb_wap_delivery WHERE branch_id=". $branch_id . " and status in ('1') and last_modified>=".$today."";
        $orderList = $db->selectrow($sql);
        $today_delivery_cancel_num = $orderList['_count'] ? $orderList['_count'] : 0;

        //售后
        $rpMdl = app::get("wap")->model("return");
        $filter = array('branch_id'=>$branch_id, 'status'=>['1']);
        $aftersale_return = $rpMdl->count($filter);
       
        $sql = "select count(*) as _count from sdb_wap_return WHERE branch_id=".$branch_id." AND status in('2') and last_modified>=".$today."";
        $returns = $db->selectrow($sql);
        $aftersale_return_cancel = $returns['_count'] ? $returns['_count'] : 0;

       return array(
            'today_num' => $today_num,
            'wap_order_index' => $today_delivery_num,
            'wap_aftersale_returnproduct' => $aftersale_return,
            'delivery_cancel_num'=>$today_delivery_cancel_num,
            'aftersale_cancel_num' => $aftersale_return_cancel,
        );
    }

     public function checkDeliveryPrint($delivery_id, $action = 'electron', &$msg)
    {
        $deliveryObj = app::get('wap')->model('delivery');
        $orderMdl = app::get('ome')->model('orders');
        $delivery = $deliveryObj->dump(array('delivery_id' => $delivery_id), 'delivery_id,delivery_bn,branch_id,outer_delivery_bn,order_bn,status,print_status');

        $omeDeliveryObj = app::get('ome')->model('delivery');
        $omeDeliveryInfo = $omeDeliveryObj->dump(array('delivery_bn' => $delivery['outer_delivery_bn']), '*');
        if (in_array($omeDeliveryInfo['status'], ['back', 'return_back', 'cancel'])) {
            $msg = '发货单:' . $delivery['delivery_bn'] . '已撤销';
            return false;
        }

        # 发货仓库
        $omeDeliveryInfo['wap_branch_id'] = $delivery['branch_id'];
        $omeDeliveryInfo['wap_delivery_id'] = $delivery['delivery_id'];
        $omeDeliveryInfo['wap_delivery_bn'] = $delivery['delivery_bn'];
        # 检查是否为手工单
        if (!empty($delivery['order_bn'])) {
            $orderInfo = $orderMdl->dump(array('order_bn' => $delivery['order_bn']), 'order_id,pay_status,source,source_status,createway');
            if (empty($orderInfo)) {
                $msg = '该发货单未找到相应的订单信息';
                return false;
            } elseif (in_array($orderInfo['pay_status'], ['5'])) {
                $msg = '该订单已全额退款，不能发货';
                return false;
            }  elseif ($orderInfo['source_status'] == 'TRADE_CLOSED') {
                $msg = '订单交易取消,不能发货';
                return false;
            } elseif ($orderInfo['source_status'] == 'PAID_FORBID_CONSIGN') {
                $msg = '拼团中订单、POP暂停或者发货强管控的订单，已付款但禁止发货';
                return false;
            }

            
        }
        if ($action!='reprint' && $delivery['print_status'] == '1') {
            $msg = '发货单:' . $delivery['outer_delivery_bn'] . '已打印';
            return false;
        }
       
        if ($action!='reprint' && $omeDeliveryInfo['status'] == 'succ') {
            $msg = '发货单:' . $delivery['outer_delivery_bn'] . '已发货';
            return false;
        }

        
        return $omeDeliveryInfo;
    }

    public function checkDeliveryStatus($delivery_id, &$msg)
    {
        if (empty($delivery_id)) {
            return false;
        }

        $deliveryObj = app::get('wap')->model('delivery');
        $omeDeliveryObj = app::get('ome')->model('delivery');

        $delivery = $deliveryObj->dump(array('delivery_id' => $delivery_id), 'delivery_id,delivery_bn,outer_delivery_bn,status');
        if ($delivery['status'] == '1') {
            $msg = '当前发货单已取消';
            return false;
        }

        $omeDeliveryInfo = $omeDeliveryObj->dump(array('delivery_bn' => $delivery['outer_delivery_bn']), '*', array('delivery_items' => array('*'), 'delivery_order' => array('*')));
        if (in_array($omeDeliveryInfo['status'], ['back', 'return_back', 'cancel'])) {
            $msg = '发货单:' . $delivery['delivery_bn'] . '已撤销';
            return false;
        }

        # 设置wap发货单信息
        $omeDeliveryInfo['wap_delivery_id'] = $delivery_id;
        $omeDeliveryInfo['wap_delivery_bn'] = $delivery['delivery_bn'];
        return $omeDeliveryInfo;
    }

}
