<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/7/14 10:05:46
 * @describe: 拆单规则抽象类
 * ============================
 */
abstract class omeauto_split_abstract {

    #拆单规则配置获取数据
    abstract public function getSpecial();

    #拆单规则保存前处理 return array(true, '保存成功')
    abstract public function preSaveSdf(&$sdf);

    #拆分订单
    abstract public function splitOrder(&$group, $splitConfig);

    /**
     * splitOrderFromSplit
     * @param mixed $arrOrder arrOrder
     * @param mixed $group group
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */

    public function splitOrderFromSplit(&$arrOrder, &$group, $splitConfig) {
        return array(true);
    }
}