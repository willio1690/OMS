<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class organization_view_input{

    function input_organization($params){
        
        $package = "mainOrganization";
        $objOrganizationSelect = kernel::single('organization_organizations_select');
        if(!$params['value']){
            //add new one load "请选择。。。"
            return '<span package="'.$package.'" class="span _x_ipt"><input type="hidden" name="'.$params['name'].'" />'.$objOrganizationSelect->get_organization_select(null,$params).'</span>';
        }else{
            $organizationObj = app::get('organization')->model('organization');
            list($package,$org_name,$org_id) = explode(':',$params['value']);
            $arr_org_name = explode("/", $org_name);
            $depth = count($arr_org_name);
            $arr_organizations = array();
            $ret = '';
            while($org_id && ($organization = $organizationObj->dump(array("org_id"=>$org_id),'org_id,org_name,parent_id'))){
                $params['depth'] = $depth--;
                array_unshift($arr_organizations,$organization);
                if($org_id = $organization['parent_id']){
                    $notice = "-";
                    $organization_org_id = $organization['org_id'];
                    if($params["org_id"] == $organization_org_id){
                        $organization_org_id = "";
                    }
                    $data = $objOrganizationSelect->get_organization_select($organization['parent_id'],$params,$organization_org_id);
                    if(!$data){
                        $notice = "";
                    }
                    $ret = '<span class="x-region-child">&nbsp;'.$notice.'&nbsp'.$objOrganizationSelect->get_organization_select($organization['parent_id'],$params,$organization_org_id).$ret.'</span>';
                }else{
                    $ret = '<span package="'.$package.'" class="span _x_ipt"><input type="hidden" value="'.$params["value"].'" name="'.$params['name'].'" />'.$objOrganizationSelect->get_organization_select(null,$params,$organization['org_id']).$ret.'</span>';
                }
            }
            if(!$ret){
                $ret = '<span package="'.$package.'" class="span _x_ipt"><input type="hidden" value="" name="'.$params['name'].'" />'.$objOrganizationSelect->get_organization_select(null,$params,$organization['org_id']).'</span>';
            }
            return $ret;
        }
        
    }
    
}
