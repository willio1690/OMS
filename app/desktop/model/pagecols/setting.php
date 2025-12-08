<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_mdl_pagecols_setting extends dbeav_model
{
    /**
     * 获取字段配置
     * @param string $tbl_name 表名
     * @param string $col_key 字段键
     * @return array|null
     */
    public function getFieldConfig($tbl_name, $col_key)
    {
        $filter = array(
            'tbl_name' => $tbl_name,
            'col_key' => $col_key,
        );
        
        return $this->dump($filter);
    }
    
    /**
     * 获取表的所有字段配置
     * @param string $tbl_name 表名
     * @return array
     */
    public function getTableConfigs($tbl_name)
    {
        $filter = array('tbl_name' => $tbl_name);
        return $this->getList('*', $filter);
    }
    
    /**
     * 检查字段是否必填
     * @param string $tbl_name 表名
     * @param string $col_key 字段键
     * @return bool
     */
    public function isFieldRequired($tbl_name, $col_key)
    {
        $config = $this->getFieldConfig($tbl_name, $col_key);
        return $config && $config['is_required'] == '1';
    }
    
    /**
     * 获取字段默认值
     * @param string $tbl_name 表名
     * @param string $col_key 字段键
     * @return string|null
     */
    public function getFieldDefaultValue($tbl_name, $col_key)
    {
        $config = $this->getFieldConfig($tbl_name, $col_key);
        return $config ? $config['default_value'] : null;
    }
    
    /**
     * 保存字段配置
     * @param array $data 配置数据
     * @return bool
     */
    public function saveFieldConfig($data)
    {
        $filter = array(
            'tbl_name' => $data['tbl_name'],
            'col_key' => $data['col_key'],
        );
        
        $existing = $this->dump($filter);
        
        if ($existing) {
            // 更新现有配置
            $data['id'] = $existing['id'];
            return $this->save($data);
        } else {
            // 创建新配置
            return $this->insert($data);
        }
    }
} 