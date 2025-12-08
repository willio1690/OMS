<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_kuaishou{

    /**
     * service_code
     * @param mixed $param param
     * @return mixed 返回值
     */
    public function service_code($param)
    {
        $cpCode  = $param['logistics'];
        $service = array(
            'ZTO'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'YUNDA'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'YTO'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'STO'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'HTKY'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'JT'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'FENGWANG'   => array(
                'NETWORK-CODING'=>array(
                    'text'       => '网点编码',
                    'code'       => 'NETWORK-CODING',
                    'input_type' => 'input',
                ),
                'NETWORK-NAME'=>array(
                    'text'       => '网点名称',
                    'code'       => 'NETWORK-NAME',
                    'input_type' => 'input',
                )
            ),
            'SF'   => array(
                'settleAccount'=>array(
                    'text'       => '客户编码',
                    'code'       => 'settleAccount',
                    'input_type' => 'input',
                ),
                'isvClientCode'=>array(
                    'text'       => '独立顾客编码',
                    'code'       => 'isvClientCode',
                    'input_type' => 'input',
                ),
                'expressProductCode'=>array(
                    'text'       => '物流编码类型',
                    'code'       => 'expressProductCode',
                    'input_type' => 'select',
                    'options'    => array(
                        '1' => '顺丰特快',
                        '2' => '顺丰标快',
                        '247' => '电商标快',
                        '111' => '顺丰干配',
                    )
                )
            ),
            'JD'   => array(
                'settleAccount'=>array(
                    'text'       => '客户编码',
                    'code'       => 'settleAccount',
                    'input_type' => 'input',
                ),
                'expressProductCode'=>array(
                    'text'       => '物流编码类型',
                    'code'       => 'expressProductCode',
                    'input_type' => 'select',
                    'options'    => array(
                        'ed-m-0001' => '特惠送',
                        'ed-m-0002' => '特快送',
                    )
                )
            ),
            'EMS'   => array(
                'settleAccount'=>array(
                    'text'       => '客户编码',
                    'code'       => 'settleAccount',
                    'input_type' => 'input',
                ),
            ),
            'POSTB'   => array(
                'settleAccount'=>array(
                    'text'       => '客户编码',
                    'code'       => 'settleAccount',
                    'input_type' => 'input',
                ),
            ),
        );
        return isset($service[$cpCode]) ? $service[$cpCode] : [];

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
            'template_name' => '快手',
            'shop_name' => '快手',
            'print_url' => 'https://docs.qingque.cn/d/home/eZQBMOMSj4mJ5D7Xplofq-p4Y?identityId=EmukFTnlEF',
            'template_url' => 'https://cloudprint.kwaixiaodian.com/page/fangzhou/customTemplateIsv',
            'shop_type' => 'kuaishou',
            'control_type' => 'kuaishou',
            'request_again' => false
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
            'ZTO'   => array('code'=>'ZTO','name'=>'中通速递'),
            'YUNDA'       => array('code'=>'YUNDA','name'=>'韵达快递'),
            'YTO'    => array('code'=>'YTO','name'=>'圆通快递'),
            'STO'    => array('code'=>'STO','name'=>'申通快递'),
            'HTKY'    => array('code'=>'HTKY','name'=>'百世快递'),
            'JT'  => array('code'=>'JT','name'=>'极兔快递'),
            // 'FENGWANG'  => array('code'=>'FENGWANG','name'=>'丰网速运'),
            'SF'  => array('code'=>'SF','name'=>'顺丰'),
            'POSTB'  => array('code'=>'POSTB','name'=>'邮政快递包裹'),
            'EMS'  => array('code'=>'EMS','name'=>'EMS'),
            'JD'  => array('code'=>'JD','name'=>'京东物流'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }
}