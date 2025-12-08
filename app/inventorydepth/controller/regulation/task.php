<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 规则应用类
*
* @author chenping
* @version 2012-6-7 14:22
*/
class inventorydepth_ctl_regulation_task extends desktop_controller
{
    var $workground = 'resource_center';
    public function __construct($app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }

    function _views(){
        $ruleObj = $this->app->model('task');
        $base_filter = array();
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('开启'),'filter'=>array('disabled'=>'true'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('关闭'),'filter'=>array('disabled'=>'false'),'optional'=>false),


        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $ruleObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }

    public function index()
    {

        $actions = array(
                'title' => $this->app->_('活动任务信息列表'),
                'actions' => array(
                       array(
                            'label' => $this->app->_('新建活动'),
                            'href' => $this->gen_url(array('act'=>'add')),
                            'target' => 'dialog::{width:600,height:300,title:\'新建活动\'}',
                        ),
                       array(
                            'label' => '库存规则货品导入模板',
                            'href' => 'index.php?app=inventorydepth&ctl=regulation_task&act=exportTemplate',
                            'target' => '_blank',
                        ),
                       array(
                            'label' => $this->app->_('关闭'),
                            'submit' => $this->gen_url(array('act'=>'closeTask')),
                            'confirm' => $this->app->_('确定关闭选中项？'),
                            'target' => 'refresh',
                        ),
                    ),
                'use_buildin_filter' => true,
                'use_buildin_recycle' => true,

            );
        $this->finder(
                'inventorydepth_mdl_task',
                $actions
        );
    }




    /**
     * 新建活动
     *
     * @return void
     * @author
     **/
    public function add($task_id='')
    {
        $this->title = $this->app->_('新建活动');
        $taskObj= $this->app->model('task');

        if($task_id){
            $task_detail = $taskObj->dump(array('task_id'=>$task_id),'*');

        }
        $this->pagedata['task_detail'] = $task_detail;

        $s = array_intersect(inventorydepth_shop_api_support::$item_sku_get_shops,inventorydepth_shop_api_support::$items_all_get_shops,inventorydepth_shop_api_support::$items_get_shops);

        $shop_list = app::get('ome')->model('shop')->getList('shop_id,name,node_id,shop_type');
        $support_shops = array();
        foreach ($shop_list as $key=>$shop) {
            if (in_array($shop['shop_type'],$s) && $shop['node_id']) {
                $support_shops[] = $shop;
            }
        }
        $this->pagedata['support_shops'] = $support_shops;
        unset($support_shops);
        $this->pagedata['title'] = $this->title;

        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('regulation/task.html');


    }







    /**
     * 保存规则应用
     *
     * @return void
     * @author
     **/
    public function save()
    {
        $this->begin();
        $post = $this->_request->get_post();

        $start_time = strtotime($post['start_time'].' '.$post['_DTIME_']['H']['start_time'].':'.$post['_DTIME_']['M']['start_time']);
        $end_time = strtotime($post['end_time'].' '.$post['_DTIME_']['H']['end_time'].':'.$post['_DTIME_']['M']['end_time']);
        if ($end_time<time()) {
            $msg = $this->app->_('当前时间大于结束时间!');
            return false;
        }
        if ($end_time && $start_time>$end_time) {
            $msg = $this->app->_('开始时间大于结束时间');
            return false;
        }
        $post['start_time'] = $start_time;
        $post['end_time'] = $end_time;
        $applyModel = $this->app->model('task');

        $task_detail = $applyModel->dump(array('task_name'=>trim($post['task_name'])),'*');
        if ($task_detail && empty($post['task_id'])){
            $this->end(false,'活动名称已存在!');
        }
        if ($post['shop_id'] == ''){
            $this->end(false,'请选择店铺!');
        }
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $post['operator'] = $opInfo['op_id'];
        $result = $applyModel->save($post);

        $url = $this->gen_url(array('act'=>'index'));
        $msg = $result ? $this->app->_('保存成功') : $this->app->_('保存失败');
        $this->end($result,$msg);
    }


    /**
     * 生成URL
     *
     * @return void
     * @author
     **/
    private function gen_url($params=array(),$full=false)
    {
        $params['app'] = isset($params['app']) ? $params['app'] : $this->app->app_id;
        $params['ctl'] = isset($params['ctl']) ? $params['ctl'] : 'regulation_task';
        $params['act'] = isset($params['act']) ? $params['act'] : 'index';

        return kernel::single('desktop_router')->gen_url($params,$full);
    }

    public function import_goods($task_id){
        $this->pagedata['task_id'] = $task_id;
        $this->display('regulation/import_task.html');
    }

    public function exportTemplate(){
        $oObj = $this->app->model('regulation_apply');
        $title = $oObj->exportTemplate('csv');
        $data = [];
        $data[0]        = array('sales001','普通');
        $data[1]        = array('sales002','捆绑');
        $lib = kernel::single('omecsv_phpexcel');
        $lib->newExportExcel($data, '货品导入模板'.date('Ymd'), 'xls', $title);
    }

    /**
     * 回写设置
     *
     * @return void
     * @author
     **/
    public function set_request($config = 'true',$task_id = null)
    {

        if ($task_id) {
            $taskObj = app::get('inventorydepth')->model('task');
            $task_detail = $taskObj->dump(array('task_id'=>$task_id),'shop_id');
            $shop_id = $task_detail['shop_id'];
            $skus_list = app::get('inventorydepth')->model('task_skus')->getFinderList('*', array('task_id'=>$task_id,'shop_id'=>$shop_id));

            $ids = array();
            foreach($skus_list as $skus){
                if($skus['id'])
                $ids[] = $skus['id'];
            }

            if($ids){
                $this->app->model('shop_skus')->update(array('request'=>$config), array('id'=>$ids));
                // 记录操作日志
                $optLogModel = app::get('inventorydepth')->model('operation_log');
                $optLogModel->write_log('task',$task_id,'task',($config=='true' ? '开启库存回写' : '关闭库存回写'));

            }

            $this->splash('success','javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);',$this->app->_('设置成功'));
        }
    }

    public function closeTask(){
        $this->begin('index.php?app=inventorydepth&ctl=regulation_task&act=index');
        $bool = $this->app->model('task')->update(array('disabled'=>'false'),$_POST);
        $msg = $bool ? $this->app->_('关闭成功！') : $this->app->_('关闭失败！');
        $this->end($bool,$msg);
    }



}
