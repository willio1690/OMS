<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class organization_ctl_admin_management extends desktop_controller {

    var $workground = "goods_manager";

    function _views(){
        $organizationObj = app::get('organization')->model("organization");
        $sub_menu = array(
            0 => array(
                    'label'=>app::get('base')->_('当前可用组织'),
                    'filter'=>array(
                        'del_mark' => 0,
                        'org_type' => 1,
                    ),
                    'optional'=>false,
            ),
            1 => array(
                    'label'=>app::get('base')->_('已删除组织'),
                    'filter'=>array(
                        'del_mark' => 1,
                        'org_type' => 1,
                    ),
                    'optional'=>false
            ),
        );
        
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = $v['filter'];
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $organizationObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=organization&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }
    
    function index(){
        $this->title = '结构管理';
        $this->action = array(
                array('label'=>'新增组织','href'=>'index.php?app=organization&ctl=admin_management&act=addGropItem','target'=>"dialog::{width:550,height:400,resizeable:false,title:'新增组织'}"),
                array(
                        'label' => '导出模板',
                        'href' => 'index.php?app=organization&ctl=admin_management&act=exportTemplate',
                        'target' => '_blank',
                ),
        );
        
        $base_filter = array();
        $base_filter["del_mark"] = 0;
        $base_filter['org_type'] = 1;
        if(isset($_GET["view"])){
            $base_filter["del_mark"] = intval($_GET["view"]);
        }
        
        //过滤状态选择 类别头部筛选
        if(isset($_POST['status'])){
            $base_filter['status'] = $_POST['status'];
        }
        //过滤组织层级选择 类别头部筛选
        if(isset($_POST['org_level_num'])){
            $base_filter['org_level_num'] = $_POST['org_level_num'];
        }
        
        $params = array(
                'title'=>$this->title,
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>true,
                'base_filter'=>$base_filter,
                'orderBy'=>'org_id ASC',
                'actions'=>$this->action,
        );
        
        //top filter
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('organization_finder_top');
            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            $panel->show('organization_mdl_organization', $params);
        }
        
        //finder
        $this->finder('organization_mdl_organization', $params);
        
    }
    
    //展示页面
    function show(){
        $obj_organizations_op = kernel::single('organization_operation');
        $this->pagedata['organization'] = $obj_organizations_op->getGropById('',1);
        $this->page('admin/organization_treeList.html');
    }
    
    //展示页面获取下级层级
    /**
     * 获取ChildNode
     * @return mixed 返回结果
     */
    public function getChildNode(){
        $obj_organizations_op = kernel::single('organization_operation');
        $this->pagedata['organization'] = $obj_organizations_op->getGropById($_POST['orgId'],1);
        $this->display('admin/organization_sub_treeList.html');
    }
    
    function editGropItem(){
        $org_id = intval($_GET["org_id"]);
        $this->pagedata["organization_item_action_url"] = "index.php?app=organization&ctl=admin_management&org_id=$org_id&act=doEditGropItem";
        $organizationObj = $this->app->model('organization');
        $row_record = $organizationObj->dump(array("org_id"=>$org_id),"*");
        $this->pagedata["org_info"] = $row_record;
        //门店绑定显示
//         if($row_record['org_type'] == 2){
//             $organizationStoreObj = $this->app->model('organization_store');
//             $bind_store = $organizationStoreObj->dump(array("rowid"=>$rowid),"*");
//             $this->pagedata["bind_store"] = $bind_store;
//         }
        //后续补充逻辑，当选择是门店类型后，支持线下店铺、虚拟仓有相应记录了，不允许修改组织类型
        
        if(isset($_GET["from_page_act"]) && $_GET["from_page_act"] == "org_show"){
            //展示页面
            $this->display('admin/show_page_org_management.html');
        }else{
            //管理页面
            $this->display('admin/management.html');
        }
        
    }
    
    function doEditGropItem(){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        if(!isset($_GET["org_id"])){
            $this->end(false,'该组织不存在');
        }
        
        $data = $_POST;
        
        //check
        $orgCheckLib    = kernel::single('organization_organizations_check');
        $error_msg      = '';
        
        //get current record 
        $organizationObj = $this->app->model('organization');
        $current_record = $organizationObj->dump(array("org_id"=>intval($_GET["org_id"])),"*");
        
        //update start
        $current_int_time = time();
        $update_arr = array();
        $needUpdateHigherlowidsnames = false;    
        
        //check org_no and org_name exist
        if($current_record["org_no"] != $data["org_no"]){
            $check_org_no_exist    = $orgCheckLib->check_org_no_exist($data["org_no"], $error_msg);
            if(!$check_org_no_exist){
                $this->end(false, $error_msg);
            }else{
                $update_arr["org_no"] = $data["org_no"];
                $needUpdateHigherlowidsnames = true;
            }
        }
        if($current_record["org_name"] != $data["org_name"]){
            $check_org_name_exist    = $orgCheckLib->check_org_name_exist($data["org_name"], $error_msg);
            if(!$check_org_name_exist){
                $this->end(false, $error_msg);
            }else{
                $update_arr["org_name"] = $data["org_name"];
                $needUpdateHigherlowidsnames = true;
            }
        }
        
        //if change status
        if(intval($current_record["status"]) == intval($data["status"])){
        }else{
            if($data["status"] == 1){
                $update_arr["recently_enabled_time"] = $current_int_time;
                if(!$current_record["first_enable_time"]){
                    $update_arr["first_enable_time"] = $current_int_time;
                }
                $update_arr["status"] = 1;
            }else{
                $update_arr['recently_stopped_time'] = $current_int_time;
                $update_arr["status"] = 2;
            }
        }
        
        //get update org_level_num/parent_id/parent_no
        //所属上级是否有选择
        if(!$data["organizationSelected"]){
            //判断原始层级是否有父层级
            if(intval($current_record["parent_id"])>0){
                //如果有 说明所属上级有变动 更新为最上级的层级
                $update_arr["parent_id"] = 0;
                $update_arr["parent_no"] = "";
                $update_arr["org_level_num"] = 1;
                $update_arr["org_parents_structure"] = "";
                $needUpdateHigherlowidsnames = true;
                $arr_update_haschild = array(
                    "parent_id" => 0,      
                    "old_parent_id" => $current_record["parent_id"]
                );
            }
        }else{
            //所属上级是否有选择 获取parent_id 判断是否有做修改
            list($package,$org_name,$org_id) = explode(':',$data["organizationSelected"]);
            $org_id = $org_id?$org_id:0;
            
            #防止所属上级选择自己
            if($current_record['org_id'] == intval($org_id))
            {
                $this->end(false,'所属上级不能选择自己');
            }
            
            if(intval($current_record['parent_id']) == intval($org_id)){
                //未修改所属上级
                $update_arr["parent_id"] = $current_record['parent_id'];
            }else{
                $update_arr["parent_id"] = $org_id;
                $parent_org_info    = $orgCheckLib->get_one_org_info($org_id);
                
                $update_arr['org_level_num'] = intval($parent_org_info["org_level_num"])+1;
                $update_arr['parent_no'] = $parent_org_info['org_no'];
                $update_arr["org_parents_structure"] = $data["organizationSelected"];
                $needUpdateHigherlowidsnames = true;
                $arr_update_haschild = array(
                        "parent_id" => $org_id,
                        "old_parent_id" => $current_record["parent_id"]
                );
            }
        }
        
        if($needUpdateHigherlowidsnames){
            if(intval($current_record["haschild"]) == 1){
                $this->end(false,'存在下级层级 不能修改上级层级');
            }
            $update_arr["org_no"] = $data["org_no"];
            $update_arr["org_name"] = $data["org_name"];
            //first: add new lowerids names into new higherlevel's lowids names
            $this->updateParentNosAndNames("add_loweridsnames",$update_arr);
            //second: remove old highlevel's lowerids names
            $this->updateParentNosAndNames("del",$current_record);
        }
        
        $filter = array(
                'org_id' => intval($_GET["org_id"])
        );
        $update_arr["org_type"] = $data["org_type"] > 0 ? $data["org_type"] : 1;
        //更新当前组织架构层级信息
        $organizationObj->update($update_arr,$filter);
        
        //编辑 如果修改了组织层级 需要更新 haschild字段
        if(!empty($arr_update_haschild)){
            $this->update_haschild($arr_update_haschild['parent_id'],$arr_update_haschild['old_parent_id']);
        }
        
        //更新门店类型的组织架构相关信息
//         $this->_saveOrgStoreInfo($data);
        
        $this->end(true,'更新成功');
    }
    
    function addChildGropItem(){
        $org_id = intval($_GET["org_id"]);
        $this->pagedata["organization_item_action_url"] = "index.php?app=organization&ctl=admin_management&org_id=$org_id&act=doAddChildGropItem";
        $organizationObj = $this->app->model('organization');
        $row_record = $organizationObj->dump(array("org_id"=>$org_id),"org_id,org_name,org_parents_structure,org_level_num");
        //获取当前组织做为所属上级
        if(!$row_record["org_parents_structure"] && intval($row_record["org_level_num"]) == 1){
            //此为最上层级 org_level_num是1
            $row_record["org_parents_structure"] = 'mainOrganization:'.$row_record['org_name'].':'.$row_record['org_id'];
        }else{
            list($package,$org_name,$org_id) = explode(':',$row_record["org_parents_structure"]);
            $row_record["org_parents_structure"] = $package.':'.$org_name."/".$row_record['org_name'].':'.$row_record['org_id'];
        }
        $this->pagedata["org_info"]["org_parents_structure"] = $row_record["org_parents_structure"];
        $this->display('admin/show_page_org_management.html');
    }
    
    function doAddChildGropItem(){
        $this->begin('');
        $data = $_POST;
        
        //check
        $orgCheckLib    = kernel::single('organization_organizations_check');
        $error_msg      = '';
        
        //check exist
        $check_org_no_exist    = $orgCheckLib->check_org_no_exist($data["org_no"], $error_msg);
        if(!$check_org_no_exist){
            $this->end(false, $error_msg);
        }
        
        $check_org_name_exist    = $orgCheckLib->check_org_name_exist($data["org_name"], $error_msg);
        if(!$check_org_name_exist){
            $this->end(false, $error_msg);
        }
        
        if(!$data["organizationSelected"]){
            $this->end(false,'未选中上级');
        }
        
        //insert start
        $current_int_time = time();
        $insert_arr = array(
                'org_no' => $data["org_no"],
                'org_name' => $data["org_name"],
                'create_time' => $current_int_time,
                'org_type' => $data["org_type"] > 0 ? $data["org_type"] : 1,
        );
        
        
        list($package,$org_name,$org_id) = explode(':',$data["organizationSelected"]);
        if(!$org_id){
            $this->end(false,'所属上级层级不存在');
        }
        $insert_arr["parent_id"] = $org_id;
        
        $current_org_info    = $orgCheckLib->get_one_org_info($org_id);
        
        $insert_arr['org_level_num'] = intval($current_org_info["org_level_num"])+1;//新增层级是上级层级加1
        $insert_arr['parent_no'] = $current_org_info["org_no"];
        $insert_arr["org_parents_structure"] = $data["organizationSelected"];
        
        //目前组织结构最大五层层级
        $chk_org_level    = $orgCheckLib->check_org_level($insert_arr['org_level_num'], $error_msg);
        if(!$chk_org_level)
        {
            $this->end(false, $error_msg);
        }
        
        if($data["status"] == 1){
            $insert_arr["recently_enabled_time"] = $current_int_time;
            $insert_arr["first_enable_time"] = $current_int_time;
            $insert_arr["status"] = 1;
            $doWhat = "doActive";
        }else{
            $insert_arr['recently_stopped_time'] = $current_int_time;
            $insert_arr["status"] = 2;
            $doWhat = "doUnactive";
        }
        
        $this->updateParentNosAndNames($doWhat,$insert_arr);
        
        //update field haschild
        if($insert_arr["parent_id"]){
            $this->update_haschild($insert_arr["parent_id"]);
        }
        
        //insert new record
        $organizationObj = $this->app->model('organization');
        $organizationObj->insert($insert_arr);
        
        //新增门店类型的组织架构相关信息
//         $data['rowid'] = $insert_arr['rowid'];
//         $this->_saveOrgStoreInfo($data);
        
        $this->end(true,'增加成功');
        
    }
    
    function addGropItem(){
        $this->pagedata["organization_item_action_url"] = "index.php?app=organization&ctl=admin_management&act=doAddGropItem";
        $this->display('admin/management.html');
    }
    
    function get_add_edit_html_select_options(){
        $this->pagedata["select_data"] = array(
                "0" => array(
                        "org_level_num" => 0,
                        "org_name" => "选择上级组织（ID+名称）"
                )
        );
        $organizationObj = $this->app->model('organization');
        $result_rows = $organizationObj->getList("org_id,org_name,org_level_num",array("status"=>1,"del_mark"=>0));
        if(!empty($result_rows)){
            foreach ($result_rows as $var_row){
                $id_plus_name = $var_row["org_id"]." + ".$var_row["org_name"];
                $temp_arr= array(
                        "org_level_num" => $var_row["org_level_num"],
                        "org_name" => $id_plus_name,
                );
                $this->pagedata["select_data"][$var_row["org_id"]] = $temp_arr;
            }
        }
    }

    function doAddGropItem(){
        $this->begin('index.php?app=organization&ctl=admin_management&act=index');
        $data = $_POST;
        
        //check exist
        $err_msg        = '';
        $orgCheckLib    = kernel::single('organization_organizations_check');
        $insert_arr     = $orgCheckLib->checkParams($data, $err_msg);
        if(!$insert_arr)
        {
            $this->end(false, $err_msg);
        }
        
        $doWhat    = $insert_arr['doWhat'];
        unset($insert_arr['doWhat']);
        
        $this->updateParentNosAndNames($doWhat,$insert_arr);
        
        //update field haschild
        if(isset($insert_arr["parent_id"])){
            $this->update_haschild($insert_arr["parent_id"]);
        }
        
        //insert new record
        $organizationObj = $this->app->model('organization');
        $organizationObj->insert($insert_arr);

        //新增门店类型的组织架构相关信息
//         $data['rowid'] = $insert_arr['rowid'];
//         $this->_saveOrgStoreInfo($data);
        
        $this->end(true,'增加成功');
    }
    
    function update_haschild($parent_id,$old_parent_id=0){
        $organizationObj = $this->app->model('organization');
        if(intval($parent_id) > 0){
            $p_org_info = $organizationObj->dump(array("org_id"=>$parent_id),"haschild");
            $child_arr = $organizationObj->getList("org_id",array("parent_id"=>$parent_id,'org_type'=>1,'del_mark'=>0), 0, -1);
            if(count($child_arr) > 0){
                $org_p_haschild['haschild'] = $p_org_info['haschild'] | 1;
            }else{
                $org_p_haschild['haschild'] = $p_org_info['haschild'] ^ 1;
            }
            $organizationObj->update($org_p_haschild,array('org_id'=>$parent_id));
        }
        if(intval($old_parent_id) > 0){
            $p_old_org_info = $organizationObj->dump(array("org_id"=>$old_parent_id),"haschild");
            $child_arr = $organizationObj->getList("org_id",array("parent_id"=>$old_parent_id,'org_type'=>1,'del_mark'=>0), 0, -1);
            if(count($child_arr) > 0){
                $org_p_haschild['haschild'] = $p_old_org_info['haschild'] | 1;
            }else{
                $org_p_haschild['haschild'] = $p_old_org_info['haschild'] ^ 1;
            }
            $organizationObj->update($org_p_haschild,array('org_id'=>$old_parent_id));
        }
    }
    


    function doDelGropItem(){
        $this->common_update_status("del");
    }
    
    function doActiveGropItem(){
        $this->common_update_status("doActive");
    }
    
    function doUnactiveGropItem(){
        $this->common_update_status("doUnactive");
    }
    
    function common_update_status($action_type){
        $this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
        
        $organizationObj = $this->app->model('organization');
        
        $fail_message = '修改状态失败';
        if(!$_GET["org_id"] || !is_numeric($_GET["org_id"]) || !$action_type){
            $this->end(false, $fail_message);
        }
        
        $filter = array(
            'org_id' => intval($_GET["org_id"])
        );
        
        $current_record=$organizationObj->dump($filter,"*");
        if(empty($current_record)){
            $this->end(false, $fail_message);
        }
        
        switch ($action_type){
            case "del" :
                //判断此组织架构是否存在门店关联信息 存在不能删除
                /*
                $organizationStoreObj = $this->app->model('organization_store');
                $org_store_related = $organizationStoreObj->dump(array("org_id"=>$current_record["org_id"]),"org_id");
                if(!empty($org_store_related)){
                    $this->end(false, '不能删除，此组织层级存在门店关联信息');
                }
                */
                $rs_useful_lower_level = $this->check_useful_lower_level_exist($current_record["org_id"]);
                if($rs_useful_lower_level){
                    $this->end(false, "有以下".$rs_useful_lower_level."组织尚未解除组织关系 请解除后再删除");
                }
                $updateData = array(
                    "del_mark" => 1, 
                    "del_time" => time(), 
                );
                $this->updateParentNosAndNames($action_type, $current_record);
                break;
            case "doActive" :
                $current_int_time = time();
                $updateData = array(
                        "status" => 1,
                        "recently_enabled_time" => $current_int_time,
                );
                if(!$current_record["first_enable_time"]){
                    $updateData["first_enable_time"] = $current_int_time;
                }
                break;
            case "doUnactive" :
                $rs_childs_active_exist = $this->check_childs_active_exist($current_record["org_id"]);
                if($rs_childs_active_exist){
                    $this->end(false, "该组织下存在启用的组织层级或门店 不能进行禁用操作");
                }
                $updateData = array(
                    "status" => 2,
                    "recently_stopped_time" => time(),
                );
                break;
        }
        
        $rs_update = $organizationObj->update($updateData,$filter);
        if($rs_update){
            //如是del操作 在删除之后 更新上级字段haschild
            if($action_type == 'del'){
                $this->update_haschild($current_record["parent_id"]);
            }
            $this->end(true, '修改状态成功');
        }else{
            $this->end(false, $fail_message);
        }
        
    }
    
    //删除是check下一级是否有可用组织层级
    function check_useful_lower_level_exist($org_id){
        $organizationObj = $this->app->model('organization');
        $result_lower_level=$organizationObj->getList("org_no", array("parent_id"=>$org_id,"del_mark"=>0));
        if(empty($result_lower_level)){
            return false;
        }else{
            $arr_result_lower_level = array();
            foreach ($result_lower_level as $var_lower_level){
                $arr_result_lower_level[] = $var_lower_level['org_no'];
            }
            return implode(",",$arr_result_lower_level);
        }
    }
    
    //停用是check所有下级是否有启用状态 不管是门店还是组织层级
    function check_childs_active_exist($org_id){
        $organizationObj = $this->app->model('organization');
        $return_result = false;
        //默认第一次获得下级org信息的父类org_id只有一个 
        $org_ids = array($org_id);
        do{
            $lower_orginfo = $organizationObj->getList("org_id,status", array("parent_id|in"=>$org_ids));
            //无下一级的组织层级和门店
            if(empty($lower_orginfo)){
                break;
            }
            //循环获取所有的下一级的组织层级和门店的org_id
            $org_ids = array();
            //循环获取是否有status=1启用状态的下一级的组织层级和门店
            $active_org_ids = array();
            foreach ($lower_orginfo as $var_lower_orginfo){
                if(intval($var_lower_orginfo["status"]) == 1){
                    $active_org_ids[] = $var_lower_orginfo["org_id"];
                }
                $org_ids[] = $var_lower_orginfo["org_id"];
            }
            //如果存在启用的层级和门店 跳出循环
            if(!empty($active_org_ids)){
                $return_result = true;
                break;
            }
        }while(!empty($org_ids));
        //返回所有下属中是否存在启用的组织层级和门店 如果有返回true 如果没有返回false
        return $return_result;
    }
    
    //做上一级的下级nos和names的处理
    function updateParentNosAndNames($doWhat,$recordInfo){
        
        if(!$recordInfo["parent_id"]){
            return;
        }

        $organizationObj = $this->app->model('organization');
        $current_record = $organizationObj->dump(array("org_id"=>$recordInfo["parent_id"]),"child_nos,child_names,org_id");
        if(empty($current_record)){
            return;
        }
        
        $updated_child_nos = array();
        $updated_child_names = array();
        
        if($current_record['child_nos']){
            $arr_child_nos = explode(",",$current_record['child_nos']);
            $arr_child_names = explode(",",$current_record['child_names']);
        }else{
            $arr_child_nos = array();
            $arr_child_names = array();
        }
        
        //for when we add new one or update one, do active and unactive, add org_no and org_name into org_no_list/org_name_list
        if($doWhat == "doActive" || $doWhat == "doUnactive" || $doWhat == "add_loweridsnames"){
            if(!in_array($recordInfo["org_no"],$arr_child_nos)){
                $arr_child_nos[] = $recordInfo["org_no"];
                $updated_child_nos = $arr_child_nos;
            }
            if(!in_array($recordInfo["org_name"],$arr_child_names)){
                $arr_child_names[] = $recordInfo["org_name"];
                $updated_child_names = $arr_child_names;
            }
        }
        //for del, remove org_no and org_name in org_no_list/org_name_list
        if($doWhat == "del"){
            if(in_array($recordInfo["org_no"],$arr_child_nos)){
                foreach ($arr_child_nos as $var_child_no){
                    if($recordInfo["org_no"] == $var_child_no){
                    }else{
                        $updated_child_nos[] = $var_child_no;
                    }
                }
            }
            if(in_array($recordInfo["org_name"],$arr_child_names)){
                foreach ($arr_child_names as $var_child_name){
                    if($recordInfo["org_name"] == $var_child_name){
                    }else{
                        $updated_child_names[] = $var_child_name;
                    }
                }
            }
        }
        
        $update_str_child_nos = "";
        $update_str_child_names = "";
        if(!empty($updated_child_nos)){
            $update_str_child_nos = implode(",",$updated_child_nos);
        }
        if(!empty($updated_child_names)){
            $update_str_child_names = implode(",",$updated_child_names);
        }
        
        $updateData_nos_names = array(
            "child_nos" => $update_str_child_nos,
            "child_names" => $update_str_child_names,
        );
        
        $organizationObj->update($updateData_nos_names,array("org_id"=>$current_record["org_id"]));

    }

    function selOrganization(){
        $path = $_GET['path'];
        $depth = $_GET['depth'];
        if(isset($_GET['type']) && $_GET['type']){
            $params = array('depth'=>$depth,'show'=>'onlytree');
        }else{
            $params = array('depth'=>$depth);
        }
        if(isset($_GET['effect']) && $_GET['effect']){
            $params["effect"] = $_GET['effect'];
        }
        $ret = kernel::single('organization_organizations_select')->get_organization_select($path,$params);
        if($ret){
            echo '&nbsp;-&nbsp;'.$ret;exit;
        }else{
            echo '';exit;
        }
    }
    
    /*
    private function _saveOrgStoreInfo($data){
        if(empty($data) || $data['org_type'] != 2){
            return true;
        }

        $organizationStoreObj = $this->app->model('organization_store');
        $org_s_id = $data['bind_store']['org_s_id'];
        $store_id = $data['bind_store']['store_id'];

        $save_data = array(
            'rowid' => $data['rowid'],
            'store_id' => $store_id,
        );

        //保存门店类型的组织架构的门店属性配置信息
        if($org_s_id){
            $res = $organizationStoreObj->update($save_data,array('org_s_id'=>$org_s_id));
        }else{
            $res = $organizationStoreObj->insert($save_data);
        }
    }
    */
    

    function exportTemplate()
    {
        
        $organizationObj    = app::get('organization')->model('organization');
        $title              = $organizationObj->io_title();
        
        $data = [];
        #模板案例
        $data[0]        = array('20160088001', '华东地区', '', '停用');
        $data[1]        = array('20160088002', '上海市', '华东地区-上海', '启用');
        kernel::single('omecsv_phpexcel')->newExportExcel($data, '组织导入模板', 'xlsx', $title);
    }
    
    //展示所有下级
    /**
     * 获取AllChildNode
     * @return mixed 返回结果
     */
    public function getAllChildNode(){
        $obj_organizations_op = kernel::single('organization_operation');
        
        //获取所有下级组织数组
        $dataList    = $obj_organizations_op->getAllChildNode($_POST['orgId']);
        if($dataList)
        {
            //格式化为html展示
            $html    = $obj_organizations_op->getAllChildNodeHtml($dataList);
            $this->pagedata['organization_html']    = $html;
        }
        
        $this->display('admin/organization_all_sub_treeList.html');
    }
}
?>