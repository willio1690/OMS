<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_ctl_admin_store_cat extends desktop_controller {

    var $name = "淘宝门店类目";
    var $workground = "tbo2o_center";

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $obj_cat_op = kernel::single('tbo2o_cat_operation');
        $this->path[]=array('text'=>'淘宝门店类目列表');
        $this->pagedata['cat'] = $obj_cat_op->getRegionById();
        $this->page('admin/store/cat/cat_treeList.html');
    }

    /**
     * 获取ChildNode
     * @return mixed 返回结果
     */
    public function getChildNode(){
		$obj_cat_op = kernel::single('tbo2o_cat_operation');
        $this->pagedata['cat'] = $obj_cat_op->getRegionById($_POST['catId']);
        $this->display('admin/store/cat/cat_sub_treeList.html');
    }

    /**
     * syncTbStoreCat
     * @param mixed $syncType syncType
     * @return mixed 返回值
     */
    public function syncTbStoreCat($syncType=''){
        $syncType = $syncType ? $syncType : $_GET['syncType'];
        $refresh = false;
        
        switch($syncType){
            case 'taobao':
                $title = '淘宝门店类目';
                $url = 'index.php?app=tbo2o&ctl=admin_store_cat&act=getTbStoreCat';
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
        
        $this->display('admin/store/cat/sync_store_cat.html');
    }
    
    /**
     * 获取淘宝门店类目数据更新类目表
     * @return json obj
     */
    public function getTbStoreCat(){
        //淘宝类目接口
        $cat_param = array("remark"=>"");
        $rt_cat = kernel::single('tbo2o_event_trigger_store')->storecategoryGet($cat_param);
        $data = json_decode($rt_cat["data"],true);
        //打接口失败
        if ($data["succ"][0]["response"]["flag"] != "success"){
            $this->splash('error',null,'同步淘宝类目失败');
        }
        //淘宝门店类目表
        $mdlTbo2oStoreCat = app::get('tbo2o')->model('store_cat');
        $storeCategory = json_decode($data["succ"][0]["response"]["storeCategory"],true);
        if(empty($storeCategory) || !$storeCategory){
            $this->splash('error',null,'同步淘宝类目失败，无返回数据。');
        }
        //先清除所有门店类目
        $clear_sql = "truncate table ".kernel::database()->prefix."tbo2o_store_cat";
        $mdlTbo2oStoreCat->db->exec($clear_sql);
        //获取多层类目信息
        $result_arr = array();
        foreach ($storeCategory as $var_c){
            $haschild = 0;
            if (isset($var_c["subCategorys"])){
                $haschild = 1;
            }
            $result_arr[] = $this->combineStoreInfoArr($var_c["id"],$var_c["name"],"",$var_c["id"],1,$haschild);
            //开始递归获取类目信息
            $this->getStoreInfo($var_c["subCategorys"],$var_c["id"],$var_c["id"],2,$result_arr);
        }
        //新建淘宝类目
        foreach ($result_arr as $rs_a){
            $mdlTbo2oStoreCat->insert($rs_a);
        }
        $rate = 100;
        $totalResults = count($result_arr);
        $downloadStatus = 'finish';
        $this->splash('success',null,$msg,'redirect',array('errormsg'=>$errormsg,'totalResults'=>$totalResults,'downloadRate'=>intval($rate),'downloadStatus'=>$downloadStatus));
    }
    
    //组类目insert数组元素
    private function combineStoreInfoArr($cat_id,$cat_name,$p_stc_id,$cat_path,$cat_grade,$haschild){
        return array(
            "cat_id" => $cat_id,
            "cat_name" => $cat_name,
            "p_stc_id" => $p_stc_id,
            "cat_path" => $cat_path,
            "cat_grade" => $cat_grade,
            "haschild" => $haschild,
        );
    }
    
    //递归获取类目多级信息
    private function getStoreInfo($subCategorys,$p_stc_id,$cat_path,$cat_grade,&$result_arr){
        if(empty($subCategorys)){
            return;
        }
        $cur_cat_grade = $cat_grade+1;
        foreach ($subCategorys as $var_c){
            $cur_path = $cat_path.",".$var_c["id"];
            $haschild = 0;
            if (isset($var_c["subCategorys"])){
                $haschild = 1;
            }
            $result_arr[] = $this->combineStoreInfoArr($var_c["id"],$var_c["name"],$p_stc_id,$cur_path,$cat_grade,$haschild);
            if(isset($var_c["subCategorys"])){
                $this->getStoreInfo($var_c["subCategorys"],$var_c["id"],$cur_path,$cur_cat_grade,$result_arr);
            }
        }
    }
    
    function selTbo2oStoreCat(){
        $path = $_GET['path'];
        $depth = $_GET['depth'];
        $params = array('depth'=>$depth);
        $ret = kernel::single('tbo2o_cat_select')->get_cat_select($path,$params);
        if($ret){
            echo '&nbsp;-&nbsp;'.$ret;exit;
        }else{
            echo '';exit;
        }
    }
    
}