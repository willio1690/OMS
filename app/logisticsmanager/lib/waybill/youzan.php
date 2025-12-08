<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_youzan extends logisticsmanager_waybill_abstract
{
    /**
     * 云栈订单来源列表
     * @var array $channelsTypeList
     */
    public static $channelsTypeList = array(

    );
    /**
     * 默认订单来源类型
     * @var String 默认来源
     */
    public static $defaultChannelsType = 'OTHER';

    public static $businessType = array(
        'EMS'    => 1,
        'EYB'    => 2,
        'SF'     => 3,
        'ZJS'    => 4,
        'ZTO'    => 5,
        'HTKY'   => 6,
        'UC'     => 7,
        'YTO'    => 8,
        'STO'    => 9,
        'TTKDEX' => 10,
        'DBKD'   => 11,
    );


    /**
     * 获取订单来源类型
     * @param String $type 类型
     * @param String $node_type 节点类型
     */
    public static function get_order_channels_type($type = '', $node_type)
    {
        $type = strtoupper($type);

        $channelsType = self::$channelsTypeList[self::$defaultChannelsType];
        if ($node_type == 'taobao') {
          if (in_array($type, array_keys(self::$channelsTypeList))) {
              $channelsType = self::$channelsTypeList[$type];
          }
        } else {
          $node_type = strtoupper($node_type);
          if (in_array($node_type, array_keys(self::$channelsTypeList))) {
              $channelsType = self::$channelsTypeList[$node_type];
          }
        }
        return $channelsType;
    }

    public static function getBusinessType($type)
    {
        $type = strtoupper($type);
        return self::$businessType[$type];
    }

    /**
     * service_code
     * @param mixed $param param
     * @return mixed 返回值
     */
    public function service_code($param)
    {
        $sdf = array('cp_code' => $param['logistics']);
        $rs  = kernel::single('erpapi_router_request')->set('shop', $param['shop_id'])->logistics_getCorpServiceCode($sdf);
        if ($rs['rsp'] == 'fail' || empty($rs['data'])) {
            return array();
        }
        $data    = json_decode($rs['data'], 1);
        $service = array();
        if ($data['waybill_apply_subscription_info']) {
//            $objWaybillType = $this->_getObject($sdf['cp_code']);
            //            $service = $objWaybillType->getServiceCode($data['waybill_product_type']);
            $obj     = kernel::single('logisticsmanager_waybill_taobao_common');
            $service = $obj->getServiceCode($data);
        }
        if($param['logistics'] == 'SF' && !$service['PAYMENT-TYPE']) {
            $service['SF-PAY-METHOD'] = array(
                'text'       => '付款方式',
                'code'       => 'SF-PAY-METHOD',
                'options'    => [''=>'','1'=>'寄方付','2'=>'收方付','3'=>'第三方付'],
                'input_type' => 'select',
            );
        }
        return $service;
    }

    /**
     * 获取ServiceCodeValue
     * @param mixed $cpCode cpCode
     * @param mixed $serviceCode serviceCode
     * @return mixed 返回结果
     */
    public function getServiceCodeValue($cpCode, $serviceCode)
    {
        if (empty($serviceCode)) {
            return array();
        }
//        $objWaybillType = $this->_getObject($cpCode);
        //        return $objWaybillType->getServiceCodeValue($serviceCode);
        $obj = kernel::single('logisticsmanager_waybill_taobao_common');
        return $obj->getServiceCodeValue($serviceCode);
    }



    /**
     * template_cfg
     * @return mixed 返回值
     */
    public function template_cfg()
    {
        $arr = array(
            'template_name' => '有赞',
            'shop_name'     => '有赞',
            'print_url'     => 'https://page.cainiao.com/waybill/cloud_printing/home.html',
            'template_url'  => 'https://help.youzan.com/displaylist/detail_4_4-2-85520',
            'shop_type'     => 'youzan',
            'control_type'  => 'youzan',
        );
        return $arr;
    }
}
