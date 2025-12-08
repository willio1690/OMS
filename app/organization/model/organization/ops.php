<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店组织架构管理员关联模型
 * 用于管理操作员与组织架构的权限关系
 */
class organization_mdl_organization_ops extends dbeav_model {
    
    /**
     * 获取用户的组织权限列表
     * @param int $op_id 操作员ID
     * @return array 组织ID数组
     */
    public function getUserOrganizations($op_id) {
        if (empty($op_id)) {
            return [];
        }
        
        $result = $this->dump(['op_id' => $op_id], 'org_ids');
        
        if (!$result || empty($result['org_ids'])) {
            return [];
        }
        
        $orgIds = explode(',', $result['org_ids']);
        return array_filter(array_map('trim', $orgIds));
    }
    
    /**
     * 保存用户的组织权限
     * @param int $op_id 操作员ID
     * @param array $org_ids_array 组织ID数组
     * @return bool 操作结果
     */
    public function saveUserOrganizations($op_id, $org_ids_array) {
        if (empty($op_id)) {
            return false;
        }
        
        // 删除旧的组织权限
        $this->delete(['op_id' => $op_id]);
        
        // 保存新的组织权限
        if (!empty($org_ids_array)) {
            // 过滤空值并去重
            $org_ids_array = array_unique(array_filter($org_ids_array));
            $org_ids_str = implode(',', $org_ids_array);
            
            $data = array(
                'op_id' => $op_id,
                'org_ids' => $org_ids_str
            );
            
            return $this->insert($data);
        }
        
        return true;
    }
    
    /**
     * 获取拥有指定组织权限的所有操作员
     * @param int $org_id 组织ID
     * @return array 操作员列表
     */
    public function getOperatorsByOrganization($org_id) {
        if (empty($org_id)) {
            return [];
        }
        
        $sql = "SELECT op_id, org_ids FROM {$this->table} WHERE FIND_IN_SET(?, org_ids) > 0";
        
        return $this->db->select($sql, [$org_id]);
    }
    
    /**
     * 为指定操作员添加组织权限
     * @param int $op_id 操作员ID
     * @param int $org_id 组织ID
     * @return bool 操作结果
     */
    public function addOrganizationToUser($op_id, $org_id) {
        if (empty($op_id) || empty($org_id)) {
            return false;
        }
        
        $existingOrgs = $this->getUserOrganizations($op_id);
        
        if (!in_array($org_id, $existingOrgs)) {
            $existingOrgs[] = $org_id;
            return $this->saveUserOrganizations($op_id, $existingOrgs);
        }
        
        return true;
    }
    
    /**
     * 移除指定操作员的组织权限
     * @param int $op_id 操作员ID
     * @param int $org_id 组织ID
     * @return bool 操作结果
     */
    public function removeOrganizationFromUser($op_id, $org_id) {
        if (empty($op_id) || empty($org_id)) {
            return false;
        }
        
        $existingOrgs = $this->getUserOrganizations($op_id);
        $key = array_search($org_id, $existingOrgs);
        
        if ($key !== false) {
            unset($existingOrgs[$key]);
            return $this->saveUserOrganizations($op_id, array_values($existingOrgs));
        }
        
        return true;
    }
    
    /**
     * 检查操作员是否有指定组织的权限
     * @param int $op_id 操作员ID
     * @param int $org_id 组织ID
     * @return bool 是否有权限
     */
    public function hasOrganizationPermission($op_id, $org_id) {
        if (empty($op_id) || empty($org_id)) {
            return false;
        }
        
        $userOrgs = $this->getUserOrganizations($op_id);
        return in_array($org_id, $userOrgs);
    }
}