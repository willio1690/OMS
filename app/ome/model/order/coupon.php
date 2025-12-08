<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_order_coupon extends dbeav_model
{
    
    static $TYPE_NAME_LIST = [
        'sku_price'                       => '单品金额',
        'base_discount'                   => '基础优惠',
        'man_jian'                        => '满减',
        'vender_fee'                      => '商家运费',
        'base_fee'                        => '基础运费',
        'remote_fee'                      => '偏远运费',
        'coupon'                          => '优惠券',
        'jing_dou'                        => '京豆',
        'balance'                         => '余额',
        'super_red_envelope'              => '超级红包',
        'plus95'                          => 'plus会员95折优惠',
        'tui_huan_huo_wu_you'             => '退换货无忧',
        'tax_fee'                         => '全球购税费',
        'luo_di_pei_service'              => '落地配服务',
        'should_pay'                      => '应付金额',
        'jing_quan'                       => '京券',
        'dong_quan'                       => '东券',
        'xian_pin_lei_jing_quan'          => '限品类京券',
        'xian_pin_lei_dong_quan'          => '限品类东券',
        'ping_tai_cheng_dan_you_hui_quan' => '按比例平台承担优惠券',
        'li_jin_you_hui'                  => '礼金优惠',
        'zhi_fu_ying_xiao_you_hui'        => '支付营销优惠',
        'jd_zhi_fu_you_hui'               => '京东支付优惠',
        'global_general_tax'              => '全球购一般贸易税',
        'global_general_include_tax'      => '全球购一般贸易税(包税)',
        'jing_xiang_li_jin'               => '京享礼金/首购礼金',
        'platform_cost_amount'            => '平台优惠(原始)',
    ];
    
    /**
     * 获取NameList
     * @param mixed $filter filter
     * @param mixed $field field
     * @return mixed 返回结果
     */
    public function getNameList($filter, $field = '*')
    {
        $list = $this->getList($field, $filter);
        
        foreach ($list as $key => $value) {
            if (!isset(self::$TYPE_NAME_LIST[$value['type']])) {
                unset($list[$key]);
                continue;
            }
            $list[$key]['type_name'] = self::$TYPE_NAME_LIST[$value['type']];
        }
        return $list;
    }
    
    /**
     * 获取OrderCouponFormatData
     * @param mixed $list list
     * @param mixed $index index
     * @return mixed 返回结果
     */
    public function getOrderCouponFormatData($list,$index = 'material_bn')
    {
        $list = ome_func::filter_by_value($list, 'order_id');
        $newData = array();
        foreach ($list as $key => $row) {
            $newDataItem = array();
            foreach ($row as $k => $value) {
                $newDataItem[$value[$index]]['order_id']     = $value['order_id'];
                $newDataItem[$value[$index]]['material_bn']  = $value['material_bn'];
                $newDataItem[$value[$index]][$value['type']] = $value['amount'];
                $newDataItem[$value[$index]]['num']          = $value['num'];
                $newData[$value['order_id']]                        = $newDataItem;
            }
        }
        return $newData;
    }
    
    /**
     * 获取订单对应的平台金额明细
     * 
     * @param int $order_id
     * @return array
     */
    public function getCouponAmountList($order_id)
    {
        $couponList = array();
        
        //select
        $dataList = $this->getList('amount,oid,type,num', array('order_id'=>$order_id));
        if(empty($dataList)){
            return $couponList;
        }
        
        //list
        foreach ($dataList as $key => $val)
        {
            $oid = $val['oid'];
            $couponType = $val['type'];
            
            //amount
            $couponList[$oid][$couponType] = $val['amount'];
            
            //nums
            $couponList[$oid]['num'] = $val['num'];
        }
        
        //商品总计优惠(含支付优惠) = promotion_amount + promotion_pay_amount
        foreach($couponList as $oid => $couponItem)
        {
            $total_promotion_amount = 0;
            foreach($couponItem as $couponType => $amount)
            {
                if(in_array($couponType, array('promotion_amount', 'promotion_pay_amount'))){
                    $total_promotion_amount = bcadd($amount, $total_promotion_amount, 2);
                    
                    $couponList[$oid]['total_promotion_amount'] = $total_promotion_amount;
                }
            }
        }
        
        return $couponList;
    }
    
    /**
     * 均摊PKG捆绑商品的相关金额
     * @todo：avg_amount代表用户实付金额(去除银行卡优惠金额)
     * 
     * @param int $order_id
     * @param array $couponRow 实付金额(已去除银行卡优惠金额)
     * @param arrray
     */
    public function avgCouponPkgAmount($order_id, $couponRow)
    {
        $objectsMdl = app::get('ome')->model('order_objects');
        $salesBasicMaterialMdl = app::get('material')->model('sales_basic_material');
        
        //objects
        $tempList = $objectsMdl->getlist('obj_id,obj_type,oid,goods_id,bn,`delete`', array('order_id'=>$order_id));
        if(empty($tempList)){
            return false;
        }
        
        $objectList = array();
        $objectBns = array();
        foreach ($tempList as $key => $val)
        {
            $oid = $val['oid'];
            $goods_id = $val['goods_id'];
            
            //oid&&pkg
            if(empty($oid) || $val['obj_type'] != 'pkg'){
                continue;
            }
            
            //delete
            if($val['delete'] != 'false'){
                continue;
            }
            
            $objectBns[$goods_id] = array('sales_material_bn'=>$val['bn']);
            
            $objectList[$oid] = $val;
        }
        
        if(empty($objectList)){
            return false;
        }
        
        //获取PKG销售物料促销价格贡献占比
        $tempList = $salesBasicMaterialMdl->getList('sm_id,bm_id,rate,number', array('sm_id'=>array_keys($objectBns)), 0, -1, 'number DESC');
        if(empty($tempList)){
            return false;
        }
        
        foreach($tempList as $key => $val)
        {
            $sm_id = $val['sm_id'];
            $bm_id = $val['bm_id'];
            
            $objectBns[$sm_id]['basic_material'][$bm_id] = $val;
        }
        
        //计算均摊金额
        foreach($couponRow as $oid => $val)
        {
            $coupon_num = $val['num'];
            
            $val['user_payamount'] = ($val['user_payamount'] ? $val['user_payamount'] : 0);
            $val['origin_amount'] = ($val['origin_amount'] ? $val['origin_amount'] : 0);
            $val['total_promotion_amount'] = ($val['total_promotion_amount'] ? $val['total_promotion_amount'] : 0);
            
            //总商品价格 = 单价 * 数量
            $val['origin_amount'] = bcmul($val['origin_amount'], $coupon_num, 2);
            
            //备份金额
            $tmpAmount = $val['user_payamount'];
            $tmpOriginAmount = $val['origin_amount'];
            $tmpPromotionAmount = $val['total_promotion_amount'];
            
            //sm_id
            $sm_id = $objectList[$oid]['goods_id'];
            
            //basic_material
            $bmList = $objectBns[$sm_id]['basic_material'] ? : [];
            $bmCount = count($bmList);
            
            //avg
            $line_i = 0;
            foreach($bmList as $bm_id => $bmVal)
            {
                $line_i++;
                
                $bm_nums = $bmVal['number'];
                $bm_rate = $bmVal['rate'];
                
                //SKU总购买数量
                $sku_nums = $coupon_num * $bm_nums;
                
                //贡献比
                $sku_rate = $bm_rate / 100;
                
                //format
                if($line_i == $bmCount){
                    $avg_amount = $tmpAmount;
                    $avg_promotion_amount = $tmpPromotionAmount;
                    
                    //sku总价
                    $sku_total_price = $tmpOriginAmount;
                    
                    //单价
                    $avg_origin_amount = bcdiv($tmpOriginAmount, $sku_nums, 2);
                }else{
                    $avg_amount = bcmul($val['user_payamount'], $sku_rate, 2);
                    $avg_promotion_amount = bcmul($val['total_promotion_amount'], $sku_rate, 2);
                    $avg_origin_amount = bcmul($val['origin_amount'], $sku_rate, 2);
                    
                    //需要均摊金额,防止除不尽
                    //@todo：有拆分数量场景,必须先除以数量,再剩以数量,除不尽放在最后一个SKU里;
                    if($sku_nums > 1){
                        //用户实付金额
                        $avg_amount = bcdiv($avg_amount, $sku_nums, 2);
                        $avg_amount = bcmul($avg_amount, $sku_nums, 2);
                        
                        //总优惠金额
                        $avg_promotion_amount = bcdiv($avg_promotion_amount, $sku_nums, 2);
                        $avg_promotion_amount = bcmul($avg_promotion_amount, $sku_nums, 2);
                        
                        //单价
                        $avg_origin_amount = bcdiv($avg_origin_amount, $sku_nums, 2);
                        
                        //sku总价
                        $sku_total_price = bcmul($avg_origin_amount, $sku_nums, 2);
                        
                        //剩余单价
                        $tmpOriginAmount = bcsub($tmpOriginAmount, $sku_total_price, 2);
                    }else{
                        //sku总价
                        $sku_total_price = $avg_origin_amount;
                        
                        //剩余单价
                        $tmpOriginAmount = bcsub($tmpOriginAmount, $avg_origin_amount, 2);
                    }
                    
                    $tmpAmount = bcsub($tmpAmount, $avg_amount, 2);
                    $tmpPromotionAmount = bcsub($tmpPromotionAmount, $avg_promotion_amount, 2);
                }
                
                //均摊的相关金额
                $objectList[$oid]['pkg_items'][$bm_id] = array(
                        'avg_amount' => $avg_amount, //用户实付金额
                        'avg_promotion_amount' => $avg_promotion_amount, //pkg货品总优惠金额
                        'avg_origin_amount' => $avg_origin_amount, //pkg货品单价
                        'sku_total_price' => $sku_total_price, //pkg货品总价
                        'buy_nums' => $sku_nums, //购买数量
                );
            }
        }
        
        return $objectList;
    }
}