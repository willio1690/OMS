<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_pdd
{
    /**
     * template_cfg
     * @return mixed 返回值
     */
    public function template_cfg() {
        $arr = array(
            'template_name' => '拼多多',
            'shop_name' => '拼多多',
            'print_url' => 'http://meta.pinduoduo.com/api/one/app/v1/lateststable?appId=com.xunmeng.pddprint&platform=windows&subType=main',
            'template_url' => 'https://mms.pinduoduo.com/waybill',
            'shop_type' => 'pinduoduo',
            'control_type' => 'pdd',
            'request_again' => true,
            'template_type'=>array('pdd_standard','pdd_user'),
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
            'YTO'   => array('code'=>'YTO','name'=>'圆通速递','template_id'=>2),
            'ZTO'   => array('code'=>'ZTO','name'=>'中通快递','template_id'=>7),
            'STO'   => array('code'=>'STO','name'=>'申通快递','template_id'=>1),
            'YUNDA' => array('code'=>'YUNDA','name'=>'韵达速递','template_id'=>6),
            'HT'    => array('code'=>'HT','name'=>'百世快递','template_id'=>4),
            'TT'    => array('code'=>'TT','name'=>'天天快递','template_id'=>3),
            'YZXB'  => array('code'=>'YZXB','name'=>'邮政快递包裹','template_id'=>12),
            'AIR'   => array('code'=>'AIR','name'=>'亚风快运','template_id'=>14),
            'ZTOKY' => array('code'=>'ZTOKY','name'=>'中通快运'),
            'SDSD'  => array('code'=>'SDSD','name'=>'D速物流'),
            'SF'    => array('code'=>'SF','name'=>'顺丰快递'),
            'YS'    => array('code'=>'YS','name'=>'优速快递'),
            'GTO'   => array('code'=>'GTO','name'=>'国通快递'),
            'DB'    => array('code'=>'DB','name'=>'德邦快递'),
            'KYE'   => array('code'=>'KYE','name'=>'跨越速运'),
            'RRS'   => array('code'=>'RRS','name'=>'日日顺物流'),
            'SZKKE' => array('code'=>'SZKKE','name'=>'京广速递'),
            'YDKY'  => array('code'=>'YDKY','name'=>'韵达快运'),
            'OTP'   => array('code'=>'OTP','name'=>'承诺达特快'),
            'AXWL'  => array('code'=>'AXWL','name'=>'安迅物流'),
            'SXJD'  => array('code'=>'SXJD','name'=>'顺心捷达'),
            'JD'          => array('code'=>'JD','name'=>'京东快递'),
            'ZJS'         => array('code'=>'ZJS','name'=>'宅急送快递'),
            'BESTQJT'     => array('code'=>'BESTQJT','name'=>'百世快运'),
            'EMS'         => array('code'=>'EMS','name'=>'邮政EMS'),
            'HOAU'        => array('code'=>'HOAU','name'=>'天地华宇'),
            'SFKY'        => array('code'=>'SFKY','name'=>'顺丰快运'),
            'DEBANGWULIU' => array('code'=>'DEBANGWULIU','name'=>'德邦物流'),
            // 'LBEX'        => array('code'=>'LBEX','name'=>'龙邦快递(即将下线)'),
            'JIUYE'       => array('code'=>'JIUYE','name'=>'九曳供应链'),
            'EWE'         => array('code'=>'EWE','name'=>'EWE全球快递'),
            'XLOBO'       => array('code'=>'XLOBO','name'=>'贝海国际速递'),
            'JTSD'        => array('code'=>'JTSD','name'=>'极兔速递'),
            'YZDSBK'      => array('code'=>'YZDSBK','name'=>'邮政电商标快'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }
}
