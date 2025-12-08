<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_vopjitx
{
    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     * @return array
     */
    public function logistics($logistics_code = '') {
        $logistics = array (
            'annengwuliu' =>
                array (
                    'code' => 'annengwuliu',
                    'name' => '安能物流',
                ),
            'quanfengkuaidi' =>
                array (
                    'code' => 'quanfengkuaidi',
                    'name' => '全峰物流',
                ),
            'quanyikuaidi' =>
                array (
                    'code' => 'quanyikuaidi',
                    'name' => '全一物流',
                ),
            'rufengda' =>
                array (
                    'code' => 'rufengda',
                    'name' => '如风达快递',
                ),
            'shentong' =>
                array (
                    'code' => 'shentong',
                    'name' => '申通快递',
                ),
            'shunfeng' =>
                array (
                    'code' => 'shunfeng',
                    'name' => '顺丰速运',
                ),
            'suer' =>
                array (
                    'code' => 'suer',
                    'name' => '速尔快递',
                ),
            'tiantian' =>
                array (
                    'code' => 'tiantian',
                    'name' => '天天速递',
                ),
            'zhaijisong' =>
                array (
                    'code' => 'zhaijisong',
                    'name' => '宅急送',
                ),
            'zhongtiewuliu' =>
                array (
                    'code' => 'zhongtiewuliu',
                    'name' => '中铁快运',
                ),
            'zhongtong' =>
                array (
                    'code' => 'zhongtong',
                    'name' => '中通速递',
                ),
            'youshuwuliu' =>
                array (
                    'code' => 'youshuwuliu',
                    'name' => '优速物流',
                ),
            'longbanwuliu' =>
                array (
                    'code' => 'longbanwuliu',
                    'name' => '龙邦物流',
                ),
            'ztky' =>
                array (
                    'code' => 'ztky',
                    'name' => '中铁物流',
                ),
            'lianbangkuaidi' =>
                array (
                    'code' => 'lianbangkuaidi',
                    'name' => '联邦物流',
                ),
            'feibaokuaidi' =>
                array (
                    'code' => 'feibaokuaidi',
                    'name' => '飞豹物流',
                ),
            'yuantong' =>
                array (
                    'code' => 'yuantong',
                    'name' => '圆通速递',
                ),
            'youzhengguonei' =>
                array (
                    'code' => 'youzhengguonei',
                    'name' => '邮政快递',
                ),
            'yunda' =>
                array (
                    'code' => 'yunda',
                    'name' => '韵达快递',
                ),
            'guotongkuaidi' =>
                array (
                    'code' => 'guotongkuaidi',
                    'name' => '国通快递',
                ),
            'hengluwuliu' =>
                array (
                    'code' => 'hengluwuliu',
                    'name' => '恒路物流',
                ),
            'huitongkuaidi' =>
                array (
                    'code' => 'huitongkuaidi',
                    'name' => '百世快递',
                ),
            'jiajiwuliu' =>
                array (
                    'code' => 'jiajiwuliu',
                    'name' => '佳吉快运',
                ),
            'kuaijiesudi' =>
                array (
                    'code' => 'kuaijiesudi',
                    'name' => '快捷物流',
                ),
            'tiandihuayu' =>
                array (
                    'code' => 'tiandihuayu',
                    'name' => '天地华宇',
                ),
            'debangwuliu' =>
                array (
                    'code' => 'debangwuliu',
                    'name' => '德邦快递',
                ),
            'pjbest' =>
                array (
                    'code' => 'pjbest',
                    'name' => '品骏快递',
                ),
            'xinbangwuliu' =>
                array (
                    'code' => 'xinbangwuliu',
                    'name' => '新邦物流',
                ),
            'subida' =>
                array (
                    'code' => 'subida',
                    'name' => '速必达物流',
                ),
            'jd' =>
                array (
                    'code' => 'jd',
                    'name' => '京东快递',
                ),
            'rrs' =>
                array (
                    'code' => 'rrs',
                    'name' => '日日顺物流',
                ),
            'annto' =>
                array (
                    'code' => 'annto',
                    'name' => '安得物流',
                ),
            'ycgky' =>
                array (
                    'code' => 'ycgky',
                    'name' => '远成快运',
                ),
            'kuayue' =>
                array (
                    'code' => 'kuayue',
                    'name' => '跨越速运',
                ),
            'donghanwl' =>
                array (
                    'code' => 'donghanwl',
                    'name' => '东瀚物流',
                ),
            'ems' =>
                array (
                    'code' => 'ems',
                    'name' => 'EMS',
                ),
            'jiuyescm' =>
                array (
                    'code' => 'jiuyescm',
                    'name' => '九曳',
                ),
            'yizhitong' =>
                array (
                    'code' => 'yizhitong',
                    'name' => '一智通',
                ),
            'jujiatong' =>
                array (
                    'code' => 'jujiatong',
                    'name' => '居家通',
                ),
            'wowvip' =>
                array (
                    'code' => 'wowvip',
                    'name' => '沃埃家',
                ),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }
}