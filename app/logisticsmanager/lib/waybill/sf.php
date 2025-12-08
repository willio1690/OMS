<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_sf
{

    public static $businessType = array(
        '1'   => 1,
        '2'   => 2,
        '3'   => 3,
        '7'   => 7,
        '28'  => 28,
        '37'  => 37,
        '38'  => 38,
        '111' => 111,
        '112' => 112,
        '13'  => 13,
    );

    public static $serviceCode = array(
        '37' => 'SFCR',
        '38' => 'SFGR',
    );

    /**
     * logistics
     * @param mixed $logistics_code logistics_code
     * @return mixed 返回值
     */
    public function logistics($logistics_code = '')
    {
        $logistics = array(
            '1'   => array('code' => '1', 'name' => '顺丰特快'),
            '2'   => array('code' => '2', 'name' => '顺丰标快'),
            '6'   => array('code' => '6', 'name' => '顺丰即日'),
            '10'   => array('code' => '10', 'name' => '国际小包'),
            '23'   => array('code' => '23', 'name' => '顺丰国际特惠(文件)'),
            '24'   => array('code' => '24', 'name' => '顺丰国际特惠(包裹)'),
            '26'   => array('code' => '26', 'name' => '国际大件'),
            '29'   => array('code' => '29', 'name' => '国际电商专递-标准'),
            '30'   => array('code' => '30', 'name' => '三号便利箱(特快)'),
            '31'   => array('code' => '31', 'name' => '便利封/袋(特快)'),
            '32'   => array('code' => '32', 'name' => '二号便利箱(特快)'),
            '33'   => array('code' => '33', 'name' => '岛内件(80CM)'),
            '35'   => array('code' => '35', 'name' => '物资配送'),
            '39'   => array('code' => '39', 'name' => '岛内件(110CM)'),
            '40'   => array('code' => '40', 'name' => '岛内件(140CM)'),
            '41'   => array('code' => '41', 'name' => '岛内件(170CM)'),
            '42'   => array('code' => '42', 'name' => '岛内件(210CM)'),
            '43'   => array('code' => '43', 'name' => '台湾岛内件-批(80CM)'),
            '44'   => array('code' => '44', 'name' => '台湾岛内件-批(110CM)'),
            '45'   => array('code' => '45', 'name' => '台湾岛内件-批(140CM)'),
            '46'   => array('code' => '46', 'name' => '台湾岛内件-批(170CM)'),
            '47'   => array('code' => '47', 'name' => '台湾岛内件-批(210CM)'),
            '48'   => array('code' => '48', 'name' => '台湾岛内件店取(80CM)'),
            '49'   => array('code' => '49', 'name' => '台湾岛内件店取(110CM)'),
            '50'   => array('code' => '50', 'name' => '千点取60'),
            '51'   => array('code' => '51', 'name' => '千点取80'),
            '52'   => array('code' => '52', 'name' => '千点取100'),
            '53'   => array('code' => '53', 'name' => '电商盒子F1'),
            '54'   => array('code' => '54', 'name' => '电商盒子F2'),
            '55'   => array('code' => '55', 'name' => '电商盒子F3'),
            '56'   => array('code' => '56', 'name' => '电商盒子F4'),
            '57'   => array('code' => '57', 'name' => '电商盒子F5'),
            '58'   => array('code' => '58', 'name' => '电商盒子F6'),
            '59'   => array('code' => '59', 'name' => 'E顺递'),
            '60'   => array('code' => '60', 'name' => '顺丰特快（文件）'),
            '61'   => array('code' => '61', 'name' => 'C1类包裹'),
            '62'   => array('code' => '62', 'name' => 'C2类包裹'),
            '63'   => array('code' => '63', 'name' => 'C3类包裹'),
            '64'   => array('code' => '64', 'name' => 'C4类包裹'),
            '65'   => array('code' => '65', 'name' => 'C5类包裹'),
            '66'   => array('code' => '66', 'name' => '特快D类'),
            '73'   => array('code' => '73', 'name' => 'F5超值箱'),
            '99'   => array('code' => '99', 'name' => '顺丰国际标快(文件)'),
            '100'   => array('code' => '100', 'name' => '顺丰国际标快(包裹)'),
            '104'   => array('code' => '104', 'name' => '岛内件(80CM,1kg以内)'),
            '106'   => array('code' => '106', 'name' => '国际重货-门到门'),
            '111'   => array('code' => '111', 'name' => '顺丰干配'),
            // '112'   => array('code' => '112', 'name' => '顺丰空配'),
            '113'   => array('code' => '113', 'name' => '便利封/袋(标快)'),
            '114'   => array('code' => '114', 'name' => '二号便利箱(标快)'),
            '115'   => array('code' => '115', 'name' => '三号便利箱(标快)'),
            '116'   => array('code' => '116', 'name' => '国际标快-BD2'),
            '117'   => array('code' => '117', 'name' => '国际标快-BD3'),
            '118'   => array('code' => '118', 'name' => '国际标快-BD4'),
            '119'   => array('code' => '119', 'name' => '国际标快-BD5'),
            '120'   => array('code' => '120', 'name' => '国际标快-BD6'),
            '121'   => array('code' => '121', 'name' => '国际标快-BDE'),
            '126'   => array('code' => '126', 'name' => '掌柜-大格'),
            '127'   => array('code' => '127', 'name' => '掌柜-中格'),
            '128'   => array('code' => '128', 'name' => '掌柜-小格'),
            '129'   => array('code' => '129', 'name' => '掌柜-柜到柜(单程)'),
            '130'   => array('code' => '130', 'name' => '掌柜-柜到柜(双程)'),
            '132'   => array('code' => '132', 'name' => '顺丰国际特惠(FBA)'),
            '136'   => array('code' => '136', 'name' => '国际集运'),
            '144'   => array('code' => '144', 'name' => '当日配-门(80CM/1KG以内)'),
            '145'   => array('code' => '145', 'name' => '当日配-门(80CM)'),
            '146'   => array('code' => '146', 'name' => '当日配-门(110CM)'),
            '147'   => array('code' => '147', 'name' => '当日配-门(140CM)'),
            '148'   => array('code' => '148', 'name' => '当日配-门(170CM)'),
            '149'   => array('code' => '149', 'name' => '当日配-门(210CM)'),
            '150'   => array('code' => '150', 'name' => '标快D类'),
            '153'   => array('code' => '153', 'name' => '整车直达'),
            '160'   => array('code' => '160', 'name' => '国际重货-港到港'),
            '178'   => array('code' => '178', 'name' => '一号便利箱(特快)'),
            '179'   => array('code' => '179', 'name' => '一号便利箱(标快)'),
            '180'   => array('code' => '180', 'name' => '岛內件-专车普运'),
            '184'   => array('code' => '184', 'name' => '顺丰国际标快+（文件）'),
            '186'   => array('code' => '186', 'name' => '顺丰国际标快+（包裹）'),
            // '199'   => array('code' => '199', 'name' => '特快包裹'),
            '201'   => array('code' => '201', 'name' => '冷运标快'),
            '202'   => array('code' => '202', 'name' => '顺丰微小件'),
            '207'   => array('code' => '207', 'name' => '限时寄递'),
            // '209'   => array('code' => '209', 'name' => '高铁专送'),
            '215'   => array('code' => '215', 'name' => '大票直送'),
            '218'   => array('code' => '218', 'name' => '国际电商专递-CD'),
            '221'   => array('code' => '221', 'name' => '香港冷运到家(≤60厘米)'),
            '222'   => array('code' => '222', 'name' => '香港冷运到家(61-80厘米)'),
            '223'   => array('code' => '223', 'name' => '香港冷运到家(81-100厘米)'),
            '224'   => array('code' => '224', 'name' => '香港冷运到家(101-120厘米)'),
            '225'   => array('code' => '225', 'name' => '香港冷运到家(121-150厘米)'),
            // '229'   => array('code' => '229', 'name' => '精温专递'),
            '231'   => array('code' => '231', 'name' => '陆运包裹'),
            '235'   => array('code' => '235', 'name' => '预售当天达'),
            '236'   => array('code' => '236', 'name' => '电商退货'),
            // '238'   => array('code' => '238', 'name' => '纯重特配'),
            '241'   => array('code' => '241', 'name' => '国际电商专递-快速'),
            // '242'   => array('code' => '242', 'name' => '丰网速运'),
            '244'   => array('code' => '244', 'name' => '店到店'),
            '245'   => array('code' => '245', 'name' => '店到门'),
            '246'   => array('code' => '246', 'name' => '门到店'),
            '247'   => array('code' => '247', 'name' => '电商标快'),
            // '248'   => array('code' => '248', 'name' => '自贸区特配'),
            '249'   => array('code' => '249', 'name' => '丰礼遇'),
            // '250'   => array('code' => '250', 'name' => '极置店配'),
            // '251'   => array('code' => '251', 'name' => '极置店配（专线）'),
            '252'   => array('code' => '252', 'name' => '前置小时达'),
            '253'   => array('code' => '253', 'name' => '前置当天达'),
            '255'   => array('code' => '255', 'name' => '顺丰卡航'),
            '256'   => array('code' => '256', 'name' => '顺丰卡航（D类）'),
            '257'   => array('code' => '257', 'name' => '医药温控配送'),
            '258'   => array('code' => '258', 'name' => '退换自寄'),
            '259'   => array('code' => '259', 'name' => '极速配'),
            // '260'   => array('code' => '260', 'name' => '入仓电标'),
            '261'   => array('code' => '261', 'name' => 'O2O店配'),
            '262'   => array('code' => '262', 'name' => '前置标快'),
            '263'   => array('code' => '263', 'name' => '同城半日达'),
            // '264'   => array('code' => '264', 'name' => '同城次日达'),
            '265'   => array('code' => '265', 'name' => '预售电标'),
            '266'   => array('code' => '266', 'name' => '顺丰空配（新）'),
            '267'   => array('code' => '267', 'name' => '行李送递-上门'),
            '268'   => array('code' => '268', 'name' => '行李送递'),
            '269'   => array('code' => '269', 'name' => '酒类配送'),
            '270'   => array('code' => '270', 'name' => '行李托运-上门'),
            '271'   => array('code' => '271', 'name' => '行李托运'),
            '272'   => array('code' => '272', 'name' => '行李送递-上门 (九龙)'),
            '273'   => array('code' => '273', 'name' => '温控配送自取'),
            '274'   => array('code' => '274', 'name' => '温控配送上门'),
            '275'   => array('code' => '275', 'name' => '酒类温控自取'),
            '276'   => array('code' => '276', 'name' => '酒类温控上门'),
            '277'   => array('code' => '277', 'name' => '跨境FBA空运'),
            '278'   => array('code' => '278', 'name' => '跨境FBA海运'),
            '283'   => array('code' => '283', 'name' => '填舱标快'),
            '285'   => array('code' => '285', 'name' => '填舱电标'),
            // '287'   => array('code' => '287', 'name' => '冷运大件标快'),
            '288'   => array('code' => '288', 'name' => '冷运大件到港'),
            '289'   => array('code' => '289', 'name' => '跨城急件'),
            '293'   => array('code' => '293', 'name' => '特快包裹（新）'),
            '297'   => array('code' => '297', 'name' => '样本安心递'),
            '299'   => array('code' => '299', 'name' => '标快零担'),
            '303'   => array('code' => '303', 'name' => '专享急件'),
            // '304'   => array('code' => '304', 'name' => '特早达'),
            // '306'   => array('code' => '306', 'name' => '专享急件（海外）'),
            '308'   => array('code' => '308', 'name' => '国际特快（文件）'),
            '310'   => array('code' => '310', 'name' => '国际特快（包裹）'),
            '316'   => array('code' => '316', 'name' => '前置次日达'),
            '318'   => array('code' => '318', 'name' => '航空港到港'),
            '323'   => array('code' => '323', 'name' => '电商微小件'),
            '325'   => array('code' => '325', 'name' => '温控包裹'),
            '329'   => array('code' => '329', 'name' => '填舱大件'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }
   
    

    /**
     * pay_method
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function pay_method($method = '')
    {
        $payMethod = array(
            '1' => array('code' => '1', 'name' => '寄方付'),
            '2' => array('code' => '2', 'name' => '收方付'),
            '3' => array('code' => '3', 'name' => '第三方付'),
        );

        if (!empty($method)) {
            return $payMethod[$method];
        }
        return $payMethod;
    }

    public static function getBusinessType($type)
    {
        return self::$businessType[$type];
    }

    //顺丰承运商服务类型的对应oms本地的物流公司编码
    public static function getLogiCodeByCarrierService($service)
    {
        return self::$serviceCode[$service];
    }

    //根据本地物流公司编码对应顺丰承运商服务类型
    public static function getCarrierServiceByLogiId($logi_id)
    {
        $corpObj = app::get('ome')->model('dly_corp');
        $corp    = $corpObj->dump($logi_id, 'channel_id');

        if ($corp['channel_id'] > 0) {
            $channelObj = app::get('logisticsmanager')->model('channel');
            $channel    = $channelObj->dump($corp['channel_id'], 'logistics_code');
            $code       = $channel['logistics_code'];
            $service    = self::$logistics[$code]['name'];
        }

        if ($service) {
            return $service;
        } else {
            return '';
        }
    }

    public static function getCarrierBycode($code)
    {
        $corpList = array(
            'EMS'   => '中国邮政',
            'JD'    => '京东快递',
            'STO'   => '申通快递',
            'ZJS'   => '宅急送',
            'YUNDA' => '韵达快递',
            'YTO'   => '圆通快递',
            'ZTO'   => '中通快递',
            'SFYX'  => '顺丰优选',
            'QF'    => '全峰快递',
            'HT'    => '汇通快递',
            'MFZS'  => '魔法自送快递',
            'ZHWL'  => '兆航物流',
            'GTO'   => '国通快递',
            'TTKDE' => '天天快递',
            'GTO'   => '国通快递',
            'GTO'   => '国通快递',
        );
        if ($corpList[$code]) {
            return $corpList[$code];
        } else {
            return '';
        }

    }

    /**
     * service_code
     * @param mixed $param param
     * @return mixed 返回值
     */
    public function service_code($param)
    {
        $service = array(
            'SVC-ISDOCALL'   => array(
                'text'       => '通知取件',
                'code'       => 'SVC-ISDOCALL',
                'input_type' => 'checkbox',
            ),
            'SVC-TIMEDOCALL' => array(
                'text'        => '取件时间',
                'code'        => 'SVC-TIMEDOCALL',
                'input_type'  => 'text',
                'placeholder' => '13:00,16:00',
            ),
            'SVC-COD' => array(
                'text'       => '代收货款',
                'code'       => 'SVC-COD',
                'input_type' => 'checkbox',
            ),
            'SVC-ZMD' => array(
                'text'       => '开启子母单',
                'code'       => 'SVC-ZMD',
                'input_type' => 'checkbox',
            ),
            'SVC-FM'  => array(
                'text'       => '开启丰密',
                'code'       => 'SVC-FM',
                'input_type' => 'checkbox',
                'require'   =>'true',
            ),
        );

        return $service;
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
            'template_name' => '顺丰',
            'control_type' => 'sf',
            'print_url' => 'http://scp-tcdn.sf-express.com/scp/soft/SCPPrint_Win32NT_4.160CN.exe',
        );
        return $arr;
    }
}
