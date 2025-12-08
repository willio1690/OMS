<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_operation_organization{

    /**
     * 获取OrgOps
     * @param mixed $org_id ID
     * @return mixed 返回结果
     */
    public function getOrgOps($org_id){
        if(!$org_id){
            return array();
        }

        $ops = array();
        $orgOpsObj = app::get('ome')->model("operation_ops");
        $orgOpsInfo = $orgOpsObj->getList('*', array('org_id' => $org_id), 0, -1);
        if($orgOpsInfo){
            foreach($orgOpsInfo as $opInfo){
                $ops[] = $opInfo['op_id'];
            }
            
            return $ops;
        }else{
            return array();
        }
    }

}