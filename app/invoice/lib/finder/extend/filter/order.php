<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class invoice_finder_extend_filter_order{
    
    function get_extend_colums(){
        $shopName = array_column(app::get('ome')->model('shop')->getList('name,shop_id'),'name','shop_id');

        $db = array(
            'order' => array(
                'columns' => array(
                    'shop_id' => array(
                        'type'          => $shopName,
                        'label'         => '来源店铺',
                        'width'         => 100,
                        'editable'      => false,
                        'in_list'       => true,
                        'filtertype'    => 'fuzzy_search_multiple',
                        'filterdefault' => true,
                    ),
                )
            )
        );
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if ($organization_permissions) {
            $orgRoles = array();
            $orgRolesObj = app::get('ome')->model('operation_organization');
            $orgRolesList = $orgRolesObj->getList('org_id,name', array('org_id' => $organization_permissions), 0, -1);
            if($orgRolesList){
                foreach($orgRolesList as $orgRole){
                    $orgRoles[$orgRole['org_id']] = $orgRole['name'];
                }
            }
            $db['order']['columns']['org_id|in'] = array('type' => $orgRoles, 'label' => '运营组织', 'editable' => false, 'width' => 60, 'filtertype' => 'normal', 'filterdefault' => true, 'in_list' => true, 'default_in_list' => true);
        }
        return $db;        
    }
    
}