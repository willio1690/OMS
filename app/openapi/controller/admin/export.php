<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/6/10 14:24:14
 * @describe: 控制器
 * ============================
 */
class openapi_ctl_admin_export extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[] = array('label'=>'添加','href'=>'index.php?app=openapi&ctl=admin_export&act=addNew','target'=>'_blank');
        $actions[] = array('label'=>'删除','submit'=>'index.php?app=openapi&ctl=admin_export&act=del','confirm' => '确认删除？');
        $params = array(
                'title'=>'导出任务',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'id desc',
                'base_filter' => array('disabled' => 'false'),
        );
        
        $this->finder('openapi_mdl_export', $params);
    }

    /**
     * 添加New
     * @return mixed 返回值
     */
    public function addNew() {
        $this->singlepage('admin/export.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        $mdlExport = app::get('openapi')->model('export');
        $data = [
            'code'=>$mdlExport->gen_id(),
            'name'=>trim($_POST['name']),
            'property'=>trim($_POST['property']),
            'bill_time'=>strtotime($_POST['bill_time'].' '.$_POST['_DTIME_']['H']['bill_time'].':'.$_POST['_DTIME_']['M']['bill_time']),
            'start_time'=>strtotime($_POST['start_time'].' '.$_POST['_DTIME_']['H']['start_time'].':'.$_POST['_DTIME_']['M']['start_time']),
            'end_time'=>strtotime($_POST['end_time'].' '.$_POST['_DTIME_']['H']['end_time'].':'.$_POST['_DTIME_']['M']['end_time']),
            'status'=>'create',
            'create_time'=>time(),
            'last_modify'=>time(),
        ];
        $mdlExport->insert($data);
        $this->splash('success', $this->url);
    }

    /**
     * download
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function download($id){
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $exportObj = app::get("openapi")->model("export");
        $task_info = $exportObj->dump(array('id'=>$id),'download_url,code,name');
        $local_file = DATA_DIR.'/export/tmp_local/'.md5(microtime().KV_PREFIX.'openapi_mdl_export').$id;
        $file_type = substr($task_info['download_url'], strrpos($task_info['download_url'], '.')); 
        list($getfile_res, $msg) = $exportObj->getDownloadFile($task_info['download_url'],$local_file);
        if($getfile_res){
            $file_size = filesize($local_file);
            $file_name = $task_info['name'].$file_type;
            if($file_type == '.xls' || $file_type == '.xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header("Content-Disposition:attachment;filename=" . $file_name);
                header('Cache-Control: max-age=0');
            } else {
                header("Content-type:text/html;charset=utf-8"); 
                Header("Content-type: application/octet-stream"); 
                Header("Accept-Ranges: bytes"); 
                Header("Accept-Length:".$file_size); 
                Header("Content-Disposition: attachment; filename=".$file_name); 
            }

            echo file_get_contents($local_file);

            @unlink($local_file);
        } else {
            header("Content-type:text/html;charset=utf-8"); 
            echo $msg;
        }
    }

    /**
     * 删除
     *
     * @return void
     * @author 
     **/
    public function del()
    {
        $this->begin($this->url);

        $mdl = app::get('openapi')->model('export');

        $mdl->update(['disabled' => 'true'], $_POST);

        $this->end(true,'删除成功');
    }
}