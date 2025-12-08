<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logistics_ctl_admin_area_rule extends desktop_controller {
        var $workground = 'setting_tools';
        var $defaultWorkground = 'setting_tools';


        function index(){
            $rule_id = $_GET['rule_id'];
            $rule = $this->app->model('rule')->getlist('branch_id,rule_name',array('rule_id'=>$rule_id),0,1);

            $branch = app::get('ome')->model('branch')->getList('name', array('branch_id'=>$rule[0]['branch_id']),0,1);
            $title = "<span style='color:red;'>".$branch[0]['name']."</span>,".$rule[0]['rule_name'];
            $action = array(
                            array(
                            'label'  => $this->app->_('添加地区规则'),
                            'href'   => 'index.php?app=logistics&ctl=admin_area_rule&act=addArearule&rule_id='.$_GET['rule_id'],
                            'target' => 'dialog::{width:800,height:600,title:\'添加规则\'}',
                            ),

                            );
            $params=array(
                'title' => $title,
                'use_buildin_recycle' => false,
                'base_filter'=>array('rule_id'=>$_GET['rule_id']),
                'actions'=>$action,
            );
            $this->finder('logistics_mdl_rule_obj',$params);
        }

        /**
         * 编辑地区规则
         */
        function addArearule(){
           $rule_id = $_GET['rule_id'];
           $type = $_GET['type'];
           $obj_id = $_GET['obj_id'];
           $rule = $this->app->model('rule')->getlist('branch_id,first_city,first_city_id,rule_id',array('rule_id'=>$rule_id),0,1);
           $rule = $rule[0];
            $rule['class_city_list'] = $this->app->model('rule')->getRuleRegion($rule_id);
           if($_GET['action']=='edit'){
            $rule_obj = $this->app->model('rule_obj')->detail_rule_obj($obj_id);
            $this->pagedata['rule_obj'] = $rule_obj;


           }

           $this->pagedata['rule'] = $rule;

            //电子面单来源类型
            $channelObj = app::get("logisticsmanager")->model('channel');
            $rows = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
            $channelType = array();
            foreach($rows as $val) {
                $channelType[$val['channel_id']] = $val['channel_type'];
                unset($val);
            }
            unset($rows);

            //物流公司信息
            $braObj = app::get('ome')->model('branch');
            $dly_corp = $braObj->get_corp($rule['branch_id'],'');
            array_push($dly_corp,array('corp_id'=>'-1','name'=>'人工审单'));
            $dlyCorpNormal = $electronIds = array();
            foreach($dly_corp as $key=>$val) {
                if($val['tmpl_type'] != 'electron') {
                    $dlyCorpNormal[] = $val;
                } else {
                    if($channelType[$val['channel_id']] == 'wlb') {
                        $electronIds[] = $val['corp_id'];
                        $dly_corp[$key]['name'] .= '(电)';
                    } else {
                        $dlyCorpNormal[] = $val;
                        $dly_corp[$key]['name'] .= '('.$channelType[$val['channel_id']].')';
                    }
                }
            }

            $this->pagedata['action'] = $_GET['action'];
            $this->pagedata['dly_corp'] = $dly_corp;
            $this->pagedata['dlyCorpNormal'] = $dlyCorpNormal;
            $this->pagedata['dlyCorpNormalJson'] = json_encode($dlyCorpNormal);
            $this->pagedata['electronIds'] = json_encode($electronIds);
            $this->pagedata['dly_corp_list'] = json_encode($dly_corp);
            $this->pagedata['elecIds'] = $electronIds;
            $this->pagedata['type'] = $type;
            if($_GET['action']=='edit'){
                   $this->page('admin/edit_area_rule.html');
           }else{
               $this->page('admin/create_area_rule.html');
           }
        }

        //规则列表
        function area_rule_list(){
            $rule_id = $_GET['rule_id'];
            $this->pagedata['rule_id']=$rule_id;
            //判断是否 按店铺设置
            $mdl_rule_obj = app::get('logistics')->model('rule_obj');
            $rs_rule_obj = $mdl_rule_obj->dump(array("rule_id"=>$rule_id));
            if($rs_rule_obj["set_type"] == "shop"){//按店铺
                $mdl_rule = app::get('logistics')->model('rule');
                $this->pagedata["rule"] = $mdl_rule->dump(array("rule_id"=>$rule_id));
                $this->pagedata["rule"]["set_type"] = $rs_rule_obj["set_type"];
                //获取选中的指定店铺
                $mdl_rule_shop= app::get('logistics')->model('rule_shop');
                $mdl_ome_shop = app::get('ome')->model('shop');
                $rs_rule_shop = $mdl_rule_shop->getList("*",array("rule_id"=>$rule_id));
                $arr_shop_ids = array();
                foreach ($rs_rule_shop as $var_rule_shop){
                    $arr_shop_ids[] = $var_rule_shop["shop_id"];
                }
                $rs_shops = $mdl_ome_shop->getList("name",array("shop_id|in"=>$arr_shop_ids));
                $shop_names = array();
                foreach ($rs_shops as $var_shop){
                    $shop_names[] = $var_shop["name"];
                }
                $this->pagedata["str_selected_shop"] = implode("，", $shop_names);
                //获取指定物流公司
                $mdl_rule_items = app::get('logistics')->model('rule_items');
                $rs_rule_items = $mdl_rule_items->dump(array("obj_id"=>$rs_rule_obj["obj_id"]));
                if ($rs_rule_items["corp_id"] == "-1"){//人工审单 -1
                    $this->pagedata["corp_name"] = "人工审单";
                }else{
                    $mdl_dly_corp = app::get('ome')->model('dly_corp');
                    $rs_dly_corp = $mdl_dly_corp->dump(array("corp_id"=>$rs_rule_items["corp_id"]));
                    $this->pagedata["corp_name"] = $rs_dly_corp["name"];
                }
                
                if($_GET['a']){
                    $this->page('admin/city_rule_list.html');
                }else{
                    $this->singlepage('admin/city_rule_list.html');
                }
            }else{//按任意重量 和 重量区间
                $type = isset($_GET['type']) ? $_GET['type'] :'default';
                $class_city = array();
                $rule_list = $this->app->model('rule_obj')->getlist('region_id,region_name',array('rule_id'=>$rule_id,'rule_type'=>'default'),0,-1,'obj_id DESC');
                foreach($rule_list as $k=>$v){
                    array_push($class_city,$v['region_id']);
                }
                $rule_list['class_city'] = $class_city;
                #一级区域
                $rule_list['class_city_list'] = $this->app->model('rule')->getRuleRegion($rule_id);
                $this->pagedata['rule_list']=$rule_list;
                if($type=='default'){//默认规则
                    $area = $this->app->model('area')->getArea();
                    $this->pagedata['area'] = $area;
                    #规则
                    $rule = $this->app->model('rule')->detailRule($rule_id,1);
                    $this->pagedata['rule'] = $rule;
                    //电子面单来源类型
                    $channelObj = app::get("logisticsmanager")->model('channel');
                    $rows = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
                    $channelType = array();
                    foreach($rows as $val) {
                        $channelType[$val['channel_id']] = $val['channel_type'];
                        unset($val);
                    }
                    unset($rows);
                    //物流公司信息
                    $braObj = app::get('ome')->model('branch');
                    $dly_corp = $braObj->get_corp($rule['branch_id'],'');
                    array_push($dly_corp,array('corp_id'=>'-1','name'=>'人工审单'));
                    $dlyCorpNormal = $electronIds = array();
                    foreach($dly_corp as $key=>$val) {
                        if($val['tmpl_type'] != 'electron') {
                            $dlyCorpNormal[] = $val;
                        } else {
                            if($channelType[$val['channel_id']] == 'wlb') {
                                $electronIds[] = $val['corp_id'];
                                $dly_corp[$key]['name'] .= '(电)';
                            } else {
                                $dlyCorpNormal[] = $val;
                                $dly_corp[$key]['name'] .= '('.$channelType[$val['channel_id']].')';
                            }
                        }
                    }
                    
                    $this->pagedata['dly_corp'] = $dly_corp;
                    $this->pagedata['dlyCorpNormal'] = $dlyCorpNormal;
                    $this->pagedata['dlyCorpNormalJson'] = json_encode($dlyCorpNormal);
                    $this->pagedata['electronIds'] = json_encode($electronIds);
                    $this->pagedata['dly_corp_list'] = json_encode($dly_corp);
                    $this->pagedata['elecIds'] = $electronIds;
                    
                    if($_GET['a']){
                        $this->page('admin/city_rule_list.html');
                    }else{
                        $this->singlepage('admin/city_rule_list.html');
                    }
                }else{//下属特殊地区规则
                    if($_GET['a']){
                        $this->display('admin/area_rule_list.html');
                    }else{
                        $this->singlepage('admin/area_rule_list.html');
                    }
                }
            }
        }

        /**
         * 保存排它区域规则
         */
        function saveAreaRule(){
            $this->begin();
            $data = $_POST;

            foreach($data['area'] as $ak=>$av){
                $region = explode(':', $av);
                $region_id = $region[2];
                $data['area'][$ak]=array('region_id'=>$region_id);

            }

            $rule = $this->app->model('rule_obj')->create_rule_obj($data);
            if($rule){
                $this->end(true,'成功');
            }

        }

        /**
         * 删除区域规则
         */
        function deleteAreaRule(){
            $this->begin();
            $obj_id = $_GET['obj_id'];
            $type = $_GET['type'];
            $rule = $this->app->model('rule_obj')->delete_rule($obj_id,$type);
            if($rule){
                $this->end(true,'成功');


            }

        }

        /**
         * 排它规则列表
         */
        function show_rule_list($page=1){

            $rule_id = $_GET['rule_id'];
            $page = intval($page);
            $area = $_GET['area'];
            $filter =array('rule_id'=>$rule_id,'rule_type'=>'other');
            if($area){

                $region = explode(':',$area);
                $area= explode('/',$region[1]);

                $area_count = count($area);
                $region_id = array();
                if($area_count==1 || $area_count==2){
                    $region_list = $this->app->model('area')->getAllChild($region[2]);
                    foreach($region_list as $rk=>$rv){
                        $region_id[] = $rv['region_id'];
                    }
                }else{
                    $region_id[] = $region[2];
                }

                $filter['region_id'] = $region_id;
            }

            $pagelimit = 50;
            $rule_list = $this->app->model('rule_obj')->getlist('*',$filter,$pagelimit*($page-1), $pagelimit,'obj_id DESC');
            foreach($rule_list as $k=>$v){
                $region_id = $v['region_id'];
                $rule_list[$k]['region_name_path'] = $this->app->model('area')->getRegionPath($region_id);
                #规则描述
                $rule_obj = $this->app->model('rule_obj')->detail_rule_obj($v['obj_id']);
                $rule_list[$k]['item_list'] = $rule_obj['items'];

            }
            $rule_list_total = $this->app->model('rule_obj')->getlist('obj_id',$filter,0,-1);
            $count = count($rule_list_total);
            $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>ceil($count/$pagelimit),
            'link'=>'index.php?app=logistics&ctl=admin_area_rule&act=area_rule_list&type=other&a=c&rule_id='.$rule_id.'&page=%d'.'&area='.$_GET['area'],
            ));



            $this->pagedata['rule_list']=$rule_list;
            $this->pagedata['rule_id']=$rule_id;
            $this->pagedata['type']='other';
            $this->pagedata['pager'] = $pager;
            $this->pagedata['total'] = $count;#总计

            $this->pagedata['count'] = $count;
            $this->pagedata['pagelimit'] = $pagelimit;
            return $this->display("admin/rule_list.html");
        }

        //执行物流优选编辑操作
        function editArearule(){
            $this->begin();
            $data= $_POST;
            if($data['rule_type']=='other'){
                $this->app->model('rule_obj')->edit_rule_obj($data);
                $this->end(true,'编辑成功');
            }else{
                if ($data["set_type"] == "shop"){//按店铺
                    if (!$data["shop_selected"]){
                        $this->end(false,'请选择指定店铺');
                    }
                }else{//按任意重量 和 按重量区间
                    //判断区域是否存在
                    $first_city = $data['first_city'];
                    if(!$first_city){
                        $this->end(false,'请选择地区');
                    }
                    foreach($first_city as $k=>$v){
                        $region = $this->app->model('rule')->chkBranchRegion($data['branch_id'],$v,$data['rule_id']);
                        if($region){
                            $this->end(false,'重复区域存在');
                        }
                    }
                }
                $this->app->model('rule')->editRule($data);
                $this->end(true,'成功','index.php?app=logistics&ctl=admin_area_rule&act=area_rule_list&type=default&rule_id='.$data['rule_id'].'&a=c');
            }
        }

        function deleteRule(){
            $this->begin();
            $item_id = $_GET['item_id'];
            $result = $this->app->model('rule_obj')->delete_rule($item_id);
            if($result){
                $this->end(true,'成功');
            }
        }

        function areaFilter(){
            $oper = $_GET['oper'];
            if($oper=='ckarea'){

                $data = $_GET;
                $branch_id = $data['branch_id'];
                if($data['p_region_id']){
                    $p_region_id = explode(',',$data['p_region_id']);
                    $region_list= array();
                    foreach($p_region_id as $k=>$v){
                        $child = $this->app->model('area')->getAllChild($v);
                        $region = kernel::single('eccommon_regions')->getOneById($v);

                        foreach ($child as $ck=>$cv) {
                            $rule_obj = $this->app->model('rule_obj')->getlist('obj_id',array('region_id'=>$cv['region_id'],'branch_id'=>$branch_id),0,1);
                            if($rule_obj){
                                $region_list[$k]['region_name'] =$region['local_name'];
                                $region_list[$k]['items'][$ck]=array(
                                    'flag'=>1,
                                    'region_name'=>$cv['region_name']

                                );


                            }
                        }



                    }

                    echo json_encode($region_list);

                }


            }else{

                $area = $_GET['area']!='' ? explode(',',$_GET['area']):'';
                $rule_id = $_GET['rule_id'];
                $rule = $this->app->model('rule')->dump($rule_id,'branch_id');

                $branch_id = $rule['branch_id'];
                $region_list = array();
                foreach($area as $k=>$v){
                    $region = explode(':', $v);
                    $region_id = $region[2];

                    $region = kernel::single('eccommon_regions')->getOneById($region_id);

                    //$region_list[$k]['region_name'] = $region['local_name'];
                    $region_list[$k]['region_name'] = $this->app->model('area')->getRegionPath($region_id);
                    #获取区域下三级区域

                    $child = $this->app->model('rule_obj')->areaFilter($region_id,$branch_id);

                    $region_list[$k]['area'] = $child;

                }

                $this->pagedata['region_list'] = $region_list;

                $this->display('admin/confirm_area_rule.html');
            }
        }

        /**
         * 批量删除地区规则
         */
        function batch_del(){
            $this->begin();
            $data = $_POST;
            $obj_id = $data['obj_id'];
            foreach($obj_id as $k=>$v){
                $this->app->model('rule_obj')->delete_rule($v,'obj');
            }
            $this->end(true,'删除成功');
        }

        /**
         * 检查区域是否已存在

         */
        function chkAreaRule(){
            $this->begin();
            $data = $_GET;

            $region_list = explode(',',$data['region_list']);
            $region_name=array();
            foreach($region_list as $ak=>$av){
                $region = explode(':', $av);
                $region_id = $region[2];

                $region_rule = $this->app->model('rule_obj')->dump(array('region_id'=>$region_id,'rule_id'=>$data['rule_id']),'region_name');
                if($region_rule){
                    //$this->end(false,'['.$region_rule['region_name'].']已存在于此规则中,不可以重复添加!');
                    $region_name[] = $region_rule['region_name'];
                }
            }
            if(!empty($region_name)){
                $region_name = implode(',',$region_name);
                $data = array();
                $data['error']='已存在';
                $data['region_name'] = $region_name;
                echo json_encode($data);
            }
        }

        function detailAreaRule($obj_id){
            $rule_obj = $this->app->model('rule_obj')->detail_rule_obj($obj_id);
            $item_list = $rule_obj['items'];

            echo json_encode($item_list);
        }


        /**
         * 批量设置
         */
        function batchUpdateAreaRule(){
            $data = $_GET;

            $rule_id = $data['rule_id'];
            $obj_id = $_GET['obj_id'];

            $ruleObj = kernel::single('logistics_rule');
            $rule_list = $ruleObj->getGroupAreaRule($obj_id);

            $this->pagedata['rule_list'] = $rule_list;
            $this->pagedata['rule_id'] = $rule_id;
            unset($rule_list);
            $this->page('admin/batch_area_rule.html');


        }

        function batch_area_step(){

            $data = $_POST;

            $rule_id = $data['rule_id'];
            $rule = $this->app->model('rule')->getlist('branch_id',array('rule_id'=>$rule_id),0,1);

            //电子面单来源类型
            $channelObj = app::get("logisticsmanager")->model('channel');
            $rows = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
            $channelType = array();
            foreach($rows as $val) {
                $channelType[$val['channel_id']] = $val['channel_type'];
                unset($val);
            }
            unset($rows);

            //物流公司信息
            $braObj = app::get('ome')->model('branch');
            $dly_corp = $braObj->get_corp($rule[0]['branch_id'],'');
            array_push($dly_corp,array('corp_id'=>'-1','name'=>'人工审单'));
            $dlyCorpNormal = $electronIds = array();
            foreach($dly_corp as $key=>$val) {
                if($val['tmpl_type'] != 'electron') {
                    $dlyCorpNormal[] = $val;
                } else {
                    if($channelType[$val['channel_id']] == 'wlb') {
                        $electronIds[] = $val['corp_id'];
                        $dly_corp[$key]['name'] .= '(电)';
                    } else {
                        $dlyCorpNormal[] = $val;
                        $dly_corp[$key]['name'] .= '('.$channelType[$val['channel_id']].')';
                    }
                }
            }

            $this->pagedata['dly_corp'] = $dly_corp;
            $this->pagedata['dlyCorpNormal'] = $dlyCorpNormal;
            $this->pagedata['dlyCorpNormalJson'] = json_encode($dlyCorpNormal);
            $this->pagedata['electronIds'] = json_encode($electronIds);
            $this->pagedata['dly_corp_list'] = json_encode($dly_corp);
            $this->pagedata['elecIds'] = $electronIds;

            $region_id = implode(',',$data['region_id']);
            $region_name = implode(',',$data['region_name']);
            $obj_id = implode(',',$data['obj_id']);
            $this->pagedata['region_id']=$region_id;
            $this->pagedata['region_name']=$region_name;
            $this->pagedata['obj_id']=$obj_id;

            $this->page('admin/batch_area_step.html');
        }

        /**
         * 保存批量设置
         */
        function saveBatchAreaRule(){
            $this->begin();
            $data = $_POST;


            $this->app->model('rule_obj')->update_rule_obj($data);
            $this->end(true,'保存成功');
        }

        //编辑规则展示弹窗页
        function editDefaultRule(){
            $rule_id = $_GET['rule_id'];
            $type = isset($_GET['type']) ? $_GET['type'] :'default';
            $branch_id = $_GET['branch_id'];
            $class_city = array();
            $rule_list = $this->app->model('rule_obj')->getlist('region_id,region_name,obj_id',array('rule_id'=>$rule_id,'rule_type'=>'default'),0,-1,'obj_id DESC');
            foreach($rule_list as $k=>$v){
                array_push($class_city,$v['region_id']);
            }
            $rule_list['class_city'] = $class_city;
            #一级区域
            $rule_list['class_city_list'] = $this->app->model('rule')->getRuleRegion($rule_id);
            $this->pagedata['rule_list']=$rule_list;
            $this->pagedata['rule_id']=$rule_id;
            $area = $this->app->model('area')->getArea();
            foreach($area as $k=>$v){
                foreach($v['area_items'] as $ak=>$av){
                    $region_obj = $this->app->model('rule')->chkBranchRegion($branch_id,$av['region_id'],$rule_id);
                    if($region_obj){
                        $area[$k]['area_items'][$ak]['flag']=1;
                    }
                }
            }

            $this->pagedata['area'] = $area;

            #规则
            $rule = $this->app->model('rule')->detailRule($rule_id,1);
            $this->pagedata['rule'] = $rule;

            //电子面单来源类型
            $channelObj = app::get("logisticsmanager")->model('channel');
            $rows = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
            $channelType = array();
            foreach($rows as $val) {
                $channelType[$val['channel_id']] = $val['channel_type'];
                unset($val);
            }
            unset($rows);

            //物流公司信息
            $braObj = app::get('ome')->model('branch');
            
            // 判断是否为门店仓
            $branchInfo = $braObj->db_dump(array('branch_id'=>$rule['branch_id'],'check_permission'=>'false'), 'b_type');
            $isStoreBranch = ($branchInfo && $branchInfo['b_type'] == '2');
            
            if ($isStoreBranch) {
                // 门店仓：获取所有可用的物流公司
                $mdl_ome_dly_corp = app::get('ome')->model('dly_corp');
                $dly_corp = $mdl_ome_dly_corp->getList('corp_id,name,type,weight,tmpl_type,channel_id,d_type,corp_model', array('disabled'=>'false'));
                
                // 如果门店参与O2O，优先显示商家配送类型的物流公司
                if (app::get('o2o')->is_installed()) {
                    $o2oStore = app::get('o2o')->model('store')->getList('store_id', array('branch_id'=>$rule['branch_id'],'is_o2o'=>'1'), 0, 1);
                    if (!empty($o2oStore)) {
                        // 重新排序：商家配送优先，然后按权重排序
                        $sellerCorps = array();
                        $otherCorps = array();
                        foreach ($dly_corp as $corp) {
                            if ($corp['corp_model'] == 'seller') {
                                $sellerCorps[] = $corp;
                            } else {
                                $otherCorps[] = $corp;
                            }
                        }
                        // 按权重排序
                        $sellerCorps = $braObj->sysSortArray($sellerCorps, 'weight', 'SORT_DESC', 'SORT_NUMERIC');
                        $otherCorps = $braObj->sysSortArray($otherCorps, 'weight', 'SORT_DESC', 'SORT_NUMERIC');
                        $dly_corp = array_merge($sellerCorps, $otherCorps);
                    }
                }
            } else {
                // 大仓：使用原有逻辑获取物流公司
                $dly_corp = $braObj->get_corp($rule['branch_id'],'');
            }
            
            array_push($dly_corp,array('corp_id'=>'-1','name'=>'人工审单'));
            $dlyCorpNormal = $electronIds = array();
            foreach($dly_corp as $key=>$val) {
                if($val['tmpl_type'] != 'electron') {
                    $dlyCorpNormal[] = $val;
                } else {
                    if($channelType[$val['channel_id']] == 'wlb') {
                        $electronIds[] = $val['corp_id'];
                        $dly_corp[$key]['name'] .= '(电)';
                    } else {
                        $dlyCorpNormal[] = $val;
                        $dly_corp[$key]['name'] .= '('.$channelType[$val['channel_id']].')';
                    }
                }
            }

            $this->pagedata['dly_corp'] = $dly_corp;
            $this->pagedata['dlyCorpNormal'] = $dlyCorpNormal;
            $this->pagedata['dlyCorpNormalJson'] = json_encode($dlyCorpNormal);
            $this->pagedata['electronIds'] = json_encode($electronIds);
            $this->pagedata['dly_corp_list'] = json_encode($dly_corp);
            $this->pagedata['elecIds'] = $electronIds;
            
            //店铺列表
            $this->pagedata['shops'] = app::get('ome')->model('shop')->getList('name,shop_id', array(), 0, -1, 'name ASC');
            $mdl_rule_shop = app::get('logistics')->model('rule_shop');
            //同仓库有其他规则选择过的shop
            $rs_has_shops = $mdl_rule_shop->getList("*",array("branch_id"=>$branch_id,"rule_id|noequal"=>$rule_id));
            if (!empty($rs_has_shops)){
                $arr_has_shop = array();
                foreach ($rs_has_shops as $var_has_shop){
                    $arr_has_shop[] = $var_has_shop["shop_id"];
                }
                foreach ($this->pagedata['shops'] as &$var_shop){
                    //同一个仓库已用店铺不可再选
                    if (in_array($var_shop["shop_id"],$arr_has_shop)){
                        $var_shop["disabled"] = true;
                    }
                }
                unset($var_shop);
            }
            //set_type为shop 当前选择的shop  以及指定物流选择
            if($rule["set_type"] == "shop"){
                $rs_current_shops = $mdl_rule_shop->getList("*",array("branch_id"=>$branch_id,"rule_id"=>$rule_id));
                $arr_current_shop = array();
                foreach($rs_current_shops as $var_current_shop){
                    $arr_current_shop[] = $var_current_shop["shop_id"];
                }
                foreach ($this->pagedata['shops'] as &$var_shop){
                    if (in_array($var_shop["shop_id"],$arr_current_shop)){
                        $var_shop["checked"] = true;
                    }
                }
                unset($var_shop);
                $mdl_rule_items = $this->app->model('rule_items');
                $rs_rule_items = $mdl_rule_items->dump(array("obj_id"=>$rule_list[0]["obj_id"]));
                foreach ($this->pagedata['dly_corp']as &$var_dly_corp){
                    if ($var_dly_corp["corp_id"] == $rs_rule_items["corp_id"]){
                        $var_dly_corp["selected"] = true; 
                    }
                }
                unset($var_dly_corp);
            }
            
            $this->page('admin/edit_city_rule.html');
        }

        function getAreaRuleDetail($rule_id){

            $filter =array('rule_id'=>$rule_id,'rule_type'=>'other');
            $rule_list = app::get('logistics')->model('rule_obj')->getlist('*',$filter,0, -1,'obj_id DESC');

            foreach($rule_list as $k=>$v){
                $region_id = $v['region_id'];
                $rule_list[$k]['region_name_path'] = app::get('logistics')->model('area')->getRegionPath($region_id);
                #规则描述
                $rule_obj = app::get('logistics')->model('rule_obj')->detail_rule_obj($v['obj_id']);
                $item_list='';
                if($v['set_type']=='weight'){
                    $rule_list[$k]['set_type_name']='重量区间';
                }else{
                    $rule_list[$k]['set_type_name']='非重量区间';
                }
                if($v['set_type']=='weight'){
                       foreach($rule_obj['items'] as $ik=>$iv){
                        $item_list.='重量区间:'.$iv['min_weight'].'g ';
                        if ($iv['max_weight']=='-1') {
                            $item_list.='以上,';
                        } else {
                            $item_list.='至'.$iv['max_weight'].'g,';;
                        }
                        $item_list.='首选物流公司:'.$iv['corp_name'];
                        if($iv['second_corp_name']) {
                            $item_list.=', 次选物流公司:'.$iv['second_corp_name'];
                        }
                        $item_list.='<br>';
                    }

                }else{
                    foreach($rule_obj['items'] as $ik=>$iv){
                        $item_list.='首选物流公司:'.$iv['corp_name'];
                        if($iv['second_corp_name']) {
                            $item_list.=', 次选物流公司:'.$iv['second_corp_name'];
                        }
                        $item_list.='<br>';
                    }
                }
                $rule_list[$k]['item_list'] = $item_list;

            }
            echo json_encode($rule_list);

        }
    }

?>