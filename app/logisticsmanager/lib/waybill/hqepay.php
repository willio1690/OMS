<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * User: jintao
 * Date: 2016/6/24
 */
class logisticsmanager_waybill_hqepay
{
    /**
     * 默认订单来源类型
     * @var String 默认来源
     */
    public static $defaultChannelsType = 'OTHER';

    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */

    public function logistics($logistics_code = '') {
        $logistics = array(
            'EMS'   => array('code'=>'EMS','name'=>'普通EMS'),
            'SF'    => array('code'=>'SF','name'=>'顺丰'),
            'ZJS'   => array('code' => 'ZJS', 'name'=>'宅急送'),
            'ZTO'   => array('code' => 'ZTO', 'name' => '中通'),
            'HTKY'  => array('code' => 'HTKY', 'name'=>'百世汇通'),
            'YTO'   => array('code' => 'YTO', 'name' => '圆通'),
            'STO'   => array('code' => 'STO', 'name' => '申通'),
            'YD' => array('code' => 'YD', 'name' => '韵达快递'),
            'DBKD'  => array('code' => 'DBKD', 'name' => '德邦快递'),
            'UC'    => array('code' => 'UC', 'name'=>'优速快递'),
            'KYSY'  => array('code' => 'KYSY', 'name'=>'跨越速运'),
            'QFKD'  => array('code' => 'QFKD', 'name'=>'全峰快递'),
            // 'JD'    => array('code' => 'JD', 'name'=>'京东快递'),
            'XFEX'  => array('code' => 'XFEX', 'name'=>'信丰快递'),
            'ANE'   => array('code' => 'ANE', 'name'=>'安能'),
            'FAST'  => array('code' => 'FAST', 'name'=>'快捷'),
            'GTO'  => array('code' => 'GTO', 'name'=>'国通'),
            'HHTT'  => array('code' => 'HHTT', 'name'=>'天天'),
            'YZPY'  => array('code' => 'YZPY', 'name'=>'邮政快递包裹'),
            'ZTKY'  => array('code' => 'ZTKY', 'name'=>'中铁快运'),
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
    public function pay_method($method = '') {
        $payMethod = array(
                '1' => array('code' => '1', 'name' => '现付'),
                '2' => array('code' => '2', 'name' => '到付'),
                '3' => array('code' => '3', 'name' => '月结'),
                '4' => array('code' => '4', 'name' => '第三方支付'),
        );
        if (!empty($method)) {
            return $payMethod[$method];
        }
        return $payMethod;
    }
    function  get_ExpType($type){
       $logistics = array( 
           'SF'=>array(
            /*
               1=>'顺丰次日',
               2=>'顺丰隔日',
               5=>'顺丰次晨',
               6=>'顺丰即日',
               9=>'顺丰宝平邮',
               10=>'顺丰宝挂号',
               11=>'医药常温',
               12=>'医药温控',
               13=>'物流普运',
               14=>'冷运宅配',
               15=>'生鲜速配',
               16=>'大闸蟹专递',
               17=>'汽配专线',
               18=>'汽配吉运',
               19=>'全球顺',
               37=>'云仓专配次日',
               38=>'云仓专配隔日'
            */
                '1'   => '顺丰特快',
                '2'   => '顺丰标快',
                '6'   => '顺丰即日',
                '10'  => '国际小包',
                '23'  => '顺丰国际特惠(文件)',
                '24'  => '顺丰国际特惠(包裹)',
                '26'  => '国际大件',
                '29'  => '国际电商专递-标准',
                '30'  => '三号便利箱(特快)',
                '31'  => '便利封/袋(特快)',
                '32'  => '二号便利箱(特快)',
                '33'  => '岛内件(80CM)',
                '35'  => '物资配送',
                '39'  => '岛内件(110CM)',
                '40'  => '岛内件(140CM)',
                '41'  => '岛内件(170CM)',
                '42'  => '岛内件(210CM)',
                '43'  => '台湾岛内件-批(80CM)',
                '44'  => '台湾岛内件-批(110CM)',
                '45'  => '台湾岛内件-批(140CM)',
                '46'  => '台湾岛内件-批(170CM)',
                '47'  => '台湾岛内件-批(210CM)',
                '48'  => '台湾岛内件店取(80CM)',
                '49'  => '台湾岛内件店取(110CM)',
                '50'  => '千点取60',
                '51'  => '千点取80',
                '52'  => '千点取100',
                '53'  => '电商盒子F1',
                '54'  => '电商盒子F2',
                '55'  => '电商盒子F3',
                '56'  => '电商盒子F4',
                '57'  => '电商盒子F5',
                '58'  => '电商盒子F6',
                '59'  => 'E顺递',
                '60'  => '顺丰特快（文件）',
                '61'  => 'C1类包裹',
                '62'  => 'C2类包裹',
                '63'  => 'C3类包裹',
                '64'  => 'C4类包裹',
                '65'  => 'C5类包裹',
                '66'  => '特快D类',
                '73'  => 'F5超值箱',
                '99'  => '顺丰国际标快(文件)',
                '100' => '顺丰国际标快(包裹)',
                '104' => '岛内件(80CM,1kg以内)',
                '106' => '国际重货-门到门',
                '111' => '顺丰干配',
                '113' => '便利封/袋(标快)',
                '114' => '二号便利箱(标快)',
                '115' => '三号便利箱(标快)',
                '116' => '国际标快-BD2',
                '117' => '国际标快-BD3',
                '118' => '国际标快-BD4',
                '119' => '国际标快-BD5',
                '120' => '国际标快-BD6',
                '121' => '国际标快-BDE',
                '126' => '掌柜-大格',
                '127' => '掌柜-中格',
                '128' => '掌柜-小格',
                '129' => '掌柜-柜到柜(单程)',
                '130' => '掌柜-柜到柜(双程)',
                '132' => '顺丰国际特惠(FBA)',
                '136' => '国际集运',
                '144' => '当日配-门(80CM/1KG以内)',
                '145' => '当日配-门(80CM)',
                '146' => '当日配-门(110CM)',
                '147' => '当日配-门(140CM)',
                '148' => '当日配-门(170CM)',
                '149' => '当日配-门(210CM)',
                '150' => '标快D类',
                '153' => '整车直达',
                '160' => '国际重货-港到港',
                '178' => '一号便利箱(特快)',
                '179' => '一号便利箱(标快)',
                '180' => '岛內件-专车普运',
                '184' => '顺丰国际标快+（文件）',
                '186' => '顺丰国际标快+（包裹）',
                '201' => '冷运标快',
                '202' => '顺丰微小件',
                '207' => '限时寄递',
                '215' => '大票直送',
                '218' => '国际电商专递-CD',
                '221' => '香港冷运到家(≤60厘米)',
                '222' => '香港冷运到家(61-80厘米)',
                '223' => '香港冷运到家(81-100厘米)',
                '224' => '香港冷运到家(101-120厘米)',
                '225' => '香港冷运到家(121-150厘米)',
                '231' => '陆运包裹',
                '235' => '预售当天达',
                '236' => '电商退货',
                '241' => '国际电商专递-快速',
                '244' => '店到店',
                '245' => '店到门',
                '246' => '门到店',
                '247' => '电商标快',
                '249' => '丰礼遇',
                '252' => '前置小时达',
                '253' => '前置当天达',
                '255' => '顺丰卡航',
                '256' => '顺丰卡航（D类）',
                '257' => '医药温控配送',
                '258' => '退换自寄',
                '259' => '极速配',
                '261' => 'O2O店配',
                '262' => '前置标快',
                '263' => '同城半日达',
                '265' => '预售电标',
                '266' => '顺丰空配（新）',
                '267' => '行李送递-上门',
                '268' => '行李送递',
                '269' => '酒类配送',
                '270' => '行李托运-上门',
                '271' => '行李托运',
                '272' => '行李送递-上门 (九龙)',
                '273' => '温控配送自取',
                '274' => '温控配送上门',
                '275' => '酒类温控自取',
                '276' => '酒类温控上门',
                '277' => '跨境FBA空运',
                '278' => '跨境FBA海运',
                '283' => '填舱标快',
                '285' => '填舱电标',
                '288' => '冷运大件到港',
                '289' => '跨城急件',
                '293' => '特快包裹（新）',
                '297' => '样本安心递',
                '299' => '标快零担',
                '303' => '专享急件',
                '308' => '国际特快（文件）',
                '310' => '国际特快（包裹）',
                '316' => '前置次日达',
                '318' => '航空港到港',
                '323' => '电商微小件',
                '325' => '温控包裹',
                '329' => '填舱大件',
           ),
          'HTKY' => array(
               1=>'标准快递'
           ),
          'DBKD'=>array(
              '1'=>'标准快递',
              '2'=>'360特惠件',
              '3'=>'电商尊享'
           ),
          'STO' => array(
              1=>'标准快递'
          ),
          'YTO' => array(
              0=>'自己联系',
              1=>'上门揽收',
              2=>'次日达',
              4=>'次晨达',
              8=>'当日达'
         ),
          'YD'=>array(
              1=>'标准快递'
         ),
          'EMS' => array(
              1=>'标准快递',
              4=>'经济快递',
              8=>'代收到付',
              9=>'快递包裹',
          ),
          'ZTO' =>array(
             1=>'普通订单',
             2=>'线下订单',
             3=>'COD订单',
             4=>'限时物流',
             5=>'快递保障订单'
          ),
         'QRT'=>array(
            1=>'标准快递'
          ),
         'UC'=>array(
           1=>'标准快递'
         ),
         'ZJS'=>array(
           1=>'标准快递'
         ),
         'KYSY'=>array(
           1=>'当天达',
           2=>'次日达',
           3=>'隔日达',
           4=>'同城件',
           5=>'同城即日',
           6=>'同城次日',
           7=>'陆运件',
         ),
         'QFKD'=>array(
           1=>'标准快递'
         ),
         'JD'=>array(
           1=>'标准快递'
         ),
         'XFEX'=>array(
           1=>'标准快递'
         ),
         'ANE'=>array(
           1=>'标准快递'
         ),
         'FAST'=>array(
           1=>'标准快递'
         ),
         'GTO'=>array(
           1=>'标准快递'
         ),
         'HHTT'=>array(
           1=>'标准快递'
         ),
         'YZPY'=>array(
           1=>'标准快递'
         ),
         'ZTKY'=>array(
           1=>'标准快递'
         ),
       );
       if($logistics){
          return $logistics[$type];
       }else{
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
        $cpCode  = $param['logistics'];
        $service = array(
            'EMS'    => array(
                'customer_name' => array(
                    'text'       => '大客户号',
                    'code'       => 'customer_name',
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => 'APP_SECRET',
                    'code'       => 'customer_pwd',
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'SF'     => array(
                'month_code' => array(
                    'text'       => '月结号',
                    'code'       => 'month_code',
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
                'SVC-ZMD' => array(
                    'text'       => '开启子母单',
                    'code'       => 'SVC-ZMD',
                    'input_type' => 'checkbox',
                ),
            ),
            'FAST'   => array(),
            'ZTKY'   => array(),
            'YZBK'   => array(),
            'YZPY'   => array(
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
            ),
            'ZJS'    => array(
                'customer_name' => array(
                    'text'       => '标识',
                    'code'       => 'customer_name',
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '秘钥',
                    'code'       => 'customer_pwd',
                    'input_type' => 'input',
                ),
                'logistic_code' => array(
                    'text'       => '快递单号前缀',
                    'code'       => 'logistic_code',
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'UAPEX'  => array(),
            'ZTO'    => array(
                'customer_name' => array(
                    'text'       => '商家ID',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '商家接口密码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
            ),
            'STO'    => array(
                'customer_name' => array(
                    'text'       => '客户简称',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '客户密码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_site'     => array(
                    'text'       => '所属网点',
                    'code'       => 'send_site',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'DBL'    => array(
                'customer_name' => array(
                    'text'       => '月结编码',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'JD'     => array(
                'customer_name' => array(
                    'text'       => '商家编码',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'XFEX'   => array(
                'customer_name' => array(
                    'text'       => '客户平台ID',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '客户平台验证码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_site'     => array(
                    'text'       => '客户商号ID或仓库ID',
                    'code'       => 'send_site',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'QFKD'   => array(
                'customer_name' => array(
                    'text'       => '账号名称',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => 'Key值',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'TBZS'   => array(
                'customer_name' => array(
                    'text'       => '账号名称',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '密码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'HHTT'   => array(
                'customer_name' => array(
                    'text'       => '客户帐号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '客户密码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_site'     => array(
                    'text'       => '网点名称',
                    'code'       => 'send_site',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'GTO'    => array(
                'customer_name' => array(
                    'text'       => '客户简称',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '客户密码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_site'     => array(
                    'text'       => '网点名称',
                    'code'       => 'send_site',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'SURE'   => array(
                'customer_name' => array(
                    'text'       => '客户号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_site'     => array(
                    'text'       => '网点编号',
                    'code'       => 'send_site',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_staff'    => array(
                    'text'       => '收件快递员',
                    'code'       => 'send_staff',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'KYSY'   => array(
                'customer_name' => array(
                    'text'       => '客户号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
            ),
            'YD'     => array(
                'customer_name' => array(
                    'text'       => '客户ID',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '接口联调密码',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'HTKY'   => array(
                'customer_name' => array(
                    'text'       => '操作编码',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => 'ERP秘钥',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'YTO'    => array(
                'customer_name' => array(
                    'text'       => '商家代码',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'month_code'    => array(
                    'text'       => '密钥串',
                    'code'       => 'month_code',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
            ),
            'YCWL'   => array(
                'customer_name' => array(
                    'text'       => '商家代码',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'send_site'     => array(
                    'text'       => '网点名称',
                    'code'       => 'send_site',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'UC'     => array(
                'customer_name' => array(
                    'text'       => '客户编号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '秘钥',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
            ),
            'ANEKY'    => array(
                'delivery_method' => array(
                    'text'       => '送货方式',
                    'code'       => 'delivery_method',
                    'input_type' => 'select',
                    'options'    => array(
                        '0'             => '自提',
                        '1'             => '送货上门',
                        '2'             => '送货上楼',
                    ),
                ),
                'customer_name' => array(
                    'text'       => '客户号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '秘钥',
                    'code'       => 'customer_pwd',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),

                // 'send_site'     => array(
                //     'text'       => '网点名称（仅数字部分）',
                //     'code'       => 'send_site',
                //     'require'    => true,
                //     'input_type' => 'input',
                // ),
            ),
            'XYJ'    => array(
                'customer_name' => array(
                    'text'       => '帐号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'SDSD'   => array(
                'customer_name'       => array(
                    'text'       => '帐号',
                    'code'       => 'customer_name',
                    'require'    => true,
                    'input_type' => 'input',
                ),
                'product_code'        => array(
                    'text'       => '商品映射',
                    'code'       => 'product_code',
                    // 'require'    => true,
                    'input_type' => 'select',
                    'options'    => array(
                        'bn'            => '货号',
                        'mnemonic_code' => '助记码',
                    ),
                ),
                'logisticsroute_code' => array(
                    'text'       => '运输路线编码',
                    'code'       => 'logisticsroute_code',
                    'input_type' => 'input',
                ),
                'pay_type'            => array(
                    'text'       => '支付类型',
                    'code'       => 'pay_type',
                    'input_type' => 'select',
                    'options'    => array(
                        '1' => '积分',
                        '2' => '余额',
                    ),
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'YBWL'   => array(
                'customer_name' => array(
                    'text'       => '商家编码',
                    'code'       => 'customer_name',
                    'input_type' => 'input',
                ),
                'customer_pwd'  => array(
                    'text'       => '商家密码',
                    'code'       => 'customer_pwd',
                    'input_type' => 'input',
                ),
                'product_code'  => array(
                    'text'       => '商品映射',
                    'code'       => 'product_code',
                    'input_type' => 'select',
                    'options'    => array(
                        'bn'            => '货号',
                        'mnemonic_code' => '助记码',
                    ),
                ),
                'INSURE'       => array(
                    'text'       => '保价',
                    'code'       => 'INSURE',
                    'input_type' => 'checkbox',
                ),
                'COD'=>array(
                    'text'       => '代收货款',
                    'code'       => 'COD',
                    'input_type' => 'checkbox',
                ),
            ),
            'SUPERB' => array(
                'customer_name'       => array(
                    'text'       => '商家编码',
                    'code'       => 'customer_name',
                    'input_type' => 'input',
                ),
                'customer_pwd'        => array(
                    'text'       => '商家密码',
                    'code'       => 'customer_pwd',
                    'input_type' => 'input',
                ),
                'product_code'        => array(
                    'text'       => '商品映射',
                    'code'       => 'product_code',
                    'input_type' => 'select',
                    'options'    => array(
                        'bn'            => '货号',
                        'mnemonic_code' => '助记码',
                        'barcode'       => '条形码',
                    ),
                ),
                // 'address2' => array(
                //     'text'       => '门牌号',
                //     'code'       => 'address2',
                //     'input_type' => 'input',
                // ),
                'logisticsroute_code' => array(
                    'text'       => '运输路线编码',
                    'code'       => 'logisticsroute_code',
                    'input_type' => 'input',
                ),
            ),
            'JXWL'     => array(
                'customer_name'       => array(
                    'text'       => '商家编码',
                    'code'       => 'customer_name',
                    'input_type' => 'input',
                ),
                'customer_pwd'        => array(
                    'text'       => '商家密码',
                    'code'       => 'customer_pwd',
                    'input_type' => 'input',
                ),
                'month_code' => array(
                    'text'       => '身份证号',
                    'code'       => 'month_code',
                    'input_type' => 'input',
                ),
            ),
        );

        return isset($service[$cpCode]) ? $service[$cpCode] : array(
            'customer_name' => array(
                'text'       => '商家编码',
                'code'       => 'customer_name',
                'input_type' => 'input',
            ),
            'customer_pwd'  => array(
                'text'       => '商家密码',
                'code'       => 'customer_pwd',
                'input_type' => 'input',
            ),
        );
    }
}