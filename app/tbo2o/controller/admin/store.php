<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_store extends desktop_controller {

    var $name = "淘宝门店管理";
    var $workground = "tbo2o_center";

    function index() {
        $params = array(
            'title'=>'淘宝门店管理',
            'actions' => array(
                array(
                    'label' => '获取本地门店',
                    'target'=>'dialog::{title:\'获取本地门店\'}','href'=>'index.php?app=tbo2o&ctl=admin_store&act=syncStore&p[0]=local',
                ),
                array(
                    'label' => '推送到淘宝',
                    'submit' => 'index.php?app=tbo2o&ctl=admin_store&act=pushTbStore&finder_id='.$_GET['finder_id'],
                    'confirm' => '你确定要对勾选的门店推送到淘宝吗？', 
                    'target' => 'dialog::{title:\'推送到淘宝\'}',
                ),
                array(
                    'label' => '淘宝更新到本地',
                    'target'=>'dialog::{title:\'淘宝更新到本地\'}','href'=>'index.php?app=tbo2o&ctl=admin_store&act=syncStore&p[0]=tb',
                ),
            ),
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
        );

        $this->finder('tbo2o_mdl_store',$params);
    }

    /**
     * syncStore
     * @param mixed $syncType syncType
     * @return mixed 返回值
     */
    public function syncStore($syncType=''){

        $syncType = $syncType ? $syncType : $_GET['syncType'];
        $refresh = false;

        switch($syncType){
            case 'local':
                $title = '本地门店信息';
                $url = 'index.php?app=tbo2o&ctl=admin_store&act=getLocalStore';
                $refresh = true;
                break;
            case 'tb':
                $title = '淘宝更新到本地';
                $url = 'index.php?app=tbo2o&ctl=admin_store&act=getTbStore';
                $refresh = true;
                break;
        }

        $this->pagedata['title'] = $title;
        $this->pagedata['url'] = $url;
        $this->pagedata['refresh'] = $refresh;

        $_POST['time'] = time();
        if ($_POST) {
            $inputhtml = '';
            $post = http_build_query($_POST);
            $post = explode('&', $post);
            foreach ($post as $p) {
                list($name,$value) = explode('=', $p);
                $params = array(
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }

        $this->display('admin/store/sync_store.html');
    }
    
    //淘宝更新到本地 查询门店更新本地门店基础信息
    /**
     * 获取TbStore
     * @return mixed 返回结果
     */
    public function getTbStore(){
        $page = $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $limit = 1;
        $offset = ($page-1)*$limit;
        //逐个打门店查询接口 更新本地门店基础信息
        $filter = array("outer_store_id|noequal"=>"");
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        $storeList = $mdlTbo2oStore->getList('outer_store_id,area', $filter, $offset, $limit);
        $outer_store_id = $storeList[0]["outer_store_id"];
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreQuery($outer_store_id,$errormsg);
        if(!$return_result){
            //失败 $errormsg
            $this->splash('error',null,$errormsg);
        }
        //更新tbo2o_store表数据
        $ud_tb_store_filter = array("outer_store_id"=>$outer_store_id);
        //获取更新的area字段
        $area = kernel::single('tbo2o_common_tbo2oapi')->getAreaFromTbAddress($return_result["address"],$storeList[0]["area"]);
        $ud_tb_store_arr = array(
            "store_name" => $return_result["storeName"],
            "cat_id" => $return_result["mainCategory"],
            "store_type" => strtolower($return_result["storeType"]),
            "open_hours" => $return_result["startTime"]."-".$return_result["endTime"],
            "status" => strtolower($return_result["storeStatus"]),
            "contacter" => $return_result["storeKeeper"]["name"],
            "tel" => $return_result["storeKeeper"]["tel"],
            "mobile" => $return_result["storeKeeper"]["mobile"],
            "fax" => $return_result["storeKeeper"]["fax"],
            "zip" => $return_result["storeKeeper"]["zipCode"],
            "address" => $return_result["address"]["detailAddress"],
            "area" => $area,
        );
        $mdlTbo2oStore->update($ud_tb_store_arr,$ud_tb_store_filter);
        //进度条
        $rate = 100;
        $totalResults = $mdlTbo2oStore->count($filter);
        $msg = '同步完成';
        $downloadStatus = 'running';
        # 判断是否已经全部下载完
        if($page >= ceil($totalResults/$limit) || $totalResults==0){
            $msg = '全部下载完';
            $downloadStatus = 'finish';
            $downloadRate = $rate;
        } else {
            $downloadRate = $page*$limit/$totalResults*$rate;
        }
        $this->splash('success',null,$msg,'redirect',array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>intval($downloadRate),'downloadStatus'=>$downloadStatus));
    }
    
    /**
     * 获取本地门店数据转换成淘宝门店信息
     * 
     * @param string $page
     * @return json obj
     */
    public function getLocalStore(){
        $page = $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $limit = 50;
        $offset = ($page-1)*$limit;

        //获取阿里全渠道的server_id
        $mdlO2oServer = app::get('o2o')->model('server');
        $rs_o2o_server = $mdlO2oServer->dump(array("type"=>"taobao"),"server_id");
        //每次取50条门店记录进行处理
        $tmp_bns = $tmp_tbbns = $pending = array();
        $storeObj = app::get('o2o')->model('store');
        //这里只取服务端是阿里全渠道的门店
        $storeList = $storeObj->getList('*', array("server_id"=>$rs_o2o_server["server_id"]), $offset, $limit);
        if($storeList){
            foreach($storeList as $store){
                $tmp_bns[] = $store['store_bn'];
                $pending[$store['store_bn']] = $store;
            }

            $tbstoreObj = app::get('tbo2o')->model('store');
            $tbstoreList = $tbstoreObj->getList('store_bn', array('store_bn'=>$tmp_bns), 0, -1);
            if($tbstoreList){
                foreach($tbstoreList as $tbstore){
                    $tmp_tbbns[] = $tbstore['store_bn'];
                }

                $add_bns = array_diff($tmp_bns, $tmp_tbbns);
            }else{
                $add_bns = $tmp_bns;
            }

            if($add_bns){
                $sql = 'insert into sdb_tbo2o_store(store_name,store_bn,local_store_id,status,open_hours,contacter,area,address,tel,mobile,fax,zip) values ';
                foreach($add_bns as $k => $add_bn){
                    if(isset($pending[$add_bn])){
                        $status = ($pending[$add_bn]['status'] == 1) ? 'normal' : 'close';
                        $sql_vals[] = "('".$pending[$add_bn]['name']."','".$pending[$add_bn]['store_bn']."',".$pending[$add_bn]['store_id'].",'".$status."','".$pending[$add_bn]['open_hours']."','".$pending[$add_bn]['contacter']."','".$pending[$add_bn]['area']."','".$pending[$add_bn]['addr']."','".$pending[$add_bn]['tel']."','".$pending[$add_bn]['mobile']."','".$pending[$add_bn]['fax']."','".$pending[$add_bn]['zip']."')";
                    }
                }

                $sqlInsert = $sql.implode(',',$sql_vals);
                if(!$tbstoreObj->db->exec($sqlInsert)){
                    $this->splash('error',null,'本地门店数据同步失败');
                 }
            }
        }

        $rate = 100;
        $totalResults = $storeObj->count();
        $msg = '同步完成';
        $downloadStatus = 'running';
        # 判断是否已经全部下载完
        if($page >= ceil($totalResults/$limit) || $totalResults==0){
            $msg = '全部下载完';
            $downloadStatus = 'finish';
            $downloadRate = $rate;
        } else {
            $downloadRate = $page*$limit/$totalResults*$rate;
        }
        $this->splash('success',null,$msg,'redirect',array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>intval($downloadRate),'downloadStatus'=>$downloadStatus));
    }

    /**
     * 淘宝门店信息编辑页
     * 
     * @param Int $store_id
     * @return Boolean
     */
    function edit($store_id){
        $this->begin('index.php?app=tbo2o&ctl=admin_store&act=index');
        if (empty($store_id)){
            $this->end(false,'操作出错，请重新操作');
        }

        $tbstoreObj = app::get('tbo2o')->model('store');

        $tmp_store_id = intval($store_id);
        $tbstoreInfo = $tbstoreObj->dump($tmp_store_id);
        if(!$tbstoreInfo){
            $this->end(false,'操作出错，请重新操作');
        }

        $this->pagedata['storeInfo'] = $tbstoreInfo;
        $this->singlepage('admin/store/edit.html');
    }

    /**
     * 淘宝门店信息编辑提交
     * 
     * @param Int $store_id
     * @return Boolean
     */
    function toEdit(){
        $this->begin('index.php?app=tbo2o&ctl=admin_store&act=index');
        //检查参数
        if(!$this->checkEditParams($_POST, $err_msg)){
            $this->end(false, $err_msg);
        }
        $tbstoreObj = app::get('tbo2o')->model('store');
        //检查是否有做数据变更
        $updata = false;
        $cat_id = $_POST['storecatSelected'];
        $store_type = $_POST['store_type'];
        $status = $_POST['status'];
        $old_data = $tbstoreObj->dump(array("store_id"=>$_POST['store_id']));
        if ($old_data["cat_id"] != $cat_id || $old_data["store_type"] != $store_type || $old_data["status"] != $status){
            $updata = true;
        }
        if($updata){
            //门店更新信息
            $updateData = array(
                    'cat_id' => $cat_id,
                    'store_type' => $store_type,
                    'status' => $status,
                    'sync' => 1, //同步状态更新为未同步
            );
            $filter['store_id'] = intval($_POST['store_id']);
            $tbstoreObj->update($updateData,$filter);
        }
        $this->end(true, '保存成功');
    }

    /**
     * 检查EditParams
     * @param mixed $data 数据
     * @param mixed $errormsg errormsg
     * @return mixed 返回验证结果
     */
    public function checkEditParams($data, &$errormsg){
        return true;
    }
    
    //单个门店新建接口
    /**
     * 添加Store
     * @param mixed $store_id ID
     * @return mixed 返回值
     */
    public function addStore($store_id){
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreCreate($store_id,$errormsg);
        if ($return_result){
            $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
            $this->splash('success',$url,"新建成功");
        }else{
            $this->splash('error', null, "新建失败：".$errormsg);
        }
    }
    
    //单个门店更新接口
    /**
     * 更新Store
     * @param mixed $store_id ID
     * @return mixed 返回值
     */
    public function updateStore($store_id){
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreUpdate($store_id,$errormsg);
        if ($return_result){
            $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
            $this->splash('success',$url,"更新成功");
        }else{
            $this->splash('error', null, "更新失败：".$errormsg);
        }
    }
    
    //单个门店删除接口
    /**
     * delStore
     * @param mixed $store_id ID
     * @return mixed 返回值
     */
    public function delStore($store_id){
        $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreDelete($store_id,$errormsg);
        if ($return_result){
            $url = 'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();';
            $this->splash('success',$url,"删除成功");
        }else{
            $this->splash('error', null, "删除失败：".$errormsg);
        }
    }
    
    //淘宝o2o全渠道接口 批量新增/更新门店接口
    /**
     * pushTbStore
     * @return mixed 返回值
     */
    public function pushTbStore(){
        //获取选取数据
        $this->_request = kernel::single('base_component_request');
        $data = $this->_request->get_post();
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        if ($data["isSelectedAll"] == "_ALL_"){
            //选择全部 拿出sync是1未同步或者2同步失败的store_id
            $data_list = $mdlTbo2oStore->getList("store_id",array("sync|in"=>array(1,2)));
        }else{
            //取选中的项 拿出sync是1未同步或者2同步失败的store_id
            $store_ids = $data['store_id'];
            $data_list = $mdlTbo2oStore->getList("store_id",array("sync|in"=>array(1,2),"store_id|in"=>$store_ids));
        }
        if(empty($data_list)){
            echo '没有需要推送的门店!';
            exit;
        }
        $store_ids = array();
        foreach ($data_list as $val_d_l){
            $store_ids[] = $val_d_l["store_id"];
        }
        //每次最多执行50条记录
        if(count($store_ids) > 50){
            echo '批量操作每次最多可以执行50条记录!';
            exit;
        }
        //加载批量模板
        $loadList[] = array('name'=>'推送到淘宝','flag'=>'all');
        //同步页面
        $url = 'index.php?app=tbo2o&ctl=admin_store&act=execPushTbStore';
        if ($_GET['redirectUrl']){
            $this->pagedata['redirectUrl'] = 'index.php?'.http_build_query($_GET['redirectUrl']);
        }
        $this->pagedata['url'] = $url;
        $this->pagedata['loadList'] = $loadList;
        
        $_POST = array();
        $_POST['time'] = time();
        $_POST['store_ids'] = json_encode($store_ids);
        if($_POST){
            $inputhtml = '';
            foreach ($_POST as $key => $val){
                $params = array(
                        'type' => 'hidden',
                        'name' => $key,
                        'value' => $val,
                );
                $inputhtml .= utils::buildTag($params,'input');
            }
            $this->pagedata['inputhtml'] = $inputhtml;
        }
        
        $this->display('admin/store/push_taobao_store.html');
    }
    
    //执行推送门店至淘宝
    function execPushTbStore(){
        //页码
        $page    = intval($_GET['page']);
        $page    = ($page > 0 ? $page : 1);
        $flag    = $_GET['flag'];
        
        $store_id_list = ($_POST['store_ids'] ? json_decode($_POST['store_ids'], true) : '');
        $totalResults  = count($store_id_list);
        if(empty($store_id_list)){
            $this->splash('error', null, '没有可执行的数据');
        }
        
        //已完成同步
        if($page > $totalResults){
            $msg        = '同步完成';
            $msgData    = array('errormsg'=>'', 'totalResults'=>$totalResults, 'downloadRate'=>100, 'downloadStatus'=>'finish');
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
        
        //正在同步
        $store_id = $store_id_list[$page - 1];
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        $rs_tbo2o_store = $mdlTbo2oStore->dump(array("store_id"=>$store_id),"outer_store_id,sync");
        if($rs_tbo2o_store["sync"] == "1"){
            //未同步状态的走新建门店接口
            $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreCreate($store_id,$errormsg);
        }else{
            //sync==2 同步失败状态的不存在outer_store_id的走新建接口 存在的走更新接口
            if($rs_tbo2o_store["outer_store_id"]){
                $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreUpdate($store_id,$errormsg);
            }else{
                $return_result = kernel::single('tbo2o_common_tbo2oapi')->tbStoreCreate($store_id,$errormsg);
            }
        }
        if($return_result === false){
            $this->splash('error', null, $errormsg);
        }else{
            $msg = '正在同步中...';
            $downloadRate = ($page / $totalResults) * 100;
            $msgData = array('errormsg'=>$errormsg, 'totalResults'=>$totalResults, 'downloadRate'=>intval($downloadRate), 'downloadStatus'=>'running');
            $this->splash('success', null, $msg,'redirect', $msgData);
        }
    }
    
}