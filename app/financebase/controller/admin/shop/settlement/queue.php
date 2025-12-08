<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 队列控制层
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_ctl_admin_shop_settlement_queue extends desktop_controller
{

    // 队列列表
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {

        $use_buildin_export    = false;
        $use_buildin_import    = false;
        

        
        $actions = array(
                    array('label'=>'添加手动拉取流水任务','href'=>'index.php?app=financebase&ctl=admin_shop_settlement_queue&act=addDownloadTask&singlepage=false&finder_id='.$_GET['finder_id'],'target'=>'dialog::{width:500,height:200,title:\'添加手动拉取流水任务\'}'),
        );

        

        $params = array(
            'title'=>'实收退任务队列',
            'actions' => $actions,
            // 'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_bulidin_view'=>true,
            'use_buildin_export'=> $use_buildin_export,
            'use_buildin_import'=> $use_buildin_import,
            'orderBy'=> 'queue_id desc',
        );

        $this->finder('financebase_mdl_queue',$params);
    }

    /**
     * view 列表
     */
    public function _views(){
        $show_menu = array(
            0=>array('label'=>'全部','optional'=>'','filter'=>array('disabled'=>'false'),'href'=>'','addon'=>'_FILTER_POINT_','show'=>'true'),
            // 1=>array('label'=>'成功','optional'=>'','filter'=>array('status'=>'succ','disabled'=>'false'),'href'=>'','addon'=>'_FILTER_POINT_','show'=>'true'),
            1=>array('label'=>'失败','optional'=>'','filter'=>array('status'=>'error','disabled'=>'false'),'href'=>'','addon'=>'_FILTER_POINT_','show'=>'true'),
        );
        return $show_menu;
    }

    public function addDownloadTask()
    {
        $now_time = time();

        base_kvstore::instance('setting/financebase')->fetch('add_download_task_time',$download_task_time);

        $this->pagedata['shop_list_alipay'] = financebase_func::getShopExtends();

        if( 300 > $now_time - $download_task_time ) exit('间隔时间超过五分钟');

        $this->display('admin/queue_download_task.html');
    }

    /**
     * doDownloadTask
     * @return mixed 返回值
     */
    public function doDownloadTask()
    {
        $mdlShop = app::get('ome')->model('shop');
        $oQueue = app::get('financebase')->model('queue');
        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_queue&act=index');

        $node_type_ref = kernel::single('financebase_func')->getConfig('node_type');

        $start_time = strtotime($_POST['time_from']);
        $end_time = strtotime($_POST['time_to']);
        $shop_id = $_POST['shop_id'];

        if(!$start_time || !$end_time)
        {
            $this->end(false, "请设置任务时间");
        }

        $diff_time = $end_time - $start_time;

        if(0 > $diff_time)
        {
            $this->end(false, "任务时间设置错误");
        }

        $max_time = 30 * 86400;

        if($diff_time > $max_time)
        {
            //$this->end(false, "时间范围只允许30天内");
        }

        $shop_list = $mdlShop->getList('name',array('shop_id'=>$shop_id),0,1);
        if(!$shop_list)
        {
            $this->end(false, "没有店铺");
        }
        $shop_name = $shop_list[0]['name'];

        // $mdlShopExtends = app::get('ome')->model('shop_extends');
        // $shop_extends_info = $mdlShopExtends->getList('*',array('shop_id'=>$shop_id),0,1);
        $channel = app::get('channel')->model('channel')->db_dump(array ('channel_bn' => $shop_id,'channel_type' => 'ipay'));
        if(!$channel)
        {
            $this->end(false, "店铺没有绑定");
        }

        $node_type = $channel['node_type'];
        $node_id   = $channel['node_id'];

        for ($i=$start_time; $i <=$end_time ; $i+=86400) 
        { 
            
                $data = array();
                $data['shop_id'] = $shop_id;
                $data['shop_name'] = $shop_name;
                $data['bill_date'] = date("Y-m-d",$i);
                $data['node_type'] = $node_type;
                $data['node_id'] = $node_id;
                $data['shop_type'] = $node_type_ref[$node_type];
                $data['channel_id'] = $channel['channel_id'];

                $queueData = array();
                $queueData['queue_mode'] = 'billApiDownload';
                $queueData['create_time'] = time();
                $queueData['queue_name'] = sprintf("%s_%s_下载任务",$data['shop_name'],$data['bill_date']);
                $queueData['queue_data'] = $data;
                $queueData['queue_no']   = $data['bill_date'];
                $queueData['shop_id']    = $shop_id;

                $queue_id = $oQueue->insert($queueData);
                $queue_id and financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'billapidownload');
        }

        base_kvstore::instance('setting/financebase')->store('add_download_task_time',time());

        $this->end(true, "设置成功");

    }


    // 查看错误原因
    /**
     * showErrorMsg
     * @param mixed $queue_id ID
     * @return mixed 返回值
     */
    public function showErrorMsg($queue_id)
    {
        $oQueue = $this->app->model("queue");
        $row = $oQueue->getRow('error_msg',array('queue_id'=>intval($queue_id),'status'=>'error'));
        $this->pagedata['error_msg'] = unserialize($row['error_msg']);
        $this->singlepage('admin/queue_show_error.html');
    }

    /**
     * doTask
     * @param mixed $queue_id ID
     * @return mixed 返回值
     */
    public function doTask($queue_id)
    {
        @ini_set('memory_limit','128M');
        $oFunc = kernel::single('financebase_func');
        $oQueue = app::get('financebase')->model('queue');

        // 获取检测任务
        $task_info = $oQueue->getList('queue_id,queue_name,queue_data,queue_mode,queue_no,download_id,retry_count',array('queue_id'=>$queue_id,'status'=>'ready'),0,1);

        if($task_info)
        {
            $task_info = $task_info[0];
            $task_info['queue_data'] = unserialize($task_info['queue_data']);

            $class_name = sprintf("financebase_autotask_task_type_".$task_info['queue_mode']);

          
            if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)){
                if (method_exists($instance,'process')){
                    $rs = $instance->process($task_info,$msg);
                    $retry_count = (int)$task_info['retry_count'] + 1;//重试次数
                    if($rs)
                    {
                        $oQueue->update(array('status'=>'succ','modify_time'=>time(),'retry_count'=>$retry_count),array('queue_id'=>$task_info['queue_id']));
                    }else{
                        $oQueue->update(array('status'=>'error','modify_time'=>time(),'error_msg'=>$msg,'retry_count'=>$retry_count),array('queue_id'=>$task_info['queue_id']));
                    }
                }
            }

            echo "执行成功";

        }else{
            echo "无任务";
        }
    }

    /**
     * 下载文件
     *
     **/
    public function downloadUrl($queue_id)
    {
        $queueMdl = app::get('financebase')->model('queue');
        $queue = $queueMdl->dump($queue_id);

        if ($queue['is_file_ready'] != '0') {
            $this->splash('error',null,'下载文件'.$queueMdl->_columns()['is_file_ready']['type'][$queue['is_file_ready']]);
        }

        $affect_rows = $queueMdl -> update(['status'=>'ready'],[
            'queue_id' => $queue_id,
            'is_file_ready' => '0',
            'status' => 'error',
        ]);

        if ($affect_rows !== 1) {
            $this->splash('error',null,'状态异常不允许下载');
        }
        
        kernel::single('financebase_autotask_task_process')->process(['queue_id' => $queue_id],$msg);

        $this->splash('success', $this->url);
    }
}