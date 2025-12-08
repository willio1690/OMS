<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/7/14 10:03:32
 * @describe: 出入口类
 * ============================
 */
class omeauto_split_router extends omeauto_split_abstract
{
    private $obj;

    /**
     * __construct
     * @param mixed $splitType splitType
     * @return mixed 返回值
     */

    public function __construct($splitType)
    {
        try {
            $objName = 'omeauto_split_' . $splitType;
            if (class_exists($objName)) {
                $this->obj = kernel::single($objName);
            }
        } catch (Exception $e) {}
    }

    #拆单规则配置获取数据
    /**
     * 获取Special
     * @return mixed 返回结果
     */
    public function getSpecial()
    {
        $data = array();
        if ($this->obj) {
            $data = $this->obj->getSpecial();
        }
        return $data;
    }

    #拆单规则保存前处理
    /**
     * preSaveSdf
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function preSaveSdf(&$sdf)
    {
        $data = array(false, '缺少类');
        if ($this->obj) {
            $data = $this->obj->preSaveSdf($sdf);
        }
        return $data;
    }

    #拆分订单
    /**
     * splitOrder
     * @param mixed $group group
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */
    public function splitOrder(&$group, $splitConfig)
    {
        $data = array(false, '缺少类');
        if ($this->obj) {
            $data = $this->obj->splitOrder($group, $splitConfig);
        }
        return $data;
    }

    #再次拆分订单
    /**
     * splitOrderFromSplit
     * @param mixed $arrOrder arrOrder
     * @param mixed $group group
     * @param mixed $splitConfig 配置
     * @return mixed 返回值
     */
    public function splitOrderFromSplit(&$arrOrder, &$group, $splitConfig)
    {
        $data = array(false, '缺少类');
        if ($this->obj) {
            $splitConfig['from'] = 'split';
            $data = $this->obj->splitOrderFromSplit($arrOrder, $group, $splitConfig);
        }
        return $data;
    }
}
