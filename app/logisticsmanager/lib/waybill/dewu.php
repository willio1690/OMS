<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_dewu
{
    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     * @return array
     */
    public function logistics($logistics_code = '')
    {
        $logistics = array(
            'VIRTUAL' => array(
                'code' => 'VIRTUAL',
                'name' => '虚拟物流',
            ),
            'RRS'         => [
                'code' => 'RRS',
                'name' => '日日顺',
            ],
            'AD'          => [
                'code' => 'AD',
                'name' => '安得物流',
            ],
            'AX'          => [
                'code' => 'AX',
                'name' => '安迅物流',
            ],
            'SN'          => [
                'code' => 'SN',
                'name' => '苏宁物流',
            ],
            'HX'          => [
                'code' => 'HX',
                'name' => '海信物流',
            ],
            'EMS'         => [
                'code' => 'EMS',
                'name' => '中国邮政',
            ],
            'SF'          => [
                'code' => 'SF',
                'name' => '顺丰',
            ],
            'JD'          => [
                'code' => 'JD',
                'name' => '京东',
            ],
            'DB'          => [
                'code' => 'DB',
                'name' => '德邦',
            ],
            'ZT'          => [
                'code' => 'ZT',
                'name' => '中通',
            ],
            'YD'          => [
                'code' => 'YD',
                'name' => '韵达快递',
            ],
            'ST'          => [
                'code' => 'ST',
                'name' => '申通快递',
            ],
            'JT'          => [
                'code' => 'JT',
                'name' => '极兔快递',
            ],
            'YT'          => [
                'code' => 'YT',
                'name' => '圆通快递',
            ],
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }

    /**
     * 打印组件三个接口
     * 获取打印数据 /logistics/waybillApply
     * 获取标准模板 /logistics/templateList
     * 获取自定义模板 /logistics/customTemplateList
     * @return array [description]
     */
    public function template_cfg()
    {
        $arr = array(
            'template_name' => '得物品牌直发',
            'template_name_2' => '得物自研',
            'shop_name'     => '得物品牌直发',
            'print_url'     => 'https://cdn.poizon.com/node-common/986e39b7-5714-9d26-aa05-db9d501f9d57.zip',
            'print_url_2'   => 'https://h5static.dewu.com/print-app/client/win/%E5%BE%97%E7%89%A9%E6%89%93%E5%8D%B0%20Setup%201.2.2.exe',
            'template_url'  => '',
            'shop_type'     => 'dewu',
            'control_type'  => 'dewu',
            'request_again' => true,
        );
        return $arr;
    }
}
