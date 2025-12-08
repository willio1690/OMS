<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class taoexlib_ctl_ietask extends desktop_controller{
    
    function index(){
        if(!kernel::single('desktop_user')->has_permission('taoexlib_ietask')){
            header('Content-Type:text/html; charset=utf-8');
            echo app::get('desktop')->_("您无权操作");exit;
        }
        //清除到期下载任务
        kernel::single('taoexlib_ietask')->clean();

        $base_filter = array();
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $base_filter['op_id'] = kernel::single('desktop_user')->get_id();
        }

        $this->finder(
            'taoexlib_mdl_ietask',
            array(
                'title'=>'导出任务列表',
                'base_filter'=>$base_filter,
                'use_buildin_export'=>false,
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_tagedit'=>true,
                'allow_detail_popup'=>false,
                'use_view_tab'=>false,
                'use_buildin_recycle'=>true,
                'orderBy'=>' task_id desc ',
                'use_buildin_recycle'=>false,
                'actions' => array(
                    array('label'=>'删除','submit'=>'index.php?app=taoexlib&ctl=ietask&act=delTask'),
                ),
            )
        );
    }

    //直接删除导出任务
    /**
     * delTask
     * @return mixed 返回值
     */
    public function delTask(){
        $this->begin('index.php?app=taoexlib&ctl=ietask&act=index');
        $ietaskObj = app::get('taoexlib')->model('ietask');

        $ids = array();
        if(isset($_POST['task_id']) && is_array($_POST['task_id']) && (count($_POST['task_id'])>0)) {
            $ids = $_POST['task_id'];
        }elseif(isset($_POST['isSelectedAll']) && ($_POST['isSelectedAll'] == '_ALL_')){//全选
            unset($_POST['app']);
            unset($_POST['ctl']);
            unset($_POST['act']);
            unset($_POST['flt']);
            unset($_POST['_finder']);
            $filter = $_POST;
            $task_ids = $ietaskObj->getList('task_id',$filter);
            foreach($task_ids as $id){
                $ids[] = $id['task_id'];
            }
        }

        if(!empty($ids)){
            //当前导出文件存储模式
            $storageLib = kernel::single('taskmgr_interface_storage');
            $isLocalStorage = $storageLib->isLocalMode();
            $ietaskLib = kernel::single('taoexlib_ietask');

            $rows = $ietaskObj->getList('task_id,status,file_name', array('task_id'=>$ids), 0, -1);
            foreach ($rows as $task){
                $cacheLib = kernel::single('taskmgr_interface_cache',$task['task_id']);
                $isLocalCache = $cacheLib->isLocalMode();
                //数据缓存是本地的，删除本地缓存
                if($isLocalCache){
                    $ietaskLib->delDirAndFile(DATA_DIR.'/export/cache/'.$task['task_id'], false);
                }

                //如果任务没有执行完成被删除，则标记任务已被删除的缓存状态标记，以便队列任务直接跳出不执行占用资源
                if($task['status'] != 'finished'){
                    $cacheLib->store('exp_task_'.$task['task_id'].'_status', 'del', 600);
                }

                //识别是否存在本地文件
                if($isLocalStorage && !empty($task['file_name'])){
                    @unlink(DATA_DIR.'/export/file/'.$task['file_name']);
                }
                
                // 删除远程文件
                $storageLib->delete($task['file_name']);

                //删除任务
                $ietaskObj->remove($task['task_id']);
            }
        }

       $this->end(true, $this->app->_('删除成功'));
    }

    function download($id){
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $ietaskObj = app::get("taoexlib")->model("ietask");
        $task_info = $ietaskObj->dump(array('task_id'=>$id),'file_name,task_name,filter_data,op_id');
        $is_super = kernel::single('desktop_user')->is_super();
        if(!$is_super) {
            if ($task_info['op_id'] != kernel::single('desktop_user')->get_id()) {
                echo app::get('desktop')->_("您无权操作");exit;
            }
        }
        $filter = @unserialize($task_info['filter_data']);
        $storageLib = kernel::single('taskmgr_interface_storage');
        $local_file = DATA_DIR.'/export/tmp_local/'.md5(microtime().KV_PREFIX).$id;
        if($filter['_io_type'] == 'xls') {
            $local_file .= '.xlsx';
        } else {
            $local_file .= '.csv';
        }
        //记录导出用户记录
        $exportData = array();
        $exportData['task_name'] = $task_info['task_name'];
        $exportData['file_name'] = $task_info['file_name'];
        $exportData['op_id'] = kernel::single('desktop_user')->get_id();
        $exportData['create_time'] = time();
        app::get('taoexlib')->model('export_history')->insert($exportData);
        
        $getfile_res = $storageLib->get($task_info['file_name'],$local_file);
        if($getfile_res){
            $file_size = filesize($local_file);
            $file_name = $task_info['task_name']."-".$id;
            if($filter['_io_type'] == 'xls') {
                $file_name .= '.xlsx';
                header('Content-Type: application/vnd.ms-excel');
                header("Content-Disposition:attachment;filename=" . $file_name);
                header('Cache-Control: max-age=0');
            } else {
                $file_name .= '.csv';
                header("Content-type:text/html;charset=utf-8"); 
                Header("Content-type: application/octet-stream"); 
                Header("Accept-Ranges: bytes"); 
                Header("Accept-Length:".$file_size); 
                Header("Content-Disposition: attachment; filename=".$file_name); 
            }

            echo file_get_contents($local_file);

            @unlink($local_file);
        }
    }

    function import(){
        $base_filter = array();
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
        	$base_filter['op_id'] = kernel::single('desktop_user')->get_id();
        }
        
    	$this->finder(
	        'taoexlib_mdl_ietask_import',
	        array(
		        'title'=>'导入任务列表',
				'base_filter'=>$base_filter,
				'use_buildin_export'=>false,
		        'use_buildin_set_tag'=>false,
		        'use_buildin_filter'=>true,
		        'use_buildin_tagedit'=>true,
	        	'allow_detail_popup'=>false,
				'use_view_tab'=>false,
	        	'use_buildin_recycle'=>true,
                'orderBy'=>' task_id desc ',
	       	   // 'use_buildin_recycle'=>false,
	        	//'actions' => array(
                  // array('label'=>'导入','href'=>'index.php?app=taoexlib&ctl=ietask&act=toImport'),
           		//),
	        )
        );
    }
    
    function toImport(){
        $oIeCfg = $this->app->model('ietask_cfg');
        $cfgList = $oIeCfg->getCfgList($ietask_cfg_id,array('ietask_cfg_id','name'));
        $this->pagedata['cfgList'] = $cfgList;
        echo $this->page('ietask/import.html');
    }
    
    function doImport(){

        if( !$_FILES['import_file']['name'] ){
            echo '<script>top.MessageBox.error("上传失败");alert("未上传文件");</script>';
            exit;
        }
        $oQueue = app::get('base')->model('queue');
        $tmpFileHandle = fopen( $_FILES['import_file']['tmp_name'],"r" );
       
        $mdl = substr($this->object_name,strlen( $this->app->app_id.'_mdl_'));

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
        $newFileName = $this->app->app_id.'_'.$mdl.'_'.$_FILES['import_file']['name'].'-'.time();
 
        base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName,serialize($contents));
        base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName.'_sdf',serialize(array()));
        base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName.'_error',serialize(array()));

 //       base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName.'_msg',serialize(array()));

        fclose($tmpFileHandle);
 
        $oo = kernel::single('desktop_finder_builder_to_run_import');
        $msgList = $oo->turn_to_sdf($aaa,array(
                    'file_type' => substr( $_FILES['import_file']['name'],-3 ),
                    'app' => $this->app->app_id,
                    'mdl' => $mdl,
                    'file_name' => $newFileName
                ));
        $msg = array();
        if( $msgList['error'] )
            $rs = array('failure',$msgList['error']);
        else
            $rs = array('success',$msgList['warning']);
        /*
            $queueData = array(
                'queue_title'=>$mdl.app::get('desktop')->_('转sdf'),
                'start_time'=>time(),
                'params'=>array(
                    'file_type' => substr( $_FILES['import_file']['name'],-3 ),
                    'app' => $this->app->app_id,
                    'mdl' => $mdl,
                    'file_name' => $newFileName
                ),
                'worker'=>'desktop_finder_builder_to_run_import.turn_to_sdf',
            );
            $oQueue->save($queueData); 
         */


        $echoMsg = '';
        if( $rs[0] == 'success' ){
			$o = app::get( $this->app->app_id )->model($mdl);
			$oImportType->model = $o;
            $frs = $oImportType->finish_import();
            if(is_array($frs) && isset($frs[0]) && !$frs[0]) {
                $echoMsg = '导入失败：'. $frs[1]['msg'];
            } else {
                $echoMsg =app::get('desktop')->_('上传成功 已加入队列 系统会自动跑完队列');
            }
            if($msgList['warning']){
                $echoMsg .= app::get('desktop')->_('但是存在以下问题')."\\n";
                $echoMsg .= implode("\\n",$msgList['warning']);
            }
        }else{
            $echoMsg = app::get('desktop')->_('导入失败 错误如下：'."\\n".implode("\\n",$msgList['error']));
        }

        header("content-type:text/html; charset=utf-8");        
        echo "<script>alert(\"".$echoMsg."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
    }


    /**
     * predownload
     * @return mixed 返回值
     */
    public function predownload()
    {
        $this->pagedata['durl'] = urldecode($_GET['durl']);
        $this->pagedata['export_agreement'] = file_get_contents(ROOT_DIR . '/export.protocol');
        $this->display('ietask/predownload.html');
    }

    /**
     * 检查Download
     * @return mixed 返回验证结果
     */
    public function checkDownload()
    {
        $url = "index.php?app=taoexlib&ctl=ietask&act=index";
        if (!base_vcode::verify('desktop', intval($_POST['verifycode']))) {
            $this->splash('error', $url, '验证码错误');
        }
        $this->splash('success', $url, '操作成功');
    }
}