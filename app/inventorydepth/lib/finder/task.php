<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_finder_task{

    function __construct($app)
    {
        $this->app = $app;

        $this->_render = app::get('inventorydepth')->render();
    }

    public $column_operator = '操作';
    public $column_operator_order = 1;
    public $column_operator_width = 300;
    public function column_operator($row)
    {
        
        $finder_id = $_GET['_finder']['finder_id'];

       
           
       
        $button = <<<EOF
        <a target='dialog::{width:500,height:300}' href='index.php?app=omecsv&ctl=admin_import&act=main&ctler=inventorydepth_mdl_task&add=inventorydepth&task_id={$row['task_id']}&_finder[finder_id]={$finder_id}'>导入货品</a>
EOF;

        return $button;
    } 


    public $column_skus_count = '货品总数';
    public $column_skus_count_order = 40;
    public function column_skus_count($row)
    {
        
        $count = app::get('inventorydepth')->model('task_skus')->count(array('task_id'=>$row['task_id']));

        return <<<EOF
        <a href='index.php?app=inventorydepth&ctl=regulation_skus&act=index&filter[task_id]={$row['task_id']}'>{$count}</a>
EOF;
    }

    public $column_request = '库存回写设置';
    public $column_request_order = 2;
    public $column_request_width = 200;
    public function column_request($row)
    {
        $openhref = 'index.php?app=inventorydepth&ctl=regulation_task&act=set_request&p[0]=true&p[1]='.$row['task_id'].'&finder_id='.$_GET['_finder']['finder_id'];
        
        $closehref = 'index.php?app=inventorydepth&ctl=regulation_task&act=set_request&p[0]=false&p[1]='.$row['task_id'].'&finder_id='.$_GET['_finder']['finder_id'];

        $button = <<<EOF
        <a style="background-color:green;float:left;text-decoration:none;" href="{$openhref}"><span title="开启" style="color:#eeeeee;padding:2px;">&nbsp;开启&nbsp;</span></a>
EOF;
$button1 = <<<EOF
        <a style="background-color:#a7a7a7;float:left;text-decoration:none;" href="{$closehref}"><span title="关闭" style="color:#eeeeee;padding:2px;">&nbsp;关闭&nbsp;</span></a>
EOF;
         return $button.$button1;
    }

    /* public $detail_operation_log = '操作日志';
    public function detail_operation_log($task_id)
    {
        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $filter = array('obj_type' => 'task','obj_id' => $task_id);
        $optLogList = $optLogModel->getList('*',$filter,0,200);
        foreach ($optLogList as &$log) {
            $log['operation'] = $optLogModel->get_operation_name($log['operation']);
        }
        
        $this->_render->pagedata['optLogList'] = $optLogList;
        return $this->_render->fetch('finder/shop/operation_log.html');
    } */

}
