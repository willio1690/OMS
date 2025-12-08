<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_autostore extends desktop_controller {

    var $workground = "goods_manager";

    function index() {
        $obj_organizations_op = kernel::single('organization_operation');
        
        $dataList    = $obj_organizations_op->getGropById();
        
        #格式化门店关联的规则
        $dataList    = $obj_organizations_op->formatOrgByStoreRule($dataList);
        
        $this->pagedata['organization'] = $dataList;
        $this->page('admin/autostore/store_treeList.html');
    }
    
    //展示页面获取下架组织信息
    /**
     * 获取ChildNode
     * @return mixed 返回结果
     */
    public function getChildNode(){
        $obj_organizations_op = kernel::single('organization_operation');
        
        $dataList    = $obj_organizations_op->getGropById($_POST['orgId']);
        
        #格式化门店关联的规则
        $dataList    = $obj_organizations_op->formatOrgByStoreRule($dataList);
        
        $this->pagedata['organization'] = $dataList;
        $this->display('admin/autostore/sub_store_treeList.html');
    }
    
    //展示所有下级
    /**
     * 获取AllChildNode
     * @return mixed 返回结果
     */
    public function getAllChildNode(){
        $obj_organizations_op = kernel::single('organization_operation');
        //获取所有下级组织数组
        $dataList = $obj_organizations_op->getAllChildNode($_POST['orgId'],2);
        if($dataList){
            //格式化为html展示
            $html = $obj_organizations_op->getAllChildNodeHtml_store($dataList,"autostore");
            $this->pagedata['store_html'] = $html;
        }
        $this->display('admin/organization/store_all_sub_treeList.html');
    }
    
    /**
     * 新建规则
     * 
     * @param intval $org_id  组织结构ID
     */
    public function addRule($org_id)
    {
        $orgObj    = app::get('organization')->model('organization');
        $storeObj  = $this->app->model('store');
        
        $org_info    = $orgObj->dump($org_id);
        if(empty($org_info))
        {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('组织结构不存在');window.close();</script>";
            exit;
        }
        elseif($org_info['status'] != 1)
        {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('该组织结构未启用');window.close();</script>";
            exit;
        }
        $this->pagedata['org_id'] = $org_id;
        
        #门店信息
        $store_info    = $storeObj->dump(array('store_bn'=>$org_info['org_no']), 'name, branch_id');
        $branch_id     = $store_info['branch_id'];
        
        $this->pagedata['store_info'] = $store_info;
        $this->pagedata['branch_id'] = intval($branch_id);

        #获取发送短信的触发事件类型列表
        $types = o2o_autostore::getAutoStoreModes();
        $this->pagedata['rule_types'] = $types;
        
        #已有规则,显示编辑页模板
        $ruleObj     = app::get('o2o')->model('autostore_rule');
        $ruleInfo    = $ruleObj->dump(array('branch_id'=>$branch_id), '*');
        if($ruleInfo)
        {
            $this->pagedata['rule_info'] = $ruleInfo;
            
            $this->singlepage('admin/autostore/editRule.html');
        }
        else 
        {
            $this->singlepage('admin/autostore/createRule.html');
        }
    }
    
    /**
     * 编辑规则
     * 
     * @param intval $rule_id
     */
    public function editRule($org_id)
    {
        $orgObj    = app::get('organization')->model('organization');
        $storeObj  = $this->app->model('store');
        
        $org_info    = $orgObj->dump($org_id);
        if(empty($org_info))
        {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('组织结构不存在');window.close();</script>";
            exit;
        }
        elseif($org_info['status'] != 1)
        {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('该组织结构未启用');window.close();</script>";
            exit;
        }
        
        #门店信息
        $store_info    = $storeObj->dump(array('store_bn'=>$org_info['org_no']), 'name, branch_id');
        $branch_id     = $store_info['branch_id'];
        $this->pagedata['store_info'] = $store_info;
        
        #规则
        $ruleObj = app::get('o2o')->model('autostore_rule');
        $ruleAreaObj = app::get('o2o')->model('autostore_rule_area_items');
        
        //$areaObj = app::get('eccommon')->model('regions');
        //$rule_id = intval($rule_id);
        
        $ruleInfo = $ruleObj->dump(array('branch_id'=>$branch_id), '*');
        $this->pagedata['rule_info'] = $ruleInfo;

    /**
        switch($ruleInfo['rule_type']){
            case 'area':
                //加载规则明细内容走ajax，这里暂无用处
                break;
        }
     * */
    
        $types = o2o_autostore::getAutoStoreModes();
        $this->pagedata['rule_types'] = $types;
    
        $this->singlepage('admin/autostore/editRule.html');
    }

    /**
     * 保存规则
     */
    public function doAddRule(){
        $this->begin('index.php?app=o2o&ctl=admin_autostore&act=getRule&branch_id='.$_POST['branch_id']);

        if(!$this->checkAddParams($_POST, $err_msg)){
            $this->end(false, $err_msg);
        }

        $ruleObj = app::get('o2o')->model('autostore_rule');
        $ruleAreaObj = app::get('o2o')->model('autostore_rule_area_items');

        //保存规则主表信息
        $addData = array(
            'rule_name' => $_POST['rule_name'],
            'rule_type' => $_POST['rule_type'],
            'branch_id' => $_POST['branch_id'],
        );
        $is_save = $ruleObj->save($addData);
        if($is_save){
            switch($addData['rule_type']){
                case 'area':
                    $area_arr = array();
                    if(isset($_POST['chose_area']) && $_POST['chose_area']){
                        foreach($_POST['chose_area'] as $area_id){
                            $area_arr[] = "(".$addData['rule_id'].", ".$area_id.", 0)";
                        }
                    }

                    if(isset($_POST['child_chose_area']) && $_POST['child_chose_area']){
                        foreach($_POST['child_chose_area'] as $p_id =>$child_area_id){
                            if($child_area_id){
                                $tmp_arr = explode(',',$child_area_id);
                                if($tmp_arr){
                                    foreach($tmp_arr as $area_id){
                                        $area_arr[] = "(".$addData['rule_id'].", ".$area_id.", ".$p_id.")";
                                    }
                                }
                            }
                        }
                    }

                    if($area_arr){
                        $sql = "INSERT INTO `sdb_o2o_autostore_rule_area_items` (`rule_id`, `area_id`, `p_area_id`) VALUES ";
                        $sqlInsert = $sql.implode(',', $area_arr).";";
                        if(!$ruleAreaObj->db->exec($sqlInsert)){
                            $this->end(false, '操作失败');
                        }
                    }
                    
                    break;
            }
        }

        $this->end(true, '操作成功');
    }

    function checkAddParams(&$params, &$err_msg){

        //检查规则必填参数
        if(empty($params['rule_name']) || empty($params['rule_type'])){
            $err_msg ="必填信息不能为空";
            return false;
        }

        return true;
    }

    /**
     * doEditRule
     * @return mixed 返回值
     */
    public function doEditRule(){
        $this->begin('index.php?app=o2o&ctl=admin_autostore&act=getRule&branch_id='.$_POST['branch_id']);

        if(!$this->checkAddParams($_POST, $err_msg)){
            $this->end(false, $err_msg);
        }

        $ruleObj = app::get('o2o')->model('autostore_rule');
        $ruleAreaObj = app::get('o2o')->model('autostore_rule_area_items');

        //保存规则主表信息
        $editData = array(
            'rule_id' => $_POST['rule_id'],
            'rule_name' => $_POST['rule_name'],
            'rule_type' => $_POST['rule_type'],
            'branch_id' => $_POST['branch_id'],
        );
        $is_save = $ruleObj->save($editData);
        if($is_save){
            switch($editData['rule_type']){
                case 'area':
                    //删除老的数据
                    $ruleAreaObj->delete(array('rule_id'=>$editData['rule_id']));

                    $area_arr = array();
                    if(isset($_POST['chose_area']) && $_POST['chose_area']){
                        foreach($_POST['chose_area'] as $area_id){
                            $area_arr[] = "(".$editData['rule_id'].", ".$area_id.", 0)";
                        }
                    }

                    if(isset($_POST['child_chose_area']) && $_POST['child_chose_area']){
                        foreach($_POST['child_chose_area'] as $p_id =>$child_area_id){
                            if($child_area_id){
                                $tmp_arr = explode(',',$child_area_id);
                                if($tmp_arr){
                                    foreach($tmp_arr as $area_id){
                                        $area_arr[] = "(".$editData['rule_id'].", ".$area_id.", ".$p_id.")";
                                    }
                                }
                            }
                        }
                    }

                    if($area_arr){
                        $sql = "INSERT INTO `sdb_o2o_autostore_rule_area_items` (`rule_id`, `area_id`, `p_area_id`) VALUES ";
                        $sqlInsert = $sql.implode(',', $area_arr).";";
                        if(!$ruleAreaObj->db->exec($sqlInsert)){
                            $this->end(false, '操作失败');
                        }
                    }
                    
                    break;
            }
        }

        $this->end(true, '操作成功');
    }

    /**
     * 删除Rule
     * @return mixed 返回值
     */
    public function deleteRule(){

        $data = $_POST;
        if(empty($data)){
            echo '请选择';
        }else{

            $this->pagedata['data'] = implode(',',$data['rule_id']);
            $this->page('admin/autostore/deleteRule.html');
        }
    }

    function doDeleteRule(){
        $this->begin();

        $ruleObj = app::get('o2o')->model('autostore_rule');
        $ruleAreaObj = app::get('o2o')->model('autostore_rule_area_items');

        $tmp_rule_ids = $_POST['rule_id'];
        $tmp_rule_arr = explode(',', $tmp_rule_ids);
        if($tmp_rule_arr){
            $ruleObj->delete(array('rule_id'=>$tmp_rule_arr));
            $ruleAreaObj->delete(array('rule_id'=>$tmp_rule_arr));
        }

        $this->end(true,'删除成功');
    }

    function getTmplByType(){
        $type = $_POST['type'];
        $rule_id = $_POST['rule_id'];

        $autoStoreLib = kernel::single('o2o_autostore');
        $ruleObj = app::get('o2o')->model('autostore_rule');
        $ruleAreaObj = app::get('o2o')->model('autostore_rule_area_items');
        $areaObj = app::get('eccommon')->model('regions');

        switch($type){
            case 'area':
                if($rule_id){
                    $area_range = $ruleAreaObj->getList('*',array('rule_id'=>$rule_id), 0, -1);
                    if($area_range){
                        foreach($area_range as $area){
                            if($area['p_area_id'] == 0){
                                $area_items[] = array('area_id'=>$area['area_id']);
                                $p_areas[] = $area['area_id'];
                            }elseif($area['p_area_id'] > 0){
                                $area_child_items[$area['p_area_id']][] = $area['area_id'];
                            }
                        }

                        $p_area_tmp_info = $areaObj->getList('*',array('region_id'=>$p_areas), 0, -1);
                        foreach($p_area_tmp_info as $p_area){
                            if($p_area['region_grade'] > 1){
                                $tmp_p_areas = explode(',',$p_area['region_path']);
                                $area_length = count($tmp_p_areas);
                                unset($tmp_p_areas[0],$tmp_p_areas[$area_length-1],$tmp_p_areas[$area_length-2]);

                                $tmp_p_areas_info = $areaObj->getList('*',array('region_id'=>$tmp_p_areas), 0, -1);
                                foreach($tmp_p_areas_info as $tmp_p_area){
                                    $p_area_names[$p_area['region_id']] .= $tmp_p_area['local_name'].'/';
                                }

                                $p_area_names[$p_area['region_id']] .= $p_area['local_name'];
                            }else{
                                $p_area_names[$p_area['region_id']] = $p_area['local_name'];
                            }
                        }

                        foreach($area_items as $k=>$area){
                            if(isset($p_area_names[$area['area_id']])){
                                $area_items[$k]['name'] = $p_area_names[$area['area_id']];
                            }

                            if(isset($area_child_items[$area['area_id']])){
                                $area_items[$k]['childs'] = implode(',',$area_child_items[$area['area_id']]);
                            }
                        }
                    }

                    //判断区域是否有下级
                    if($area_items)
                    {
                        foreach ($area_items as $key => $val)
                        {
                            $area_info    = $areaObj->dump(array('region_id'=>$val['area_id']), 'haschild');
                            $area_items[$key]['haschild']    = $area_info['haschild'];
                        }
                    }
                    
                    $this->pagedata['area_items'] = $area_items;
                }
                break;
            case 'lbs':
                break;
        }

        $tmpl = $autoStoreLib->getTmplConfByMode($type);
        $this->display($tmpl);
    }
    
    //获取area区域是否还有下级地区
    function getAreaHaschild()
    {
        $region_id    = $_POST['region_id'];
        if(empty($region_id))
        {
            echo('error');
            exit;
        }
        
        $areaObj      = app::get('eccommon')->model('regions');
        $area_info    = $areaObj->dump(array('region_id'=>$region_id), 'haschild');
        
        if($area_info['haschild'])
        {
            echo('true');
            exit;
        }
        
        echo('false');
        exit;
    }
}
