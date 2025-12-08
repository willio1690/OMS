<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_product_serial{

    //执行导入
    function process(){
        //处理文件
        $file_info = $this->upload_file();
        if($file_info['rsp'] == 'fail'){
            return $file_info;
        }
        //检查数据有效性 并返回可导入数据和存在的数据信息 
        $rs_check = $this->valid_data($file_info["data"]);
        if($rs_check['rsp'] == 'fail'){
            return $rs_check;
        }
        //执行唯一码导入（走队列）
        $rs_import = $this->do_import_serial_queue($rs_check["import_product_serials_data"]);
        if($rs_import['rsp'] == 'fail'){
            return $rs_import;
        }
        //存在warning信息的话拼接弹窗信息
        $message = "上传成功 已加入队列 系统会自动跑完队列";
        if(!empty($rs_check['warning'])){
            $message .= "\n".app::get('desktop')->_('但是存在以下问题')."\n";
            if(!empty($rs_check['warning']["exist_product_serials"])){
                $message .= "这些唯一码在相应的仓库下已存在：".implode("，",$rs_check['warning']["exist_product_serials"]);
            }
            if(!empty($rs_check['warning']["exist_product_serials_other_branch"])){
                $message .= "这些唯一码在其他的仓库下已存在：".implode("，",$rs_check['warning']["exist_product_serials_other_branch"]);
            }
        }
        $rs_import["message"] = $message;
        return $rs_import;
    }
    
    /*
     * 唯一码导入（走队列）
     * $import_product_serials_data 可导入的数据集
     */
    private function do_import_serial_queue($import_product_serials_data){
        //分片数组走队列
        $number = $page = 0;
        $limit = 2;
        $sdfs = array();
        foreach($import_product_serials_data as $var_ipsd){
            if ($number < $limit){
                $number++;
            }else{
                $page++;
                $number = 0;
            }
            $sdfs[$page][] = $var_ipsd;
        }
        //加入队列任务
        $oQueue = app::get('base')->model('queue');
        foreach ($sdfs as $i){
            $queueData = array(
                    'queue_title'=>'唯一码导入',
                    'start_time'=>time(),
                    'params'=>array(
                            'sdfdata'=>$i,
                            'app' => 'wms',
                            'mdl' => 'product_serial'
                    ),
                    'worker'=>'wms_product_serial_import.run',
            );
            $oQueue->save($queueData);
        }
        
        //测试不走队列
//         $mdl_ome_ps = app::get('wms')->model('product_serial');
//         $operationLogObj        = app::get('ome')->model('operation_log');
//         foreach($sdfs as $var_sdf){
//             foreach($var_sdf as $var_sdf_v2){
//                 $insert_arr = array_merge(array("create_time"=>time()),$var_sdf_v2);
//                 $mdl_ome_ps->insert($insert_arr);
//                 //write log import serial
//                 $operationLogObj->write_log('product_serial_import@wms',$insert_arr['serial_id'],'唯一码导入');
//             }
//         }
        //成功
        return kernel::single('ome_func')->getApiResponse();
    }
    
    //检查数据有效性 并返回可导入数据
    private function valid_data($content_arr){
        $ome_func_lib = kernel::single('ome_func');
        //先判断是否有空值
        foreach($content_arr as $var_ca){
            if (!trim($var_ca[0]) || !trim($var_ca[1]) || !trim($var_ca[2])){
                $empty_error = "仓库名称、货号、唯一码都不能为空。";
                break;
            }
        }
        if($empty_error){
            return $ome_func_lib->getErrorApiResponse($empty_error);
        }
        $import_product_serials_data = array(); //可导入的唯一码数据数组
        $exist_product_serials = array(); //当前仓库中存在的唯一码
        $exist_product_serials_other_branch = array(); //其他仓库中存在不作废的唯一码
        $import_product_serials = array(); //可导入的唯一码
        $duplicate_product_serials = array(); //导入数据中重复的唯一码
        //再验证数据
        $mdl_ome_branch = app::get('ome')->model('branch');
        $mdl_ma_ba = app::get('material')->model('basic_material');
        $mdl_ome_branch_product = app::get('ome')->model('branch_product');
        $mdl_wms_product_serial = app::get('wms')->model('product_serial');

        $error_arr = array();
        foreach ($content_arr as $ca){
            $row_data = $this->get_row_data_by_position($ca);
            //验证唯一码格式（同编码）
            $reg_bn_code = "/^[0-9a-zA-Z\_\-]*$/";
            if(!preg_match($reg_bn_code,$row_data["serial_number"])){
                $error_arr[] = "唯一码：".$row_data["serial_number"]."格式不对。只能是数字英文下划线组成。";
            }
            $rs_branch = $mdl_ome_branch->dump(array("name"=>$row_data["branch_name"],"b_type"=>1)); //目前只支持电商线上仓
            if(empty($rs_branch)){
                $error_arr[] = "仓库：".$row_data["branch_name"]."不存在。";
            }
            $rs_material = $mdl_ma_ba->dump(array("material_bn"=>$row_data["basic_ma_bn"]));
            if(empty($rs_material)){
                $error_arr[] = "货号：".$row_data["basic_ma_bn"]."不存在。";
            }
            $rs_branch_product = $mdl_ome_branch_product->dump(array("branch_id"=>$rs_branch["branch_id"],"product_id"=>$rs_material["bm_id"]));
            if(empty($rs_branch_product)){
                $error_arr[] = "仓库：".$row_data["branch_name"]."下的货号：".$row_data["basic_ma_bn"]."不存在。";
            }
            //获取可导入的唯一码
            $rs_ps = $mdl_wms_product_serial->dump(array("serial_number"=>$row_data["serial_number"], "branch_id"=>$rs_branch["branch_id"]));
            if(!empty($rs_ps)){
                if(!in_array($row_data["serial_number"],$exist_product_serials)){
                    $exist_product_serials[] = $row_data["serial_number"];
                }
            }elseif(in_array($row_data["serial_number"],$import_product_serials)){
                if(!in_array($row_data["serial_number"],$duplicate_product_serials)){
                    $duplicate_product_serials[] = $row_data["serial_number"];
                }
            }else{
                $could_import = true;
                //判断其他其他仓库中是否存在此唯一码
                $rs_dead_ps = $mdl_wms_product_serial->getList("status",array("serial_number"=>$row_data["serial_number"],"branch_id|noequal"=>$rs_branch["branch_id"]));
                if(!empty($rs_dead_ps)){ //如果存在
                    foreach($rs_dead_ps as $var_d_p){
                        if($var_d_p["status"] != "2"){ //状态status不是2已作废状态的 不能导入
                            $could_import = false;
                            $exist_product_serials_other_branch[] = $row_data["serial_number"];
                            break;
                        }
                    }
                }
                if($could_import){
                    $import_product_serials[] = $row_data["serial_number"];
                    $import_product_serials_data[] = array(
                        "branch_id" => $rs_branch_product["branch_id"], //仓库id
                        "product_id" => $rs_material["bm_id"], //基础物料id
                        "bn" => $rs_material["material_bn"], //基础物料bn
                        "serial_number" => $row_data["serial_number"], //唯一码
                    );
                }
            }
        }
        //确定导入数据中是否有重复唯一码 有则返回error
        if(!empty($duplicate_product_serials)){
            $error_arr[] = "导入唯一码中有重复数据：".implode("，",$duplicate_product_serials)."。";
        }
        if(empty($import_product_serials)){
            $error_arr[] = "可导入有效唯一码为空。";
        }
        if(!empty($error_arr)){ //有错误信息
            return $ome_func_lib->getErrorApiResponse($error_arr);
        }else{ //无错误信息
            $succ_return = $ome_func_lib->getApiResponse($error_arr);
            $succ_return["import_product_serials_data"] = $import_product_serials_data;
            if(!empty($exist_product_serials)){
                $succ_return["warning"]["exist_product_serials"] = $exist_product_serials;
            }
            if(!empty($exist_product_serials_other_branch)){
                $succ_return["warning"]["exist_product_serials_other_branch"] = $exist_product_serials_other_branch;
            }
            return $succ_return;
        }
    }
    
    //处理上传文件并返回结果数据
    private function upload_file(){
        $ome_func_lib = kernel::single('ome_func');
        if(!$_FILES['import_file']['name']){
            return $ome_func_lib->getErrorApiResponse("未上传文件");
        }
        $oIo = kernel::servicelist('desktop_io');
        foreach( $oIo as $aIo ){
            if( $aIo->io_type_name == substr($_FILES['import_file']['name'],-3 ) ){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);
        if( !$oImportType ){
            return  $ome_func_lib->getErrorApiResponse("导入格式不正确");
        }
        //获取内容
        $tmpFileHandle = fopen( $_FILES['import_file']['tmp_name'],"r" );
        $contents = array();
        $oImportType->fgethandle($tmpFileHandle,$contents);
        fclose($tmpFileHandle);
        //去除标题行数据
        unset($contents[0]);
        //获取首列内容不为空的有效数据
        $real_contents = array();
        foreach($contents as $row){
            if(!empty($row[0])){
                $real_contents[] = $row;
            }
        }
        if(empty($real_contents)){
            return  $ome_func_lib->getErrorApiResponse("导入数据项为空");
        }else{
            return  $ome_func_lib->getApiResponse($real_contents);
        }
    }
    
    //根据位置获取导入数据
    private function get_row_data_by_position($row_arr){
        return array(
            "branch_name" => trim($row_arr[0]),
            "basic_ma_bn" => trim($row_arr[1]),
            "serial_number" => trim($row_arr[2]),
        );
    }
    
    //导出模板内容
    function exportTemplate(){
        $arr = array('*:仓库名称','*:货号','*:唯一码');
        foreach ($arr as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    /**
     *
     * 根据传入信息作废唯一码
     * @param Int $serial_id 唯一码ID
     */
    public function cancelSerial($serial_id){
        if(!$serial_id){
            return false;
        }

        $prdSerialObj    = app::get('wms')->model('product_serial');
        $rs = $prdSerialObj->update(array('status'=>2,'update_time'=>time()), array('serial_id'=>$serial_id));
        if(is_numeric($rs) && $rs > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据传入信息上架唯一码
     * @param Int $serial_id 唯一码ID
     */
    public function renewSerial($serial_id,&$message){
        if(!$serial_id){
            return false;
        }
        $prdSerialObj    = app::get('wms')->model('product_serial');
        
        //需判断当前唯一码的值 是否有非作废的数据
        $rs_serial = $prdSerialObj->dump(array('serial_id'=>$serial_id));
        $rs_used_serial = $prdSerialObj->dump(array("serial_number"=>$rs_serial["serial_number"],"status|noequal"=>"2","branch_id"=>$rs_serial["branch_id"]));
        if(!empty($rs_used_serial)){
            $message = "当前唯一码已用";
            return false;
        }
        
        $rs = $prdSerialObj->update(array('status'=>0,'update_time'=>time()), array('serial_id'=>$serial_id));
        if(is_numeric($rs) && $rs > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据传入信息预占唯一码
     * @param Int $serial_id 唯一码ID
     */
    public function freezeSerial($serial_id){
        if(!$serial_id){
            return false;
        }

        $prdSerialObj    = app::get('wms')->model('product_serial');
        $rs = $prdSerialObj->update(array('status'=>4,'update_time'=>time()), array('serial_id'=>$serial_id,'status'=>'0'));
        if(is_numeric($rs) && $rs > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据传入信息释放唯一码
     * @param Int $serial_id 唯一码ID
     */
    public function unfreezeSerial($serial_id){
        if(!$serial_id){
            return false;
        }

        $prdSerialObj    = app::get('wms')->model('product_serial');
        $rs = $prdSerialObj->update(array('status'=>0,'update_time'=>time()), array('serial_id'=>$serial_id));
        if(is_numeric($rs) && $rs > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据传入信息出库唯一码
     * @param Int $serial_id 唯一码ID
     */
    public function outStorage($serial_id){
        if(!$serial_id){
            return false;
        }

        $prdSerialObj    = app::get('wms')->model('product_serial');
        $rs = $prdSerialObj->update(array('status'=>1,'update_time'=>time()), array('serial_id'=>$serial_id));
        if(is_numeric($rs) && $rs > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据传入信息退入唯一码
     * @param Int $serial_id 唯一码ID
     */
    public function returnStorage($serial_id){
        if(!$serial_id){
            return false;
        }

        $prdSerialObj    = app::get('wms')->model('product_serial');
        $rs = $prdSerialObj->update(array('status'=>3,'update_time'=>time()), array('serial_id'=>$serial_id));
        if(is_numeric($rs) && $rs > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * 根据传入信息退入唯一码进新仓
     * @param Array $sdf 唯一码ID
     */
    public function returnStorageToNewBranch($params){
        if(!$params){
            return false;
        }

        $nowTime = time();

        $prdSerialObj    = app::get('wms')->model('product_serial');
        $serialData = array(
            'branch_id' => $params['branch_id'],
            'product_id' => $params['product_id'],
            'bn' => $params['bn'],
            'serial_number' => $params['serial_number'],
            'status' => 3,
            'create_time' => $nowTime,
            'update_time' => $nowTime
         );

        $rs = $prdSerialObj->insert($serialData);
        if($rs){
            return $serialData['serial_id'];
        }else{
            return false;
        }
    }
    
    //扫码入库检查
    public function check_serial_import($serial_number,$branch_id,$basic_material_bn){
        $return_result = array("result"=>false);
        if(!$serial_number || !$branch_id || !$basic_material_bn){
            $return_result["message"] = "仓库、基础物料、唯一码都不能为空。";
            return $return_result;
        }
        $mdl_ome_branch = app::get('ome')->model('branch');
        $mdl_ma_ba = app::get('material')->model('basic_material');
        $mdl_ome_branch_product = app::get('ome')->model('branch_product');
        $mdl_wms_product_serial = app::get('wms')->model('product_serial');
        //验证唯一码格式（同编码）
        $reg_bn_code = "/^[0-9a-zA-Z\_\-]*$/";
        if(!preg_match($reg_bn_code,$serial_number)){
            $return_result["message"] = "唯一码格式不对。只能是数字英文下划线组成。";
            return $return_result;
        }
        $rs_material = $mdl_ma_ba->dump(array("material_bn"=>$basic_material_bn));
        if(empty($rs_material)){
            $return_result["message"] = "当前基础物料不存在。";
            return $return_result;
        }
        $rs_branch_product = $mdl_ome_branch_product->dump(array("branch_id"=>$branch_id,"product_id"=>$rs_material["bm_id"]));
        if(empty($rs_branch_product)){
            $return_result["message"] = "当前仓库中没有此基础物料货品。";
            return $return_result;
        }
        //获取可导入的唯一码
        $rs_ps = $mdl_wms_product_serial->dump(array("serial_number"=>$serial_number,"branch_id"=>$branch_id));
        if(!empty($rs_ps)){
            $return_result["message"] = "当前仓库中已存在此唯一码。[$serial_number]";
            return $return_result;
        }
        $could_import = true;
        //判断其他其他仓库中是否存在此唯一码
        $rs_dead_ps = $mdl_wms_product_serial->getList("status",array("serial_number"=>$serial_number,"branch_id|noequal"=>$branch_id));
        if(!empty($rs_dead_ps)){ //如果存在
            foreach($rs_dead_ps as $var_d_p){
                if($var_d_p["status"] != "2"){ //状态status不是2已作废状态的 不能导入
                    $could_import = false;
                    break;
                }
            }
        }
        if($could_import){
            $return_result["result"] = true;
            $return_result["product_id"] = $rs_material["bm_id"];
            return $return_result;
        }else{
            $return_result["message"] = "其他仓库中已存在非作废状态的当前唯一码信息。";
            return $return_result;
        }
    }
    
}
