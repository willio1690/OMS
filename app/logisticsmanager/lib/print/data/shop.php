<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Class logisticsmanager_print_data_shop
 * @author ykm 2015-12-23
 * @describe 处理快递单打印店铺相关数据
 */
class logisticsmanager_print_data_shop {
    private $mField = array(
        'shop_id',
        'area',
        'addr',
        'shop_type',
        'node_id',
        'node_type',
        'addon'
    );
    private $cpCode;
    private $rowDelivery;
    private $cloudStackPosition = array();

    /**
     * shop
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function shop(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        $this->cpCode = $corp['type'];
        $middle = array();
        foreach($oriData as $k => $val) {
            $middle[$k] = $val['shop_id'];
        }
        $shopModel = app::get('ome')->model('shop');
        $strField = kernel::single('logisticsmanager_print_data')->getSelectField($this->mField, $field, $shopModel);
        $shopRows = $shopModel->getList($strField, array('shop_id'=>array_values($middle)));

        $cFilter = array(
            'channel_id' => $corp['channel_id'],'status'=>'true',
        );
        $channelInfo = app::get("logisticsmanager")->model("channel")->dump($cFilter, 'channel_type,service_code');
        $serviceCode = @json_decode($channelInfo['service_code'], true);

        $shop = array();
        foreach($shopRows as $row) {
            $addon = $row['addon'];
            $row['user_id'] = $addon['user_id'];
            $shop[$row['shop_id']] = $row;
        }
        if(in_array('cloud_stack_position', $field)) { //打印模板勾选了云栈大头笔
            $this->getCloudStackPosition($middle, $oriData, $shop);
        }
        foreach($middle as $key => $value) {
            #分销王订单新增代销人收货信息
            $tmpShop = $shop[$value];
            if($tmpShop['node_type'] == 'shopex_b2b' || $tmpShop['node_type'] == 'ecshop_b2c'){
                #开启分销王代销人发货信息
                $delivery_cfg = app::get('ome')->getConf('ome.delivery.status.cfg');
                if($delivery_cfg['set']['ome_delivery_sellagent']){
                    #订单扩展表上的状态是
                    $deliveryOrder = app::get($type)->model('delivery_order')->dump(array('delivery_id'=>$oriData[$key]['delivery_id']));
                    $oSellagent = app::get('ome')->model('order_selling_agent');
                    $sellagent_detail = $oSellagent->dump(array('order_id'=>$deliveryOrder['order_id']));
                    #订单扩展表上的状态是1  (只有代销人发货人与发货地址都存在，状态才会是1)
                    if($sellagent_detail['print_status'] == '1'){
                        $tmpShop['name'] = $sellagent_detail['website']['name'];
                        $tmpShop['default_sender'] = $sellagent_detail['seller']['seller_name'];
                        $tmpShop['mobile'] = $sellagent_detail['seller']['seller_mobile'];
                        $tmpShop['tel'] = $sellagent_detail['seller']['seller_phone'];
                        $tmpShop['zip'] = $sellagent_detail['seller']['seller_zip'];
                        $tmpShop['addr'] =  $sellagent_detail['seller']['seller_address'];
                        $tmpShop['area'] = $sellagent_detail['seller']['seller_area'];
                    }
                }
            }

            //丰密模板非拼多多面单打印手机号中间4位星号显示
            if($oriData[$key]['shop_type'] !='pinduoduo' && $serviceCode['SVC-FM']['value']){
                $tmpShop['tel']    = kernel::single('base_view_helper')->modifier_cut($tmpShop['tel'],-1,'****',false,true);
                $tmpShop['mobile'] = kernel::single('base_view_helper')->modifier_cut($tmpShop['mobile'],-1,'****',false,true);
            }

            foreach($field as $f) {
                if(isset($tmpShop[$f])) {
                    $oriData[$key][$pre . $f] = $tmpShop[$f];
                } elseif(method_exists($this, $f)) {
                    $this->rowDelivery = $oriData[$key];
                    $oriData[$key][$pre . $f] = $this->$f($tmpShop);
                } else {
                    $oriData[$key][$pre . $f] = '';
                }
            }
        }
    }

    private function area_0($row) {
        $area = $this->getArea($row);
        return $area[0];
    }

    private function area_1($row) {
        $area = $this->getArea($row);
        return $area[1];
    }

    private function area_2($row) {
        $area = $this->getArea($row);
        return $area[2];
    }

    private function area_all($row) {
        $area = $this->getArea($row);
        return $area[0].$area[1].$area[2];
    }

    private function cloud_stack_position($row) {
        $delivery = $this->rowDelivery;
        $csp = $this->cloudStackPosition[$delivery['delivery_bn']];
        return $csp ? $csp : '';
    }

    private function getCloudStackPosition($middle, $oriData, $shop) {
        /*if($this->cpCode != 'ZTO') {
            return false;
        }*/
        $TBShop = app::get('ome')->model('shop')->getList('shop_id, addon', array('shop_type' => 'taobao', 'node_id|noequal' => ''));
        foreach($TBShop as $val) {
            if($val['addon'] && strtotime($val['addon']['session_expire_time']) > time()) {
                $shopId = $val['shop_id'];
                break;
            }
        }
        if(empty($shopId)) {
            return false;
        }
        $arrMiddle = array_chunk($middle, 10, true);
        $erpapiRouter = kernel::single('erpapi_router_request');
        foreach ($arrMiddle as $middleVal) {
            $arrData = array();
            foreach($middleVal as $k => $val) {
                $area = $this->getArea($shop[$val]);
                $delivery = $oriData[$k];
                $arrData[] = array(
                    'dly_area_0' => $area[0],
                    'dly_area_1' => $area[1],
                    'dly_area_2' => $area[2],
                    'dly_address' => $shop[$val]['addr'],
                    'ship_area_0' => $delivery['ship_province'],
                    'ship_area_1' => $delivery['ship_city'],
                    'ship_area_2' => $delivery['ship_district'],
                    'ship_addr' => $delivery['ship_addr'],
                    'delivery_bn' => $delivery['delivery_bn']
                );
            }
            #$onlineData = $objRouter->setShopId($shopId)->getCloudStackPrintTags($arrData,$this->cpCode,$shopId);
            $onlineData = $erpapiRouter->set('shop', $shopId)->logistics_getCloudStackPrintTags($arrData,$this->cpCode);
            if ($onlineData['rsp'] === 'succ') {
                $waybill_distribute_info_response = json_decode($onlineData['data'], true);
                $waybill_distribute_info = $waybill_distribute_info_response['waybill_distribute_info_response']['waybill_distribute_infos']['waybill_distribute_info'];
                foreach ($waybill_distribute_info as $value) {
                    $this->cloudStackPosition[$value['address_pair']['trade_order_code']] = $value['short_address'];
                }
            }
        }
    }

    private function getArea($row) {
        static $area = array();
        if(!$area[$row['area']]) {
            $area[$row['area']] = explode('-', kernel::single('base_view_helper')->modifier_region($row['area']));
        }
        return $area[$row['area']];
    }
}