<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class taoexlib_ietask_import{

    /**
     * 保存File
     * @return mixed 返回操作结果
     */
    public function saveFile(){
		//$this->_ftpSaveFile();
	    $data = $this->_localSaveFile();
	    
	    return $data;
	}
	
    /**
     * parse
     * @param mixed $ietask_cfg_id ID
     * @return mixed 返回值
     */
    public function parse($ietask_cfg_id){
		$data = $this->saveFile();
		if(!empty($data))return false;
		
		
		$oIeImport = $this->app->model('ietask_import');
		$importData = array();
		
		return $oIeImport->save($importData);
	}
	
	/*
	 * $data = array(
	 * 			'total'=>1,
	 * 			'pages'=>1,
	 * 			'page'=>1,
	 * 			''
	 * );
	 * 
	 * 
	 * 
	 */

    public function preview(){
		$this->process();
	}
	
    /**
     * 处理
     * @param mixed $ietask_import_id ID
     * @return mixed 返回值
     */
    public function process($ietask_import_id){
		$oIeCfg = $this->app->model('ietask_cfg');
		$cfg = $oIeCfg->getCfg($ietask_cfg_id);
		$xml_data = kernel::single('taoexlib_xml')->xml2array($cfg['xml_data']);
		$rows = $this->_mapDataByXml($xml_data,$data);
		
	}
	
	private function _ftpSaveFile(){
		
	}
	
	private function _localSaveFile(){
	   if( !$_FILES['import_file']['name'] ){
            echo '<script>top.MessageBox.error("上传失败");alert("未上传文件");</script>';
            exit;
        }
        $tmpFileHandle = fopen( $_FILES['import_file']['tmp_name'],"r" );
       
        $mdl = $_POST['model'];
        $app_id = $_POST['app'];

        $oIo = kernel::servicelist('desktop_io');
        foreach( $oIo as $aIo ){
            if( $aIo->io_type_name == substr($_FILES['import_file']['name'],-3 ) ){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);
        if( !$oImportType ){
            echo '<script>top.MessageBox.error("上传失败");alert("导入格式不正确");</script>';
            exit;
        }
        
        $contents = array();
        $oImportType->fgethandle($tmpFileHandle,$contents);
        $newFileName = $app_id.'_'.$mdl.'_'.$_FILES['import_file']['name'].'-'.time();
 
        base_kvstore::instance($app_id.'_'.$mdl)->store($newFileName,serialize($contents));
        base_kvstore::instance($app_id.'_'.$mdl)->store($newFileName.'_sdf',serialize(array()));
        base_kvstore::instance($app_id.'_'.$mdl)->store($newFileName.'_error',serialize(array()));

        fclose($tmpFileHandle);
	}
	
	private function _mapDataByXml($xml,$data){
		
	}
	
	private function _checkData($xml,$data){
		
	}
}
