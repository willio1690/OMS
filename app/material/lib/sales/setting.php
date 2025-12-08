<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料页面配置服务
 * Class material_sales_setting
 */
class material_sales_setting
{
    /**
     * 获取销售物料页面配置
     * @param string $type 配置类型
     * @return array
     */
    public function get_pagecols_setting($type = '')
    {
        $predefinedScenes = array(
            'material_sales_material' => array(
                'name'     => '新建销售物料',
                'elements' => array(
                    'price_rate' => array(
                        'name' => '价格贡献比分摊方式',
                        'options' => array(
                            'retail_price' => '按零售价',
                            'cost' => '按成本价',
                        ),
                        'default' => 'retail_price'
                    ),
                )
            ),
            'material_basic_material' => array(
             
                'name'     => '新建基础物料',
                'elements' => array(
                    'unit' => array(
                        'name' => '包装单位',
                        'options' => array(),
                        'default' => '',
                        'is_required'=>'1',
                    )
                )
            ),
        );
        return $predefinedScenes[$type] ?? $predefinedScenes;
    }
    
    /**
     * 获取价格字段名称
     * @return string 价格字段名称：'cost' 或 'retail_price'
     */
    public function getPriceField()
    {
        $priceRate = $this->getConfig('price_rate', 'retail_price');
        return ($priceRate == 'cost') ? 'cost' : 'retail_price';
    }
    
    /**
     * 获取价格字段显示名称
     * @return string 价格字段显示名称：'成本价' 或 '零售价'
     */
    public function getPriceLabel()
    {
        $priceRate = $this->getConfig('price_rate', 'retail_price');
        return ($priceRate == 'cost') ? '成本价' : '零售价';
    }
    
    /**
     * 获取价格配置信息
     * @return array 包含price_rate、price_field、price_label的数组
     */
    public function getPriceConfig()
    {
        $priceRate = $this->getConfig('price_rate', 'retail_price');
        return array(
            'price_rate' => $priceRate,
            'price_field' => $this->getPriceField(),
            'price_label' => $this->getPriceLabel(),
        );
    }
    
    /**
     * 获取指定配置项的值
     * @param string $configKey 配置键名
     * @param mixed $defaultValue 默认值
     * @return mixed 配置值
     */
    public function getConfig($configKey, $defaultValue = null)
    {
        try {
            $pageConfigModel = app::get('desktop')->model('pagecols_setting');
            if ($pageConfigModel) {
                $config = $pageConfigModel->getFieldConfig('material_sales_material', $configKey);
                if ($config && isset($config['default_value'])) {
                    return $config['default_value'];
                }
            }
        } catch (Exception $e) {
            // 如果获取配置失败，使用默认值
        }
        
        return $defaultValue;
    }
    
    /**
     * 获取所有配置信息
     * @return array 所有配置项的数组
     */
    public function getAllConfig()
    {
        $configs = array();
        
        // 价格相关配置
        $priceConfig = $this->getPriceConfig();
        $configs = array_merge($configs, $priceConfig);
        
        // 可以在这里添加其他配置项
        // 例如：$configs['other_config'] = $this->getConfig('other_config', 'default_value');
        
        return $configs;
    }
} 