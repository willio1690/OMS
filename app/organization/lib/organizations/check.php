<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 企业组织结构数据验证Lib类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: check.php 2016-07-29 15:00
 */
class organization_organizations_check
{
    /**
     * 检查企业组织编码
     */

    public function check_org_no_exist($org_no, &$error_msg, $org_type = null)
    {
        $organizationObj = app::get('organization')->model('organization');
        
        // 如果是经销商类型(org_type=3)，需要添加BS_前缀来检查唯一性
        if ($org_type == 3) {
            $check_org_no = 'BS_' . $org_no;
        } else {
            $check_org_no = $org_no;
        }
        
        $rsinfo_org_no = $organizationObj->dump(array("org_no"=>$check_org_no),"org_id");
        if(empty($rsinfo_org_no)){
            return true;
        }else{
            $error_msg    = '该组织编码已经存在';
            return false;
        }
    }

    /**
     * 检查企业组织名称
     */
    public function check_org_name_exist($org_name, &$error_msg)
    {
//         $organizationObj = app::get('organization')->model('organization');
//         $rsinfo_org_name = $organizationObj->dump(array("org_name"=>$org_name),"org_id");
//         if(empty($rsinfo_org_name)){
//             return true;
//         }else{
//             $error_msg    = '该组织名称已经存在';
//             return false;
//         }

        //组织名称允许重复
        return true;
    }

    /**
     * 查询企业组织信息
     */
    public function get_one_org_info($org_id)
    {
        if(!$org_id){
            return false;
        }

        $organizationObj    = app::get('organization')->model('organization');
        return $organizationObj->dump(array("org_id"=>$org_id),"*");
    }

    /**
     * 检查组织结构层级
     */
    public function check_org_level($level_num, &$error_msg)
    {
        if(intval($level_num) > 5)
        {
            $error_msg    = '组织结构层级最多五层';
            return false;
        }

        return true;
    }

    /**
     * Post数据验证有效性
     * 
     * @param  Array   $params
     * @param  String  $error_msg
     * @return Boolean
     */
    public function checkParams(&$params, &$err_msg)
    {
        $chk_err_msg    = '';

        $check_org_no_exist    = $this->check_org_no_exist($params['org_no'], $chk_err_msg);
        if(!$check_org_no_exist)
        {
            $err_msg    = $chk_err_msg;
            return false;
        }

        $check_org_name_exist    = $this->check_org_name_exist($params['org_name'], $chk_err_msg);
        if(!$check_org_name_exist)
        {
            $err_msg    = $chk_err_msg;
            return false;
        }

        //insert start
        $current_int_time = time();
        $insert_arr = array(
                'org_no' => $params["org_no"],
                'org_name' => $params["org_name"],
                'create_time' => $current_int_time,
                'org_type' => $params["org_type"] > 0 ? $params["org_type"] : 1,
        );

        //新增组织 确定 org_level_num\parent_id\parent_no
        if($params['organizationSelected'])
        {
            //所属上级 ：有选择
            list($package,$org_name,$org_id) = explode(':', $params["organizationSelected"]);
            if(!$org_id){
                $err_msg    = '所属上级层级不存在';
                return false;
            }
            $insert_arr["parent_id"] = $org_id;

            $current_org_info    = $this->get_one_org_info($org_id);
            if(empty($current_org_info))
            {
                $err_msg    = '所属上父级不存在';
                return false;
            }

            $insert_arr['org_level_num'] = intval($current_org_info["org_level_num"])+1;//新增层级是上级层级加1
            $insert_arr['parent_no'] = $current_org_info["org_no"];
            $insert_arr["org_parents_structure"] = $params["organizationSelected"];
        }
        else
        {
            //所属上级 ：无选择 parent_id\parent_no 都为空
            $insert_arr["org_level_num"] = 1;
            $insert_arr["parent_id"] = 0; // 没有上级给0，null不好处理
        }

        //目前组织结构最大五层层级
        $chk_org_level    = $this->check_org_level($insert_arr['org_level_num'], $chk_err_msg);
        if(!$chk_org_level)
        {
            $err_msg    = $chk_err_msg;
            return false;
        }

        if($params["status"] == 1){
            $insert_arr["recently_enabled_time"] = $current_int_time;
            $insert_arr["first_enable_time"] = $current_int_time;
            $insert_arr["status"] = 1;
            $doWhat = "doActive";
        }else{
            $insert_arr['recently_stopped_time'] = $current_int_time;
            $insert_arr["status"] = 2;
            $doWhat = "doUnactive";
        }

        $insert_arr['doWhat']    = $doWhat;

        return $insert_arr;
    }
}
