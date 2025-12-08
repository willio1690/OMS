<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @describe: 直赔单
 * ============================
 */
class ome_ctl_admin_compensate_record extends desktop_controller {
    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[] = array('label' => '单据拉取', 'href' => $this->url.'&act=billSync', 'target' => 'dialog::{title:\'单据拉取\',width:650,height:260}');
        $actions[] = array(
            'label'  => '导入',
            'href'   => $this->url.'&act=execlImportDailog&p[0]=compensate_record',
            'target' => 'dialog::{width:500,height:300,title:\'导入\'}',
        );
        $params = array(
                'title'=>'直赔单',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_recycle'=>false,
                'actions'=>$actions,
                'orderBy'=>'id desc',
        );
        
        $this->finder('ome_mdl_compensate_record', $params);
    }

    /**
     * billSync
     * @return mixed 返回值
     */
    public function billSync() {
        $shops = app::get('ome')->model('shop')->getList('shop_id, name, config', ['node_type'=>'360buy']);
        if(empty($shops)) {
            exit('缺少京东店铺');
        }
        foreach($shops as $key => $shop) {
            $shop['config'] = @unserialize($shop['config']);
            $shop['config'] = $shop['config'] ? $shop['config'] : array();
            if($shop['config']['compensate'] != 'sync') {
                unset($shops[$key]);
            }
        }
        if(empty($shops)) {
            exit('需要开启直赔单设置');
        }
        
        $this->pagedata['shops'] = $shops;
        $this->pagedata['start_time'] = strtotime('-7 days');
        $this->pagedata['end_time'] = time();

        $this->pagedata['request_url'] = $this->url . '&act=doBillSync';
        $this->display('admin/compensate/record/bill_sync.html');
    }

    /**
     * 同步单据
     * 
     * @return void
     * @author 
     */
    public function doBillSync()
    {
        $start_time   = $_POST['start_time'].' '.$_POST['_DTIME_']['H']['start_time'].':'.$_POST['_DTIME_']['M']['start_time'].':00';
        $end_time     = $_POST['end_time'].' '.$_POST['_DTIME_']['H']['end_time'].':'.$_POST['_DTIME_']['M']['end_time'].':00';
        $pageNo = (int) $_POST['page_no'];
        $shop_id = $_POST['shop_id'];

        if (strtotime($start_time) >= strtotime($end_time)) {
            echo json_encode(['total'=>0]);exit;
        }
        $ret = ['total'=>0,'succ'=>0,'fail'=>0];
        $sdf = ['start_modified' => date('Y-m-d H:i:s', strtotime($start_time)), 'end_modified' => date('Y-m-d H:i:s', strtotime($end_time)), 'page_no'=>$pageNo, 'page_size'=>'50'];
        $result = kernel::single('ome_event_trigger_shop_compensate')->syncRecord($shop_id, $sdf);
        if(is_array($result['data'])) {
            $ret['total'] += count($result['data']);
        }
        $result = kernel::single('ome_event_trigger_shop_compensate')->syncIndemnity($shop_id, $sdf);
        if(is_array($result['data'])) {
            $ret['total'] += count($result['data']);
        }
        $ret['succ'] = $ret['total'];
        echo json_encode($ret);exit;
    }
}