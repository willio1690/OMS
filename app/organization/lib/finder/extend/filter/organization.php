<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class organization_finder_extend_filter_organization{
     function get_extend_colums(){

        $mdlOrganization = app::get('organization')->model('organization');
        //get org level arr
        $sql_str = "SELECT org_level_num FROM sdb_organization_organization WHERE del_mark<>1 and org_type=1 GROUP BY org_level_num";
        $result_rows = $mdlOrganization->db->select($sql_str);
        $arr_org_level_num_list = array();
         foreach ($result_rows as $var_item){
             $arr_org_level_num_list[$var_item['org_level_num']] = $var_item['org_level_num'];
         }
        
        $db['organization']=array (
            'columns' => array (
               'status' =>
                    array (
                    'type' => $mdlOrganization->org_status,
                    'editable' => false,
                    'label' => '状态选择',
                    'width' => 100,
                   'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'organization_finder_top',
                ),
               'org_level_num' =>
                    array (
                    'type' => $arr_org_level_num_list,
                    'editable' => false,
                    'label' => '组织层级选择',
                    'width' => 100,
                   'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'organization_finder_top',
                ),
            )
        );
        return $db;
     }
}
