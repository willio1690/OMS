<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_mdl_interfacefield_mapper extends dbeav_model
{
    /**
     * 根据渠道和场景获取映射配置
     * 
     * @param string $channel_id 渠道ID
     * @param string $channel_type 渠道类型
     * @param string $scene 场景
     * @return array|null
     */
    public function getMapperByChannel($channel_id, $channel_type, $scene = '')
    {
        $filter = array(
            'channel_id' => $channel_id,
            'channel_type' => $channel_type,
        );
        
        if ($scene) {
            $filter['scene'] = $scene;
        }
        
        return $this->db_dump($filter);
    }
    
    /**
     * 获取所有映射配置
     * 
     * @param string $channel_id 渠道ID
     * @param string $channel_type 渠道类型
     * @return array
     */
    public function getMappersByChannel($channel_id, $channel_type)
    {
        $filter = array(
            'channel_id' => $channel_id,
            'channel_type' => $channel_type,
        );
        
        return $this->getList('*', $filter);
    }
    
    /**
     * 保存映射配置
     * 
     * @param array $data 数据
     * @return bool
     */
    public function saveMapper($data)
    {
        if (isset($data['id']) && $data['id']) {
            return $this->save($data);
        } else {
            return $this->insert($data);
        }
    }
    
    /**
     * 删除映射配置
     * 
     * @param int $id 主键ID
     * @return bool
     */
    public function deleteMapper($id)
    {
        return $this->delete(array('id' => $id));
    }
} 