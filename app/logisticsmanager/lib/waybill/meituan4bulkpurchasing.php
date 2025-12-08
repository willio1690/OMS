<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_meituan4bulkpurchasing
{
     /**
      * 打印组件三个接口
      * 获取打印数据 /logistics/waybillApply
      * 获取标准模板 /logistics/templateList
      * 获取自定义模板 /logistics/customTemplateList
      * @return array [description]
      */
     public function template_cfg() {
        $arr = array(
            'template_name' => '美团电商',
            'shop_name' => '美团电商',
            'print_url' => 'https://portal-portm.meituan.com/klfe/mtob/prod/downloadConfig',
            'template_url' => '',
            'shop_type' => 'meituan4bulkpurchasing',
            'control_type' => 'meituan4bulkpurchasing',
            'request_again' => true
        );
        return $arr;
    }
    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     * @return array
     */
    public function logistics($logistics_code = '') {
        $logistics = array(
            'ems'   => array('code'=>'ems','name'=>'EMS'),
            'shentong'   => array('code'=>'shentong','name'=>'申通'),
            'shunfeng'   => array('code'=>'shunfeng','name'=>'顺丰'),
            'youzhengguonei'   => array('code'=>'youzhengguonei','name'=>'邮政'),
            'yuantong'   => array('code'=>'yuantong','name'=>'圆通'),
            'yunda'   => array('code'=>'yunda','name'=>'韵达'),
            'zhongtong'   => array('code'=>'zhongtong','name'=>'中通'),
            'jd'   => array('code'=>'jd','name'=>'京东'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }

    /**
     * service_code
     * @param mixed $param param
     * @return mixed 返回值
     */
    public function service_code($param)
    {
        $cpCode  = $param['logistics'];
        $service = array(
            'shunfeng' => array(
                'MERCHANT_ACCOUNT' => array(
                    'text' => '商家快递公司账号',
                    'code' => 'MERCHANT_ACCOUNT',
                    'input_type' => 'input'
                ),
                'express_type' => array(
                    'text' => '物流公司业务类型',
                    'code' => 'express_type',
                    'input_type' => 'select',
                    'options'    => array(
                        '1'   => '顺丰特快',
                        '2'   => '顺丰标快',
                        '6'   => '顺丰即日',
                        '10'   => '国际小包',
                        '23'   => '顺丰国际特惠(文件)',
                        '24'   => '顺丰国际特惠(包裹)',
                        '26'   => '国际大件',
                        '29'   => '国际电商专递-标准',
                        '30'   => '三号便利箱(特快)',
                        '31'   => '便利封/袋(特快)',
                        '32'   => '二号便利箱(特快)',
                        '33'   => '岛内件(80CM)',
                        '35'   => '物资配送',
                        '39'   => '岛内件(110CM)',
                        '40'   => '岛内件(140CM)',
                        '41'   => '岛内件(170CM)',
                        '42'   => '岛内件(210CM)',
                        '43'   => '台湾岛内件-批(80CM)',
                        '44'   => '台湾岛内件-批(110CM)',
                        '45'   => '台湾岛内件-批(140CM)',
                        '46'   => '台湾岛内件-批(170CM)',
                        '47'   => '台湾岛内件-批(210CM)',
                        '48'   => '台湾岛内件店取(80CM)',
                        '49'   => '台湾岛内件店取(110CM)',
                        '50'   => '千点取60',
                        '51'   => '千点取80',
                        '52'   => '千点取100',
                        '53'   => '电商盒子F1',
                        '54'   => '电商盒子F2',
                        '55'   => '电商盒子F3',
                        '56'   => '电商盒子F4',
                        '57'   => '电商盒子F5',
                        '58'   => '电商盒子F6',
                        '59'   => 'E顺递',
                        '60'   => '顺丰特快（文件）',
                        '61'   => 'C1类包裹',
                        '62'   => 'C2类包裹',
                        '63'   => 'C3类包裹',
                        '64'   => 'C4类包裹',
                        '65'   => 'C5类包裹',
                        '66'   => '特快D类',
                        '73'   => 'F5超值箱',
                        '99'   => '顺丰国际标快(文件)',
                        '100'   => '顺丰国际标快(包裹)',
                        '104'   => '岛内件(80CM,1kg以内)',
                        '106'   => '国际重货-门到门',
                        '111'   => '顺丰干配',
                        '112'   => '顺丰空配',
                        '113'   => '便利封/袋(标快)',
                        '114'   => '二号便利箱(标快)',
                        '115'   => '三号便利箱(标快)',
                        '116'   => '国际标快-BD2',
                        '117'   => '国际标快-BD3',
                        '118'   => '国际标快-BD4',
                        '119'   => '国际标快-BD5',
                        '120'   => '国际标快-BD6',
                        '121'   => '国际标快-BDE',
                        '126'   => '掌柜-大格',
                        '127'   => '掌柜-中格',
                        '128'   => '掌柜-小格',
                        '129'   => '掌柜-柜到柜(单程)',
                        '130'   => '掌柜-柜到柜(双程)',
                        '132'   => '顺丰国际特惠(FBA)',
                        '136'   => '集货转运',
                        '144'   => '当日配-门(80CM/1KG以内)',
                        '145'   => '当日配-门(80CM)',
                        '146'   => '当日配-门(110CM)',
                        '147'   => '当日配-门(140CM)',
                        '148'   => '当日配-门(170CM)',
                        '149'   => '当日配-门(210CM)',
                        '150'   => '标快D类',
                        '153'   => '整车直达',
                        '160'   => '国际重货-港到港',
                        '178'   => '一号便利箱(特快)',
                        '179'   => '一号便利箱(标快)',
                        '180'   => '岛內件-专车普运',
                        '184'   => '顺丰国际标快+（文件）',
                        '186'   => '顺丰国际标快+（包裹）',
                        '199'   => '特快包裹',
                        '201'   => '冷运标快',
                        '202'   => '顺丰微小件',
                        '207'   => '限时寄递',
                        '209'   => '高铁专送',
                        '215'   => '大票直送',
                        '218'   => '国际电商专递-CD',
                        '221'   => '香港冷运到家(≤60厘米)',
                        '222'   => '香港冷运到家(61-80厘米)',
                        '223'   => '香港冷运到家(81-100厘米)',
                        '224'   => '香港冷运到家(101-120厘米)',
                        '225'   => '香港冷运到家(121-150厘米)',
                        '229'   => '精温专递',
                        '231'   => '陆运包裹',
                        '235'   => '预售当天达',
                        '236'   => '电商退货',
                        '238'   => '纯重特配',
                        '241'   => '国际电商专递-快速',
                        '244'   => '店到店',
                        '245'   => '店到门',
                        '246'   => '门到店',
                        '247'   => '电商标快',
                        '248'   => '自贸区特配',
                        '249'   => '丰礼遇',
                        '250'   => '极置店配',
                        '251'   => '极置店配（专线）',
                        '252'   => '前置小时达',
                        '253'   => '前置当天达',
                        '255'   => '顺丰卡航',
                        '256'   => '顺丰卡航（D类）',
                        '257'   => '医药温控配送',
                        '258'   => '退换自寄',
                        '259'   => '极速配',
                        '260'   => '入仓电标',
                        '261'   => 'O2O店配',
                        '262'   => '前置标快',
                        '263'   => '同城半日达',
                        '264'   => '同城次日达',
                        '265'   => '预售电标',
                        '266'   => '顺丰空配（新）',
                        '267'   => '行李送递-上门',
                        '268'   => '行李送递',
                        '269'   => '酒类配送',
                        '270'   => '行李托运-上门',
                        '271'   => '行李托运',
                        '272'   => '行李送递-上门 (九龙)',
                        '273'   => '温控配送自取',
                        '274'   => '温控配送上门',
                        '275'   => '酒类温控自取',
                        '276'   => '酒类温控上门',
                        '277'   => '跨境FBA空运',
                        '278'   => '跨境FBA海运',
                        '283'   => '填舱标快',
                        '285'   => '填舱电标',
                        '287'   => '冷运大件标快',
                        '288'   => '冷运大件到港',
                        '289'   => '跨城急件',
                        '293'   => '特快包裹（新）',
                        '297'   => '样本安心递',
                        '299'   => '标快零担',
                    )
                ),
            )
        );
        return isset($service[$cpCode]) ? $service[$cpCode] : array(
            'MERCHANT_ACCOUNT' => array(
                'text' => '商家快递公司账号',
                'code' => 'MERCHANT_ACCOUNT',
                'input_type' => 'input'
            )
        );
    }
}
