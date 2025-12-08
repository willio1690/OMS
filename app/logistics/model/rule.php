<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logistics_mdl_rule extends dbeav_model{

    var $defaultOrder = array('rule_id','DESC');

    //执行创建规则
    function createRule($data){
        $rule_data = array(
            "rule_name" => $data['rule_name'],
            "branch_id" => $data['branch_id'],
        );
        if ($data["weight"]){//权重
            $rule_data["weight"] = $data["weight"];
        }
        if($data["set_type"] == "shop"){//按店铺
            //主表新增
            $rule_id = $this->insert($rule_data);
            //相关表新增
            $this->insertShopRule($rule_id,$data);
        }else{//按任意重量 和 按重量区间
            $rule_data['first_city'] = $data['first_city'];
            $rule_data['first_city_id'] = $data['p_region_id'];
            $rule = $this->save($rule_data);
            $rule_id = $rule_data['rule_id'];
            $data['rule_id'] = $rule_id;
            if($data['p_region_id']){
                $data['first_city'] = explode(',',$data['p_region_id']);
            }
            $this->createRegionRule($data);
        }
        return $rule_id;
    }

    function detailRule($rule_id,$region_grade,$rule_type='default'){
        $rule = $this->dump($rule_id,'*');
        $dly_corpObj = app::get('ome')->model('dly_corp');
        $braObj = app::get('ome')->model('branch');
        $branch = $braObj->dump($rule['branch_id'],'name');
        $rule['branch_name'] = $branch['name'];
        $filter = array('rule_id'=>$rule_id);
        $sql = "SELECT distinct r.item_id,o.set_type FROM sdb_logistics_rule_obj as o LEFT JOIN sdb_logistics_region_rule as r on o.obj_id=r.obj_id WHERE o.rule_id=".$rule_id." AND o.rule_type='".$rule_type."'";

        $item_id = $this->db->select($sql);
        $rule['set_type'] = $item_id[0]['set_type'];
        $item_id_list = array();
        foreach($item_id as $k=>$v){
            $item_id_list[] = $v['item_id'];

        }

        if($item_id_list) {
            $rows = $dly_corps = array();
            $rows = $dly_corpObj->getList('corp_id,name');
            foreach ($rows as $val) {
                $dly_corps[$val['corp_id']] = $val['name'];
                unset($val);
            }
            unset($rows);
            $item_id_list = implode('","',$item_id_list);

            $item_sql = 'SELECT min_weight,max_weight,corp_id,second_corp_id,item_id FROM sdb_logistics_rule_items WHERE item_id in ("'.$item_id_list.'") ORDER BY min_weight ASC';
            $item_list = $this->db->select($item_sql);
            foreach ($item_list as $ik=>$item) {
                if($item['corp_id']=='-1') {
                    $item_list[$ik]['corp_name'] = '人工审单';
                }else{
                    $item_list[$ik]['corp_name']=$dly_corps[$item['corp_id']];
                    $item_list[$ik]['second_corp_name']=$dly_corps[$item['second_corp_id']];
                }
            }
            $rule['item_list'] = $item_list;
        }

        return $rule;
    }

    function editRule($data){
        $rule_id = $data['rule_id'];
        $has_shop_rule = true; //过程中是否有按店铺set_type为shop存在 默认存在true
        //组更新主表的数组
        if ($data['first_city']){
            $first_city_id_update = implode(',',$data['first_city']);
            $first_city_update = $this->app->model('area')->getRegion($data['first_city']);
        }
        $rule_data = array(
            "rule_id" => $rule_id,
            "rule_name" => $data['rule_name'],
            "weight" => $data['weight'],
            "first_city_id" => $first_city_id_update,
            "first_city" => $first_city_update,
        );
        $rule = $this->app->model('rule_obj')->dump(array('rule_id'=>$rule_id,'rule_type'=>'default'),'set_type');
        if ($rule['set_type'] != $data['set_type']){ //切换了规则类型，删除原有类型规则
            if ($rule['set_type'] == "shop"){ //原始规则是shop按店铺 除了主表删除原有关联表的相关数据 并新增任意重量或重量区间相关数据
                $del_shop_rule_arr = array($rule_id);
                $this->deleteShopRule($del_shop_rule_arr,false);
                $this->createRegionRule($data);
            }elseif ($data['set_type'] == "shop"){ //切换成shop按店铺 除了主表删除原有类型规则  并新增按店铺相关数据
                $rule_data['first_city_id'] = "";
                $rule_data['first_city'] = "";
                if($data['deleteareaflag']=='0'){ //原有逻辑 其实这表提交过来的这个值 一直为0
                    $this->deleteRule($rule_id,'','default',1);
                    $this->deleteRule($rule_id,'','other',0);
                }else{
                    $this->deleteRule($rule_id,'','',1);
                }
                $this->insertShopRule($rule_id,$data);
            }else{//原始规则和保存规则都没有指定店铺的 按原来旧的业务走
                $has_shop_rule = false; //后续走原有的业务代码
                $readydel_rule = $this->app->model('rule_obj')->getlist('obj_id',array('rule_id'=>$rule_id,'rule_type'=>'default'));
                $rule_obj_id = array();
                foreach($readydel_rule as $rule_obj){
                    $rule_obj_id[] = $rule_obj['obj_id'];
                }
                if($rule_obj_id){
                    $rule_obj_id = implode(',',$rule_obj_id);
                    $readydel_items = $this->db->select('SELECT item_id FROM  sdb_logistics_region_rule WHERE obj_id in ('.$rule_obj_id.') GROUP BY item_id');
                    $item_id = array();
                    foreach($readydel_items as $item){
                        $item_id[] = $item['item_id'];
                    }
                    $region_sql = 'DELETE FROM sdb_logistics_region_rule WHERE obj_id in('.$rule_obj_id.')';
                    $this->db->exec($region_sql);
                    if ($item_id) {
                        $item_id = implode(',',$item_id);
                        $items_sql = 'DELETE FROM sdb_logistics_rule_items WHERE item_id in('.$item_id.')';
                        $this->db->exec($items_sql);
                    }
                }
            }
        }else{ //没有切换规则类型
            if ($rule['set_type'] == "shop"){ //按店铺set_type是shop
                $rule_data['first_city_id'] = "";
                $rule_data['first_city'] = "";
                //rule_obj表无需更新  更新rule_items表corp_id字段即可（变更的情况下） 更新rule_shop表（删、加）
                $rs_rule_obj = $this->app->model('rule_obj')->dump(array('rule_id'=>$rule_id));
                $mdl_rule_items = $this->app->model('rule_items');
                $rs_rule_items = $mdl_rule_items->dump(array('obj_id'=>$rs_rule_obj["obj_id"]));
                if($rs_rule_items["corp_id"] != $data["shop_corp_id"]){
                    $update_arr = array("corp_id"=>$data["shop_corp_id"]);
                    $filter_arr = array("item_id"=>$rs_rule_items["item_id"]);
                    $mdl_rule_items->update($update_arr,$filter_arr);
                }
                //处理rule_shop关系表
                $mdl_rule_shop = $this->app->model('rule_shop');
                $rs_rule_shop = $mdl_rule_shop->getList("*",array("rule_id"=>$rule_id));
                $arr_rule_shop = array();
                foreach ($rs_rule_shop as $var_rule_shop){
                    $arr_rule_shop[] = $var_rule_shop["shop_id"];
                }
                $del_rule_shop = array_diff($arr_rule_shop,$data["shop_selected"]);
                $add_rule_shop = array_diff($data["shop_selected"],$arr_rule_shop);
                if (!empty($del_rule_shop)){ //删除
                    $mdl_rule_shop->delete(array("shop_id"=>$del_rule_shop,"rule_id"=>$rule_id,"branch_id"=>$data['branch_id']));
                }
                if (!empty($add_rule_shop)){ //新增
                    foreach ($add_rule_shop as $var_add_rule_shop){
                        $insert_arr = array(
                            "rule_id" => $rule_id,
                            "shop_id" => $var_add_rule_shop,
                            "branch_id" => $data['branch_id'],
                        );
                        $mdl_rule_shop->insert($insert_arr);
                    }
                }
            }else{ //按任意重量或重量区间
                $has_shop_rule = false; //后续走原有的业务代码
            }
        }
        
        //更新主表内容
        $this->save($rule_data);
        
        if (!$has_shop_rule){
            $rule_obj = $this->app->model('rule_obj')->getlist('region_id',array('rule_id'=>$rule_id,'rule_type'=>'default'),0,-1,'obj_id DESC');
            $first_city_id = array();
            foreach($rule_obj as $k=>$v){
                $first_city_id[]=$v['region_id'];
            }
            $region_del = array_diff($first_city_id,$data['first_city']);
            $region_add = array_diff($data['first_city'],$first_city_id);
            if($region_del){
                $this->deleteRule($rule_id,$region_del,'default',1);
                $region_del = implode(',',$region_del);
                $region_obj = $this->app->model('rule_obj')->regionFilter($region_del,$data['branch_id']);
                if($region_obj) {
                    if($data['deleteareaflag']==0){#只删除默认规则，排它规则仍保留
                        $region_obj = implode(',',$region_obj);
                        $childsql = 'UPDATE sdb_logistics_rule_obj SET rule_id=0 WHERE obj_id in ('.$region_obj.')';
                        $this->db->exec($childsql);
                    }else if($data['deleteareaflag']==1){#删除其下所有子规则
                        foreach ($region_obj as $obj_id){
                            $this->app->model('rule_obj')->delete_rule($obj_id,'obj');
                        }
                    }
                }
            }
            $this->createRegionRule($data);
        }

    }

    //创建区域规则
    function createRegionRule($data){
        $regionLib = kernel::single('eccommon_regions');
        $first_city = $data['first_city'];
        $rule_id = $data['rule_id'];
        $branch_id = $data['branch_id'];
        $region_id_list = implode(',',$first_city);
        $weight_conf = array();
        if($data['set_type']=='weight'){
            foreach($data['min_weight'] as $k=>$v){
                $items_data = array();
                $items_data['min_weight'] = $v;
                $items_data['max_weight'] = $data['max_weight'][$k];
                $items_data['corp_id'] = $data['corp_id'][$k];
                $items_data['second_corp_id']=$data['second_corp_id'][$k];
                if($data['item_id']!=''){
                    $items_data['item_id'] = $data['item_id'][$k];
                }
                $this->app->model('rule_items')->save($items_data);
                $weight_conf[] = array(
                    'item_id'=>$items_data['item_id'],
                );
            }
        }else if($data['set_type']=='noweight'){
            $items_data = array();
            $items_data['corp_id']=(int)$data['default_corp_id'];
            $items_data['second_corp_id']=(int)$data['default_second_corp_id'];
            if($data['default_item_id']!=''){
                $items_data['item_id'] = $data['default_item_id'];
            }
            $this->app->model('rule_items')->save($items_data);
            $weight_conf[] = array(
                'item_id'=>$items_data['item_id'],
            );
        }
        foreach($first_city as $key=>$val){
            //obj 中区域唯一
            $rule_obj = $this->app->model('rule_obj')->dump(array('branch_id'=>$branch_id,'region_id'=>$val),'obj_id');
            if(empty($rule_obj)){
               $obj_data = array();
                $obj_data['rule_id'] = $rule_id;
                $obj_data['region_id'] = $val;
                $obj_data['region_grade']=1;
                $obj_data['rule_type']='default';
                $obj_data['set_type']=$data['set_type'];
                $obj_data['branch_id']=$data['branch_id'];
                $region = $regionLib->getList('local_name',array('region_id'=>$val),0,1);
                $obj_data['region_name'] = $region[0]['local_name'];
               $this->app->model('rule_obj')->save($obj_data);
            }else{
                #更新类型
                $obj_data = array(
                    'obj_id'=>$rule_obj['obj_id'],
                    'set_type'=>$data['set_type']
                );
                $this->app->model('rule_obj')->save($obj_data);
            }
            $obj_id = $obj_data['obj_id'];
            if($weight_conf){
                foreach($weight_conf as $wk=>$wv){
                    $item_id = $wv['item_id'];
                    $region_rule = $this->app->model('region_rule')->dump(array('item_id'=>$item_id,'region_id'=>$val),'*');
                    if(!$region_rule){
                        $this->db->exec("insert into sdb_logistics_region_rule(item_id,region_id,obj_id) VALUES($item_id,$val,$obj_id)");
                    }
                }
            }
        }
        #关联规则
        if($data['relationflag']==0){//0关联规则 1不关联规则
            $region_obj = $this->app->model('rule_obj')->regionFilter($region_id_list,$data['branch_id']);
            if($region_obj){
                $region_obj = implode(',',$region_obj);
                $childsql = 'UPDATE sdb_logistics_rule_obj SET rule_id='.$rule_id.' WHERE obj_id in ('.$region_obj.') AND rule_id=0';
                $this->db->exec($childsql);
            }
        }
        return true;
    }
    
    /**
     * 判断区域是否存在
     */
    function chkBranchRegion($branch_id,$region_id,$rule_id){

        $sql = 'SELECT o.obj_id FROM sdb_logistics_rule_obj as o LEFT JOIN sdb_logistics_rule as r ON o.rule_id=r.rule_id WHERE r.branch_id='.$branch_id .' AND o.region_id in ('.$region_id.')';
        if($rule_id!=''){
            $sql.=' AND o.rule_id!='.$rule_id;
        }

        $rule_obj = $this->db->select($sql);
        return $rule_obj;
    }

    /**
     * 建立父级仓库关联
     */

    function updateRule($data){

        $sql = 'UPDATE sdb_logistics_branch_rule SET parent_id='.$data['branch'].' WHERE branch_id='.$data['branch_id'];
        $rule = $this->db->exec($sql);
        //$this->branchRuleData(true);
        return $rule;
    }

    /**
     * 获取对应规则一级区域
     */
    function getRuleRegion($rule_id){
        $regionLib = kernel::single('eccommon_regions');
        $rule_list = $this->app->model('rule_obj')->getlist('region_id,region_name',array('rule_id'=>$rule_id,'rule_type'=>'default'),0,-1,'obj_id DESC');
        $region_list = array();
        foreach($rule_list as $k=>$v){
            $region_list[$k]['region_id'] = $v['region_id'];
            $region = $regionLib->getOneById($v['region_id'],'local_name');
            $region_list[$k]['region_name'] = $region['local_name'];
        }

        return $region_list;
    }



    function branchRuleData($return=false){
        $contents=$this->getMapBranchRule();
        base_kvstore::instance('logistics/branch/rule')->store('rule_data',$contents);

    }

    /**
     * 彻底删除规则
     * 删除规则时只删除所在仓库一级地区规则
     */

    function deleteRule($rule_id,$region_id='',$rule_type,$deleteareaflag='0',$branch_id=''){

        $sqlstr='';
        if($region_id){

            $region_id = implode(',',$region_id);
            $sqlstr.= ' AND region_id in ('.$region_id.')';
        }
        if($rule_type){
            $sqlstr.= ' AND rule_type=\''.$rule_type.'\'';
        }
        if($branch_id) {
            $sqlstr.=' AND branch_id='.$branch_id;
        }
        if($rule_id){
            $sqlstr.=' AND rule_id in ('.$rule_id.') ';
        }

        $sql = 'SELECT obj_id,region_id,branch_id FROM sdb_logistics_rule_obj where 1 '.$sqlstr;
        $obj_id_list = $this->db->select($sql);
        if($obj_id_list){
            $region_del = array();
            foreach($obj_id_list as $ok=>$ov){
                $obj_id[]=$ov['obj_id'];
                $region_del[$ov['region_id']] = $ov['branch_id'];
            }
            $obj_id=implode(',',$obj_id);

            $region_rule = $this->db->select('SELECT item_id FROM sdb_logistics_region_rule WHERE obj_id in ('.$obj_id.') group by item_id');
            $item_id = array();
            foreach($region_rule as $rk=>$rv){
                $item_id[] = $rv['item_id'];
            }
            $item_id = !empty($item_id) ? implode(',',$item_id) : '';
            if($deleteareaflag=='1'){
                #删除区域对象表
                $this->db->exec('delete from sdb_logistics_rule_obj WHERE obj_id in ('.$obj_id.')');
                #删除区域明细关联表
                $this->db->exec('delete from sdb_logistics_region_rule WHERE obj_id in ('.$obj_id.')');
                #因为明细表共用，查询明细表是否没有其它区域关联，如果是删除
                $region_rule_exist = $this->db->select('SELECT  item_id FROM sdb_logistics_region_rule WHERE item_id in ('.$item_id.')');
                if(!$region_rule_exist){
                    if($item_id){
                        $this->db->exec('delete from sdb_logistics_rule_items WHERE item_id in ('.$item_id.')');
                    }
                }
            }
            #更新规则表
            if($rule_type=='other' && $deleteareaflag=='0'){
                $childsql = 'UPDATE sdb_logistics_rule_obj SET rule_id=0 WHERE obj_id in ('.$obj_id.')';

                $this->db->exec($childsql);
            }
        }
        #


        }
        
    /*
     * 删除按店铺set_type=shop的类型 
     * $use_shop_ruleids 需要处理rule_id数组形式
     * $rule_table 是否删除主表数据（sdb_logistics_rule） 默认true
     */
    function deleteShopRule($use_shop_ruleids,$rule_table=true){
        if(empty($use_shop_ruleids)){
            return;
        }
        $mdl_rule = app::get('logistics')->model('rule');
        $mdl_rule_obj = app::get('logistics')->model('rule_obj');
        $mdl_rule_items = app::get('logistics')->model('rule_items');
        $mdl_rule_shop = app::get('logistics')->model('rule_shop');
        //获取obj_ids为了删除items表用
        $rs_obj = $mdl_rule_obj->getList("obj_id",array("rule_id|in"=>$use_shop_ruleids));
        $arr_obj_ids = array();
        foreach ($rs_obj as $var_obj){
            $arr_obj_ids[] = $var_obj["obj_id"];
        }
        $str_obj_ids = implode(",", $arr_obj_ids);
        $this->db->exec('delete from sdb_logistics_rule_items WHERE obj_id in ('.$str_obj_ids.')');
        $str_rule_ids = implode(",",$use_shop_ruleids);
        if ($rule_table){
            $this->db->exec('delete from sdb_logistics_rule WHERE rule_id in ('.$str_rule_ids.')');
        }
        $this->db->exec('delete from sdb_logistics_rule_obj WHERE rule_id in ('.$str_rule_ids.')');
        $this->db->exec('delete from sdb_logistics_rule_shop WHERE rule_id in ('.$str_rule_ids.')');
    }
    
    //新建按店铺set_type=shop的类型相关表的数据
    function insertShopRule($rule_id,$data){
        $mdl_rule_obj = app::get('logistics')->model('rule_obj');
        $mdl_rule_items = app::get('logistics')->model('rule_items');
        $mdl_rule_shop = app::get('logistics')->model('rule_shop');
        $obj_arr = array(
                "rule_id" => $rule_id,
                "set_type" => "shop",
                "branch_id" => $data['branch_id'],
                "rule_type" => "default",
        );
        $rule_obj_id = $mdl_rule_obj->insert($obj_arr);
        $items_arr = array(
                "obj_id" => $rule_obj_id,
                "corp_id" => $data['shop_corp_id'],
        );
        $mdl_rule_items->insert($items_arr);
        foreach ($data["shop_selected"] as $var_shop_id){
            $temp_insert_arr = array(
                    "rule_id" => $rule_id,
                    "shop_id" => $var_shop_id,
                    "branch_id" => $data['branch_id']
            );
            $mdl_rule_shop->insert($temp_insert_arr);
        }
    }



}
?>