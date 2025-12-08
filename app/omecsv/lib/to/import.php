<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omecsv_to_import extends omecsv_prototype{

    function main(){
        @set_time_limit(0);   @ini_set('memory_limit','1024M');
        $fileName = $_FILES['import_file']['name'];
        if( !$fileName ){
            echo '<script>top.MessageBox.error("上传失败");alert("未上传文件");</script>';
            exit;
        }
        
        $this->object_name = $_GET['ctler'];
        $this->app =  app::get($_GET['add']);
        $mdl = substr($this->object_name,strlen( $this->app->app_id.'_mdl_'));
        //操作日志
        $this->doLog($mdl, array_merge($_POST, $_GET));

        $model = app::get($this->app->app_id)->model($mdl);
        if (isset($_POST['filter'])) {
            $model->import_filter = $_POST['filter'];
        }
        
        $pathinfo = pathinfo($fileName);
        
        $oIo = kernel::servicelist('omecsv_io');
        foreach( $oIo as $aIo ){        
            if( $aIo->io_type_name == $pathinfo['extension']){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);

        if( !$oImportType ){
            echo '<script>top.MessageBox.error("上传失败");alert("导入格式错误");</script>';
            exit;
        }

       
        try {
            $contents = array();
            $oImportType->fgethandle($_FILES['import_file']['tmp_name'],$contents);
            $model->import_totalRows = count($contents);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            echo '<script>top.MessageBox.error("'.$msg.'");alert("'.$msg.'");</script>';exit;
        }

        $newarray = array();
        $newFileName = $this->app->app_id.'_'.$mdl.'_'.$fileName.'-'.time();
        $oo = kernel::single('omecsv_to_run_import');
        if (ob_get_level() == 0) ob_start();
        $msgList = $oo->turn_to_sdf($contents,$aaa,$newarray,array(
                    'file_type' => $pathinfo['extension'],
                    'app' => $this->app->app_id,
                    'mdl' => $mdl,
                    'file_name' => $newFileName
                ));
        ob_end_flush();
        $msg = array();


        if( $msgList['error'] )
            $rs = array('failure',$msgList['error']);
        else
            $rs = array('success',$msgList['warning']);


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
        }else{
            $echoMsg = app::get('desktop')->_('导入失败 ');
        }

        echo "<script>parent.MessageBox.success(\"上传成功\");parent.$('processBar', parent.$('import_form')).setStyle('width','100%');parent.$('processNotice', parent.$('import_form')).setHTML(\"".$echoMsg."\");if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
    }
    
    
    /**
     * 操作日志
     * @param Obj $model 数据模型
     * @param Array $params 参数
     */
    function doLog($modelName, $params) {
        $model = app::get($this->app->app_id)->model($modelName);
        $logParams = array(
            'app' => trim($params['app']),
            'ctl' => trim($params['ctl']),
            'act' => trim($params['act']),
            'modelFullName' => $params['ctler'],
            'type' => 'import',
        );
        unset($params['app']);
        unset($params['ctl']);
        unset($params['act']);
        unset($params['ctler']);
        if (isset($params['_finder'])) {
            unset($params['_finder']);
        }
        if (isset($params['finder_id'])) {
            unset($params['finder_id']);
        }
        $logParams['params'] = $params;
//        echo "<pre>";
//        print_r($logParams);exit;
        //是否记录日志
        if ($model->isDoLog()) {
            $type = $model->getLogType($logParams);
            //ome应用是否已经安装
            if (app::get('ome')->is_installed()) {
                ome_operation_log::insert($type, $logParams);
            }
        }
    }

}
