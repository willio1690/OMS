<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#京东阿尔发 https://jos.jd.com/commondoc?listId=335
class erpapi_logistics_matrix_jdalpha_request_electron extends erpapi_logistics_request_electron
{
    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function bufferRequest($sdf){
        return $this->directNum;
    }
    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf){

        // 京东大件走特殊接口
        if ($this->__channelObj->channel['logistics_code'] == 'JDDJ') {
            return $this->jddj_directRequest($sdf);
        }

        $this->primaryBn = $sdf['primary_bn'];

        $delivery     = $sdf['delivery'];
        $shopInfo     = $sdf['shop'];
        $from_address = $shopInfo['province']. $shopInfo['city'] .  $shopInfo['area'] . $shopInfo['address_detail'];
        $to_address   = $delivery['ship_addr'] ? $delivery['ship_province'] . $delivery['ship_city'] . $delivery['ship_district'] . $delivery['ship_addr'] : '_SYSTEM';

        $receiver = array(
            'contact'      => $delivery['ship_name'],
            'phone'        => $delivery['ship_tel']?$delivery['ship_tel']:$delivery['ship_mobile'],#座机没有，用手机
            'mobile'       => $delivery['ship_mobile'],
            'provinceName' => $delivery['ship_province'],
            'cityName'     => $delivery['ship_city'],
            'countryName'  => $delivery['ship_district'],
            'address'      => $this->charFilter($to_address),
        );

        $sender = array(
            'contact'      => $shopInfo['default_sender'],
            'phone'        => $shopInfo['tel']?$shopInfo['tel']:$shopInfo['mobile'],#座机没有，用手机
            'mobile'       => $shopInfo['mobile'],
            'provinceName' => $shopInfo['province'],
            'cityName'     => $shopInfo['city'],
            'countryName'  => $shopInfo['area'],
            'address'      => $this->charFilter( $from_address),
        );

        #加盟类型
        if($sdf['mode'] == 'join'){
            $params['branchCode'] = $sdf['jdalpha_businesscode'];#承运商发货网点编码
        }else{
        #直营类型
            $params['settlementCode'] = $sdf['jdalpha_businesscode'];#结算编码
        }
        
        //[兼容]京东店铺类型
        if(in_array($delivery['shop_type'], array('360buy', 'jd'))){
            $delivery['shop_type'] = '360buy';
        }
        
        //params
        $params['waybillType']      = '1';#1、普通运单;2、生鲜;3、航空。目前都不支持生鲜
        $params['waybillCount']     = '1';#所需运单的数量最多传99单
        $params['company_code']     = $this->__channelObj->channel['logistics_code'];#承运商编码
        $params['salePlatform']     = $sdf['sale_plateform'];#0010001代表京东平台下的订单
        $params['platformOrderNo']  = $delivery['shop_type'] == "360buy" ? implode(',', $sdf['order_bns']) : $delivery['delivery_bn'] . base_shopnode::node_id('ome');//平台订单号，即pop订单号，如果多订单合并发货，每个订单号之间用“，”逗号分隔,非京东平台,填所对应平台的订单号
        $params['vendorCode']       = $sdf['jdalpha_vendorCode'];#商家编码
        $params['vendorName']       = $shopInfo['shop_name'];#商家名称
        $params['VendorOrderCode']  = $delivery['delivery_bn'];#商家自有订单号
        $params['weight']           = number_format($delivery['weight'],2,".","");
        $params['volume']           = '0';#体积
        $params['promiseTimeType']  = 0;#承诺时效类型，无时效默认传0,ERP暂无时效
        $params['payType']          = '0';#付款方式0-在线支付 目前暂不支持货到付款业务
        $params['goodsMoney']       = number_format($delivery['total_amount'],2,".","");
        $params['shouldPayMoney']   = '0';#代收金额
        $params['needGuarantee']    = $sdf['is_protect']?$sdf['is_protect']:false;#是否要保价
        $params['guaranteeMoney']   = $sdf['protect_price']?number_format($sdf['protect_price'],2,".",""):0;#保价金额
        $params['receiveTimeType']  = '0';#收货时间类型，0任何时间，1工作日2节假日,ERP暂无收货时间
        $params['fromAddress']      = json_encode($sender);#京标发货四级地址
        $params['expressPayMethod'] = $sdf['expressPayMethod'];#快递费付款方式
        $params['expressType']      = $sdf['expressType'];#快件产品类别
        $delivery['company_code']   = $params['company_code'];

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
            if($is_encrypt && $delivery['shop_type'] == "360buy") {
                $encryptTid = current($delivery['order_bns']);
                $encryptOrder = kernel::database()->selectrow('select order_id from sdb_ome_orders where order_bn="'.$encryptTid.'" and shop_id="'.$delivery['shop']['shop_id'].'"');
                if($encryptOrder) {
                    $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$encryptOrder['order_id']], 'encrypt_source_data');
                    if($original) {
                        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);

                    }
                }

                if($encrypt_source_data['oaid']){
                    $receiver['oaid'] = $encrypt_source_data['oaid'];
                    $is_encrypt = false;
                    if($index = strpos($receiver['phone'] , '>>')) {

                        $receiver['phone'] = substr($receiver['phone'] , 0, $index);
                        
                    }
                    if($index = strpos($receiver['mobile'] , '>>')) {
                        
                        $receiver['mobile'] = substr($receiver['mobile'] , 0, $index);
                    }
                    if($index = strpos($receiver['contact'] , '>>')) {
                        
                        $receiver['contact'] = substr($receiver['contact'] , 0, $index);
                    }
                    if($index = strpos($receiver['address'] , '>>')) {
                        
                        $receiver['address'] = substr($receiver['address'] , 0, $index);
                        
                    }



                }else{
                    if($index = strpos($receiver['phone'] , '>>')) {
                        $receiver['phone'] = substr($receiver['phone'] , $index + 2, -5);
                        $is_encrypt = false;
                    }
                    if($index = strpos($receiver['mobile'] , '>>')) {
                        $receiver['mobile'] = substr($receiver['mobile'] , $index + 2, -5);
                        $is_encrypt = false;
                    }
                    
                }

                
                
                
            }
        }
        $params['toAddress']        = json_encode( $receiver);#京标收货四级地址

        if ($delivery['bool_type'] & ome_delivery_bool_type::__SHSM_CODE) {
            $params['serviceList'] = json_encode([['name'=>'DELIVERY_TO_DOOR']]);
        }
        // 云鼎解密
        $gateway = ''; $jst = array ('order_bns' => $delivery['order_bns']);
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];
            // 新增解密字段
            $params['order_bns']    = implode(',', $delivery['order_bns']);
            $gateway = $delivery['shop_type'];
        }

        $back =   $this->requestCall(STORE_LOGISTICS_JDALPHA_WAYBILL_RECEIVE, $params,array(),$sdf, $gateway);

        return $this->backToResult($back, $delivery);
    }
    /**
     * backToResult
     * @param mixed $back back
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function backToResult($back, $delivery){
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        $msg = $back['msg'] ? $back['msg'] : $back['err_msg'] ;
        if($back['rsp'] =='fail' || empty($data)) {
            return $msg;
        }
        $waybillCodeList = $data['msg']['waybillCodeList'];
        $result = array();
        $logi_no = $waybillCodeList[0];
        $waybillExtend['logistics_no'] =  $logi_no;
        $waybillExtend['delivery_bn']  =  $delivery['delivery_bn'];
        $waybillExtend['company_code'] =  $delivery['company_code'];
        $waybillExtendata = $this->waybillExtend($waybillExtend); #获取大头笔

        // $logisticRelate     = $this->getLogisticRelate($delivery['logi_id']);
        // if($this->isJdPrintControl($logisticRelate['prt_tmpl_id'])){
        //     //获取打印面单数据
        //     list($jdPrintData, $msg)  = $this->getPrintData([$logi_no],$delivery,$logisticRelate['jd_businesscode'],'wj');

        //     if($jdPrintData){
        //         $waybillExtendata['json_packet'] = json_encode(['jd_print_data' => $jdPrintData]);
        //     }else{
        //         $logi_no = '';//获取打印数据失败,不可打印
        //     }
        // }

        $result[] = array(
            'succ'         => $logi_no? true : false,
            'msg'          => (string) $msg,
            'delivery_id'  => $delivery['delivery_id'],
            'delivery_bn'  => $delivery['delivery_bn'],
            'logi_no'      => $logi_no,
            'position_no'  => $waybillExtendata['position'],#大头笔编码
            'position'     => $waybillExtendata['position_no'],#大头笔名称
            'package_wd'   => $waybillExtendata['package_wd'],#集包地编码
            'package_wdjc' => $waybillExtendata['package_wdjc'],#集包地名称
            'json_packet'  => $waybillExtendata['json_packet'],
        );
        $this->directDataProcess($result);
        return $result;
    }


    private function jddj_directRequest($sdf)
    {
        $this->title     = '京东大件-获取物流单号';
        $this->primaryBn = $sdf['primary_bn'];

        $serviceCode = [];
        if($this->__channelObj->channel['service_code']) {
            $serviceCode = @json_decode($this->__channelObj->channel['service_code'], 1);
        }

        $delivery     = $sdf['delivery'];
        $shopInfo     = $sdf['shop'];
        $from_address = $shopInfo['province']. $shopInfo['city'] .  $shopInfo['area'] . $shopInfo['address_detail'];
        $to_address   = $delivery['ship_addr'] ? $delivery['ship_province'] . $delivery['ship_city'] . $delivery['ship_district'] . $delivery['ship_addr'] : '_SYSTEM';

        $receiver = array(
            'contact'           =>  $delivery['ship_name'],
            'phone'             =>  $delivery['ship_tel'] ? $delivery['ship_tel'] : $delivery['ship_mobile'],#座机没有，用手机
            'mobile'            =>  $delivery['ship_mobile'] ? $delivery['ship_mobile'] : $delivery['ship_tel'], #没有手机用座机
            'provinceName'      =>  $delivery['ship_province'],
            'cityName'          =>  $delivery['ship_city'],
            'countryName'       =>  $delivery['ship_district'],
            'countrysideName'   =>  $delivery['ship_town'],
            'address'           =>  $this->charFilter($to_address),
        );

        $productSku = $item_name_arr = $weight_arr = $width_arr = $length_arr = $height_arr = [];
        $getOldService = $openBoxService = $deliveryInstallService = $installFlag = [];

        $deliveryItems = app::get('wms')->model('delivery_items')->getList('*', ['delivery_id'=>$delivery['delivery_id']]);
        $productsList  = app::get('material')->model('basic_material')->getList('*', ['bm_id'=>array_column($deliveryItems, 'product_id')]);
        $materialExtList = app::get('material')->model('basic_material_ext')->getList('*', ['bm_id|in'=>array_column($deliveryItems, 'product_id')]);
        $materialExtList = array_column($materialExtList, null, 'bm_id');

        foreach ($productsList as $pk => $pv) {
            $length_arr[] = (string)round($materialExtList[$pv['product_id']]['length']);
            $width_arr[]  = (string)round($materialExtList[$pv['product_id']]['width']);
            $height_arr[] = (string)round($materialExtList[$pv['product_id']]['high']);
            $productSku[] = $pv['bn'];
            $item_name_arr[] = $pv['name'];
            $weight_arr[] = $pv['weight'] ? (string)round($pv['weight']/1000) : "0.1"; // 数值必须大于0；支持小数点后一位
            $installFlag[] = "2"; // 是否安维；如果需要安维需开通京东安装服务；1：是、2：否
            $getOldService[] = (string)$serviceCode['getOldService']['value']; // 增值服务:取旧服务; 0:否 1:是
            $openBoxService[] = (string)$serviceCode['openBoxService']['value']; // 增值服务:开箱服务; 0:否 1:开箱通电 2:开箱验机 3:禁止开箱
            $deliveryInstallService[] = (string)$serviceCode['deliveryInstallService']['value']; // 增值服务:送装一体服务; 0:否 1:是
        }

        $sender = array(
            'contact'      => $shopInfo['default_sender'],
            'phone'        => $shopInfo['tel'] ? $shopInfo['tel'] : $shopInfo['mobile'],#座机没有，用手机
            'mobile'       => $shopInfo['mobile'],
            'provinceName' => $shopInfo['province'],
            'cityName'     => $shopInfo['city'],
            'countryName'  => $shopInfo['area'],
            'address'      => $this->charFilter( $from_address),
        );

        $isJDOrder = 2;
        if (in_array($sdf['delivery']['shop']['node_type'], ['360buy', 'jd'])) {
            $isJDOrder = 1;
        } elseif (in_array($sdf['delivery']['shop']['node_type'], ['taobao','tmall'])) {
            $isJDOrder = 3;
        }

        $order_bn = $sdf['order_bns'][0];
        list($ebu_no, $shop_id) = explode('|||', $this->__channelObj->channel['shop_id']);

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
            if($is_encrypt && $delivery['shop_type'] == "360buy") {

                $encryptTid = current($delivery['order_bns']);
                $encryptOrder = kernel::database()->selectrow('select order_id from sdb_ome_orders where order_bn="'.$encryptTid.'" and shop_id="'.$delivery['shop_id'].'"');

                $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$encryptOrder['order_id']], 'encrypt_source_data');

                $encrypt_source_data = array();
                if($original) {
                    $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                }

                if($encrypt_source_data['oaid']){
                    $receiver['oaid'] = $encrypt_source_data['oaid'];
                    $is_encrypt = false;

                    if($index = strpos($receiver['phone'] , '>>')) {
                        $receiver['phone'] = substr($receiver['phone'] , 0, $index);
                    }
                        
                    if($index = strpos($receiver['mobile'] , '>>')) {
                        $receiver['mobile'] = substr($receiver['mobile'] , 0, $index);
                    }
                        
                    if($index = strpos($receiver['contact'] , '>>')) {
                        $receiver['contact'] = substr($receiver['contact'] , 0, $index);
                    }
                        
                    if($index = strpos($receiver['address'] , '>>')) {
                        $receiver['address'] = substr($receiver['address'] , 0, $index);
                    }
                }else{
                    if($index = strpos($receiver['phone'] , '>>')) {
                        $receiver['phone'] = substr($receiver['phone'] , $index + 2, -5);
                        $is_encrypt = false;
                    }

                    if($index = strpos($receiver['mobile'] , '>>')) {
                        $receiver['mobile'] = substr($receiver['mobile'] , $index + 2, -5);
                        $is_encrypt = false;
                    }
                }
            }
            if($is_encrypt && in_array($delivery['shop_type'], ['shopex_b2b']) ) {
                $encryptTid = current($delivery['order_bns']);
                $encryptOrder = kernel::database()->selectrow('select order_id from sdb_ome_orders where order_bn="'.$encryptTid.'" and shop_id="'.$delivery['shop_id'].'"');
                if($encryptOrder) {
                    $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$encryptOrder['order_id']], 'encrypt_source_data');
                    if($original) {
                        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
                        if($encrypt_source_data['receiver_phone_index']) {
                            $params['platformOrderNo'] = $encrypt_source_data['tid'];
                            $receiver['phone'] = $encrypt_source_data['receiver_phone_index'];
                            $is_encrypt = false;
                        }
                        if($encrypt_source_data['receiver_mobile_index']) {
                            $params['platformOrderNo'] = $encrypt_source_data['tid'];
                            $receiver['mobile'] = $encrypt_source_data['receiver_mobile_index'];
                            $is_encrypt = false;
                        }
                    }
                }
            }
        }
        $params['toAddress']        = json_encode($receiver);#京标收货四级地址

        if ($delivery['bool_type'] & ome_delivery_bool_type::__SHSM_CODE) {
            $params['serviceList'] = json_encode([['name'=>'DELIVERY_TO_DOOR']]);
        }

        // 云鼎解密
        $gateway = ''; $jst = array ('order_bns' => $delivery['order_bns']);
        if ($is_encrypt) {
            $params['s_node_id']    = $delivery['shop']['node_id'];
            $params['s_node_type']  = $delivery['shop_type'];
            // 新增解密字段
            $params['order_bns']    = implode(',', $delivery['order_bns']);
            $gateway = $delivery['shop_type'];
        }

        $params = [
            'tid'               =>  $order_bn,
            'deptNo'            =>  $ebu_no, // 开放平台事业部编号
            'orderNo'           =>  $order_bn,
            'senderName'        =>  $sender['contact'],
            'senderMobile'      =>  $sender['mobile'],
            'senderPhone'       =>  $sender['phone'],#座机没有，用手机
            'senderAddress'     =>  $sender['address'],
            'receiverName'      =>  $receiver['contact'],
            'receiverMobile'    =>  $receiver['mobile'],
            'receiverPhone'     =>  $receiver['phone'],
            'receiverAddress'   =>  $receiver['address'],
            'isJDOrder'         =>  $isJDOrder, // 1-京东；2-其他；3-天猫；
            'isCod'             =>  $delivery['is_cod'] == 'true' ? 1 : 0, // 是否货到付款
            'oaid'              =>  $receiver['oaid'],
            'onDoorPickUp'      =>  (string)$serviceCode['onDoorPickUp']['value'], // 是否上门揽件 1：是、2：非
            'pickUpDate'        =>  date('Y/m/d',time()+3600), // 上门揽件时间
            'isGuarantee'       =>  '2', // 是否保价 1：需要保价、2：不需保价
            // 'productType'       =>  (string)$serviceCode['productType']['value'], // 产品类型；0：宅配(2C业务)；1：零担；2：整车
            'sameCityDelivery'  =>  (string)$serviceCode['sameCityDelivery']['value'], // 是否纯配同城 :0：否；1：是
            'lasDischarge'      =>  (string)$serviceCode['lasDischarge']['value'], // 卸货到店：0-否，1-是，默认否
            'thirdPayment'      =>  (string)$serviceCode['thirdPayment']['value'], // 运费结算方式； 0-月结、2-到付
            'openBoxService'    =>  implode(',', $openBoxService),
            'weight'            =>  implode(',', $weight_arr),
            'width'             =>  implode(',', $width_arr),
            'length'            =>  implode(',', $length_arr),
            'height'            =>  implode(',', $height_arr),
            'installFlag'       =>  implode(',', $installFlag),
            'packageName'       =>  implode(',', $item_name_arr),
            'productSku'        =>  implode(',', $productSku),
            'getOldService'     =>  implode(',', $getOldService),
            'deliveryInstallService'    =>  implode(',',$deliveryInstallService),
        ];
        if ($sdf['is_protect'] == 'true') {
            $params['isGuarantee'] = '1';
            $params['guaranteeValue'] = (string)$sdf['protect_price'];#保价费用
        }

        $back = $this->requestCall(STORE_WMS_TRANSPORTLASWAYBILL_GET, $params,array(),$jst, $gateway);
        return $this->jddjBackToResult($back, $delivery);
    }

    private function jddjBackToResult($back, $delivery){
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        $msg  = isset($data['msg']['jingdong_eclp_co_transportLasWayBill_responce']['v1']) ? $data['msg']['jingdong_eclp_co_transportLasWayBill_responce']['v1']['resultMsg'] : $back['err_msg'] ;
        if($back['rsp'] =='fail' || empty($data)) {
            return $msg;
        }
        $v1         = $data['msg']['jingdong_eclp_co_transportLasWayBill_responce']['v1'];
        $logi_no    = $v1['lwbNo'];

        $result   = [];
        $result[] = array(
            'succ'         => $logi_no? true : false,
            'msg'          => (string) $msg,
            'delivery_id'  => $delivery['delivery_id'],
            'delivery_bn'  => $delivery['delivery_bn'],
            'logi_no'      => $logi_no,
            'json_packet'  => [],
        );
        return $result;
    }

    /**
     * waybillExtend
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function waybillExtend($sdf) {
        $this->title = '京东阿尔法获取大头笔';
        $this->primaryBn = $sdf['delivery_bn'];
        $params['logistics_no'] =  $sdf['logistics_no'];
        $params['company_code'] =  $sdf['company_code'];
        $result = $this->requestCall(STROE_LOGISTICS_JDALPHA_WAYBILL_BIGSHOP_QUERY,$params);

        $data = $result['data'] ? json_decode($result['data'], true):'';
        $data = $data['msg'];
        if($result['rsp'] =='fail' || empty($data)) {
            return false;
        }
        $extend_data['position_no']         =  $data['bigShotCode'];#大头笔编码
        if($data['secondSectionCode']){
            $extend_data['position_no'] = $extend_data['position_no'].'-'.$data['secondSectionCode'];
        }
        if($data['thirdSectionCode']){
            $extend_data['position_no'] = $extend_data['position_no'].'-'.$data['thirdSectionCode'];
        }
        $extend_data['position']            =  $data['bigShotName'];#大头笔名称
        $extend_data['package_wd']          =  $data['gatherCenterCode'];#集包地编码
        $extend_data['package_wdjc']        =  $data['gatherCenterName'];#集包地名称

        $extend_data['json_packet']         = json_encode($data);

        return $extend_data;
    }

    /**
     * recycleWaybill
     * @param mixed $waybillNumber waybillNumber
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回值
     */
    public function recycleWaybill($waybillNumber,$delivery_bn = '') {
        $obj_delviery = app::get('ome')->model('delivery');
        $obj_orders   = app::get('ome')->model('orders');

        // 判断快递单是否补打
        $bill = app::get('ome')->model('delivery_bill')->dump(array('logi_no'=>$waybillNumber));
        if ($bill) {
            $delivery = $obj_delviery->dump(array('delivery_id'=>$bill['delivery_id']),'delivery_id,delivery_bn');

            $order_bns = array($delivery['delivery_bn'].'_'.$bill['log_id']);
        } else {
            $delivery = $obj_delviery->dump(array('logi_no'=>$waybillNumber),'delivery_id,delivery_bn');

            foreach ($obj_orders->getOrdersBnById($delivery['delivery_id']) as $value) {
                $order_bns[] = $value['order_bn'];
            }
        }

        app::get('logisticsmanager')->model('waybill')->update(array('status'=>2,'create_time'=>time()),array('waybill_number'=>$waybillNumber));

        $this->title = '京东阿尔法_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';

        $this->primaryBn = $waybillNumber;

        $opInfo = kernel::single('ome_func')->getDesktopUser();

        $params = array(
            'tid'          => implode(',', $order_bns),
            'company_code' => $this->__channelObj->channel['logistics_code'],
            'logistics_no' => $waybillNumber,
            'name'         => $opInfo['op_name']
        );
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback'
        );
        $this->requestCall(STORE_LOGISTICS_JDALPHA_WAYBILL_UNBIND, $params, $callback);
    }

    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf){


        $mapCode = $params = [];

        $jdalpha = explode('|||',$this->__channelObj->channel['shop_id']);

        // 京东大件用ewCustomerCode
        if ($this->__channelObj->channel['logistics_code'] == 'JDDJ') {
            $mapCode['ewCustomerCode'] = $jdalpha[0];
        } else {
            $mapCode['eCustomerCode'] = $jdalpha[2];
        }

        $wmsDelivery = app::get('wms')->model('delivery')->dump($sdf['delivery_id'], 'shop_id,outer_delivery_bn');
        $shop = app::get('ome')->model('shop')->dump($wmsDelivery['shop_id'],'tbbusiness_type');


        // $omeDelivery = app::get('ome')->model('delivery')->dump(['delivery_bn' => $wmsDelivery['outer_delivery_bn']],'delivery_id');
        // // 运单号回写
        // kernel::single('ome_event_trigger_logistics_electron')->delivery($omeDelivery['delivery_id']);

        $this->title = '获取京东打印数据';
        $this->primaryBn = $sdf['logi_no'];

        $orderBns = kernel::single('ome_extint_order')->getOrderBns($wmsDelivery['outer_delivery_bn']);

        // $params['cp_code'] = 'JD';

        $waybillInfo = array();
        $waybillInfo['orderNo']       = array_pop($orderBns);
        $waybillInfo['popFlag']       = $shop['tbbusiness_type'] == 'SOP'?1:0;
        $waybillInfo['wayBillCode']   = $sdf['logi_no'];
        $waybillInfo['jdWayBillCode'] = $sdf['logi_no'];

        if ($sdf['batch_logi_no']) {
            $waybillInfo['packageCode'] = $sdf['batch_logi_no'];
        }

        $params['map_code']        = json_encode($mapCode); 
        $params['waybill_infos']   = json_encode([$waybillInfo]); 
        $params['object_id']       = substr(time(), 4).uniqid();

        $back = $this->requestCall(STORE_USER_DEFINE_AREA, $params);
        $back['msg'] = $back['res'];
        if($back['rsp'] == 'succ'){
             $data      = json_decode($back['data'],true);
             $back['data'] = $data['jingdong_printing_printData_pullData_responce']['returnType']['prePrintDatas'][0]['perPrintData']?:'';
        }
        return $back;  
    }

    public function getWaybillISearch($sdf = array())
    {

        $params = array(
        );

        $title = '京东订购地址获取';

        $result = $this->__caller->call(STORE_ETMS_SIGNSUCCESS_INFO,$params,array(),$title, 6, $this->__channelObj->channel['logistics_code']);
        if ($result['rsp'] == 'succ' && $result['data']) {
            $this->_getWISCallback($result['data']);
        }

        return array('rsp'=>$result['rsp'],'msg'=>$result['rsp']=='succ'?'获取成功':'获取失败');
    }

    private function _getWISCallback($data)
    {
        $data = json_decode($data,true);

        if (!$data || !is_array($data['resultInfo']) || !is_array($data['resultInfo']['data'])) return ;

        $extendObj = app::get('logisticsmanager')->model('channel_extend');

        // 取有面单号的
        $process_params = array();
        foreach ($data['resultInfo']['data'] as $info) {
            if($info['providerCode'] != $this->__channelObj->channel['logistics_code']) {
                continue;
            }
            if($info['amount'] < (int)$process_params['allocated_quantity']) {
                continue;
            }
            $service = [];
            if(is_array($info['valueAddedServices'])) foreach($info['valueAddedServices'] as $v) {
                $service[$v['serviceCode']] = [
                    'code' => $v['serviceCode'],
                    'name' => $v['serviceName'],
                    'value' => 1
                ];
            }
            $process_params = array(
                'cancel_quantity'    =>  0,
                'allocated_quantity' => $info['amount'],
                'province'           => $info['address']['provinceName'],
                'city'               => $info['address']['cityName'],
                'area'               => $info['address']['countryName'],
                'street'             => $info['address']['countrysideName'],
                'address_detail'     => $info['address']['address'],
                'channel_id'         => $this->__channelObj->channel['channel_id'],
                'print_quantity'     => 0,
                'addon'              => $service
            );
        }

        if (!$process_params) return ;

        $extend = $extendObj->dump(array('channel_id'=>$this->__channelObj->channel['channel_id']),'id');
        if ($extend) $process_params['id'] = $extend['id'];

        $extendObj->save($process_params);
    }
}
