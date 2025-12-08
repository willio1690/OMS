<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_wphvip
{
    /**
     * service_code
     * @param mixed $param param
     * @return mixed 返回值
     */
    public function service_code($param)
    {
        $cpCode  = $param['logistics'];
        $service = array(
            'debangwuliu'  => array(
                'transportType' => array(
                    'text'       => '运输方式/产品类型',
                    'code'       => 'transportType',
                    'input_type' => 'select',
                    'options'    => array(
                        'RCP'       => '大件快递360',
                        'NZBRH'     => '重包入户',
                        'ZBTH'      => '重包特惠',
                        'WXJTH'     => '微小件特惠',
                        'JJDJ'      => '经济大件',
                        'PACKAGE'   => '标准快递',
                        'HKDJC'     => '航空大件次日达',
                        'HKDJG'     => '航空大件隔日达',
                        'TZKJC'     => '特快专递',
                        'JZKY'      => '精准空运（仅散客模式支持该运输方式）',
                        'JZQY_LONG' => '精准汽运',
                        'JZKH'      => '精准卡航',
                        'ZCPS '     => '整车配送',
                        'JZZHC'     => '精准专车',
                    ),
                ),
                'deliveryType'  => array(
                    'text'       => '配送方式',
                    'code'       => 'deliveryType',
                    'input_type' => 'select',
                    'options'    => array(
                        '1' => '自提',
                        '2' => '送货进仓',
                        '3' => '送货（不含上楼）',
                        '4' => '送货上楼',
                        '5' => '大件上楼',
                    ),
                ),
                'payType'       => array(
                    'text'       => '支付方式',
                    'code'       => 'payType',
                    'input_type' => 'select',
                    'options'    => array(
                        '0' => '发货人付款（现付）（大客户模式不支持寄付）',
                        '1' => '收货人付款（到付）',
                        '2' => '发货人付款（月结）',
                    ),
                ),
                'insure'        => array(
                    'text'       => '保价',
                    'code'       => 'insure',
                    'input_type' => 'checkbox',
                ),
                'cod'           => array(
                    'text'       => '代收货款',
                    'code'       => 'cod',
                    'input_type' => 'checkbox',
                ),
            ),
            'kuayue'       => array(
                'transportType' => array(
                    'text'       => '运输方式/产品类型',
                    'code'       => 'transportType',
                    'input_type' => 'select',
                    'options'    => array(
                        '10'   => '当天达',
                        '20'   => '次日达',
                        '30'   => '隔日达',
                        '40'   => '陆运件',
                        '50'   => '同城次日',
                        '60'   => '次晨达',
                        '70'   => '同城即日',
                        '80'   => '航空件',
                        '90'   => '早班件',
                        '100'  => '中班件',
                        '110'  => '晚班件',
                        '160'  => '省内次日',
                        '170 ' => '省内即日',
                        '210'  => '空运',
                        '220'  => '专运',
                    ),
                ),
                'payType'       => array(
                    'text'       => '支付方式',
                    'code'       => 'payType',
                    'input_type' => 'select',
                    'options'    => array(
                        '10' => '寄方付',
                        '20' => '收方付',
                        '30' => '第三方付',
                    ),
                ),
                'receiptFlag'   => array(
                    'text'       => '有无回单',
                    'code'       => 'receiptFlag',
                    'input_type' => 'select',
                    'options'    => array(
                        '10' => '有',
                        '20' => '无',
                    ),
                ),
                'dismantling'   => array(
                    'text'       => '是否自动下单',
                    'code'       => 'dismantling',
                    'input_type' => 'select',
                    'options'    => array(
                        '10' => '是',
                        '20' => '否',
                    ),
                ),
            ),
            'tiandihuayu'  => array(
                'transportType' => array(
                    'text'       => '运输方式/产品类型',
                    'code'       => 'transportType',
                    'input_type' => 'select',
                    'options'    => array(
                        '50000000000000000001' => '偏线',
                        '20000000000000000001' => '整车/易-包裹',
                        '70000000000000000001' => '易-安装',
                        '60000000000000000001' => '易-入户',
                        '90000000000000000001' => '经济拼车',
                        '10000000000000000001' => '定日达',
                        '30000000000000000001' => '经济快运',
                    ),
                ),
                'payType'       => array(
                    'text'       => '支付方式',
                    'code'       => 'payType',
                    'input_type' => 'select',
                    'options'    => array(
                        'ARRIVAL_PAYMENT'    => '到货付清',
                        'CASH_PAYMENT'       => '发货付清',
                        'SHIPPER_SETTLEMENT' => '发货结算',
                    ),
                ),
            ),
            'tiandihuayu'  => array(
                'transportType' => array(
                    'text'       => '运输方式/产品类型',
                    'code'       => 'transportType',
                    'input_type' => 'select',
                    'options'    => array(
                        '50000000000000000001' => '偏线',
                        '20000000000000000001' => '整车/易-包裹',
                        '70000000000000000001' => '易-安装',
                        '60000000000000000001' => '易-入户',
                        '90000000000000000001' => '经济拼车',
                        '10000000000000000001' => '定日达',
                        '30000000000000000001' => '经济快运',
                    ),
                ),
                'insure'        => array(
                    'text'       => '保价',
                    'key'        => 'insuranceValue',
                    'code'       => 'insure',
                    'input_type' => 'checkbox',
                ),
            ),
            'shunxinjieda' => array(
                'deliveryType' => array(
                    'text'       => '配送方式',
                    'code'       => 'deliveryType',
                    'input_type' => 'select',
                    'options'    => array(
                        '自提'        => '自提',
                        '送货上楼（无电梯）' => '送货上楼（无电梯）',
                        '送货上楼（有电梯）' => '送货上楼（有电梯）',
                        '送货（不含上楼）'  => '送货（不含上楼）',
                    ),
                ),
                'payType'      => array(
                    'text'       => '支付方式',
                    'code'       => 'payType',
                    'input_type' => 'select',
                    'options'    => array(
                        '现付' => '现付',
                        '到付' => '到付',
                        '月结' => '月结',
                    ),
                ),
                'pickupType'   => array(
                    'text'       => '揽收方式',
                    'code'       => 'pickupType',
                    'input_type' => 'select',
                    'options'    => array(
                        '上门接货' => '上门接货',
                        '客户自送' => '客户自送',
                    ),
                ),
                'backSignBill' => array(
                    'text'       => '签单方式',
                    'code'       => 'backSignBill',
                    'input_type' => 'select',
                    'options'    => array(
                        ''     => '请选择',
                        '原件回单' => '原件回单',
                        '电子回单' => '电子回单',
                    ),
                ),
                'insure'       => array(
                    'text'       => '保价',
                    'key'        => 'insuranceValue',
                    'code'       => 'insure',
                    'input_type' => 'checkbox',
                ),
                'cod'          => array(
                    'text'       => '代收货款',
                    'key'        => 'codAmount',
                    'code'       => 'cod',
                    'input_type' => 'checkbox',
                ),
            ),
            'tiandihuayu'  => array(
                'deliveryType' => array(
                    'text'       => '配送方式',
                    'code'       => 'deliveryType',
                    'input_type' => 'select',
                    'options'    => array(
                        'DELIVERY'      => '送货上门',
                        'PICKUPSELF'    => '客户自提',
                        'BIGUPSTAIRS'   => '大件上楼',
                        'SMALLUPSTAIRS' => '小件上楼',
                    ),
                ),
            ),
            'shunfeng'     => array(
                'expressTypeId' => array(
                    'text'       => '快件产品类别',
                    'code'       => 'expressTypeId',
                    'input_type' => 'select',
                    'options'    => array(
                        '1'   => '顺丰特快',
                        '2'   => '顺丰标快',
                        '6'   => '顺丰即日',
                        '10'  => '国际小包',
                        '12'  => '国际特惠配送',
                        '23'  => '顺丰国际特惠(文件)',
                        '24'  => '顺丰国际特惠(包裹)',
                        '29'  => '国际电商专递-标准',
                        '30'  => '三号便利箱(特快)',
                        '31'  => '便利封/袋(特快)',
                        '32'  => '二号便利箱(特快)',
                        '33'  => '岛内件(80CM)',
                        '35'  => '物资配送',
                        '39'  => '岛内件(110CM)',
                        '50'  => '千点取60',
                        '53'  => '电商盒子F1',
                        '54'  => '电商盒子F2',
                        '59'  => 'E顺递',
                        '60'  => '顺丰特快（文件）',
                        '61'  => 'C1类包裹',
                        '111' => '顺丰干配',
                        '112' => '顺丰空配',
                        '153' => '整车直达',
                        '154' => '重货包裹',
                        '155' => '标准零担',
                        '199' => '特快包裹',
                        '201' => '冷运标快',
                        '202' => '顺丰微小件',
                        '208' => '特惠专配',
                        '209' => '高铁专送',
                        '215' => '大票直送',
                        '221' => '香港冷运到家(≤60厘米)',
                        '229' => '精温专递',
                        '231' => '陆运包裹',
                        '233' => '精温专递（样本陆）',
                        '235' => '极效前置-预售',
                        '238' => '纯重特配',
                        '242' => '丰网速运',
                        '247' => '电商标快',
                        '250' => '极置店配',
                        '255' => '顺丰卡航',
                        '256' => '顺丰卡航（D类）',
                        '257' => '退换上门',
                        '258' => '退换自寄',
                    ),
                ),
                'insure'        => array(
                    'text'       => '保价',
                    'code'       => 'insure',
                    'input_type' => 'checkbox',
                ),
                'specialSafe'   => array(
                    'text'       => '特安',
                    'code'       => 'specialSafe',
                    'input_type' => 'checkbox',
                ),
                'overLW'        => array(
                    'text'       => '超长超重服务',
                    'code'       => 'overLW',
                    'input_type' => 'checkbox',
                ),
            ),
            'jd'           => array(
                'pickupType' => array(
                    'text'       => '揽收方式',
                    'code'       => 'pickupType',
                    'input_type' => 'select',
                    'options'    => array(
                        '2' => '自送',
                        '1' => '上门取件',
                    ),
                ),
                'payType'    => array(
                    'text'       => '支付方式',
                    'code'       => 'payType',
                    'input_type' => 'select',
                    'options'    => array(
                        '1' => '寄付现结',
                        '2' => '到付现结',
                        '3' => '寄付月结',
                    ),
                ),
                'master'     => array(
                    'text'       => '主产品',
                    'code'       => 'master',
                    'key'        => 'productNo',
                    'input_type' => 'select',
                    'options'    => array(
                        'ed-m-0001' => '特惠送/京东大件', // 唯品会说京东大件用特惠送的service_code
                        'ed-m-0002' => '特快送',
                        'LL-SD-M'   => '生鲜特快',
                        'LL-HD-M'   => '生鲜特惠',
                        'ed-m-0012' => '特惠包裹',
                        'ed-m-0020' => '特快包裹',
                        'ed-m-0019' => '特惠小件',
                        'ed-m-0059' => '电商特惠',
                    ),
                ),
                'warmLayer'  => array(
                    'text'       => '配送温层',
                    'code'       => 'warmLayer',
                    'input_type' => 'select',
                    'options'    => array(
                        'common'   => '生鲜普通',
                        'usual'    => '生鲜常温',
                        'alive'    => '生鲜鲜活',
                        'control'  => '生鲜控温',
                        'cold'     => '生鲜冷藏',
                        'freezing' => '生鲜冷冻',
                        'deepCool' => '生鲜深冷',
                    ),
                ),
            ),
        );

        return isset($service[$cpCode]) ? $service[$cpCode] : array();
    }

    /**
     * 打印组件三个接口
     * 获取打印数据 /logistics/waybillApply
     * 获取标准模板 /logistics/templateList
     * 获取自定义模板 /logistics/customTemplateList
     * @return array [description]
     */
    public function template_cfg() {
        $arr = array(
            'template_name' => '唯品会vip',
            'shop_name' => '唯品会',
            'print_url' => 'https://vos.appvipshop.com/vip-lbs-printer/printer/vip_printer_release.exe',
            'template_url' => '',
            'shop_type' => 'vop',
            'control_type' => 'wphvip',
            'request_again' => true
        );
        return $arr;
    }

    /**
     * 获取物流公司编码
     * 
     * @param $logistics_code
     * @return array|mixed
     * author : Joe
     * Date : 2022-01-27 15:37
     */
    public function logistics($logistics_code = '')
    {
        $logistics = array(
            'zhongtong'      => array('code' => 'zhongtong', 'name' => '中通速递'),
            'yunda'          => array('code' => 'yunda', 'name' => '韵达快递'),
            'yuantong'       => array('code' => 'yuantong', 'name' => '圆通快递'),
            'shentong'       => array('code' => 'shentong', 'name' => '申通快递'),
            'shunfeng'       => array('code' => 'shunfeng', 'name' => '顺丰速运'),
            'ems'            => array('code' => 'ems', 'name' => 'EMS'),
            'jd'             => array('code' => 'jd', 'name' => '京东快递'),
            'debangwuliu'    => array('code' => 'debangwuliu', 'name' => '德邦快递'),
            'annto'          => array('code' => 'annto', 'name' => '安得物流'),
            'kuayue'         => array('code' => 'kuayue', 'name' => '跨越'),
            'tiandihuayu'    => array('code' => 'tiandihuayu', 'name' => '天地华宇'),
            'shunxinjieda'   => array('code' => 'shunxinjieda', 'name' => '顺心捷达'),
            'youzhengguonei' => array('code' => 'youzhengguonei', 'name' => '邮政国内小包'),
            'fengwang'       => array('code' => 'fengwang', 'name' => '丰网'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }
}