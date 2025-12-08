<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */



/**
 * 退供差异账单
 */
class vop_ctl_admin_vreturn_diff extends desktop_controller {

    public function index() {

        $params = [
                'title'=>' 退供差异',
                // 'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                // 'use_buildin_export'=>false,
                // 'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'orderBy' => 'id desc',
                'actions'=>[
                    [
                        'label' => '获取退供差异单',
                        'href' => $this->url.'&act=pageSync',
                        'target' => 'dialog::{width:600,height:300,title:\'获取退供差异单\'}'
                    ],
                ],
        ];
        
        $this->finder('vop_mdl_vreturn_diff', $params);
    }
    /**
     * _views
     * @return mixed 返回值
     */
    public function _views()
    {
        
        $sub_menu = array(
            array('label' => '全部','filter'=>[],'optional'=>false,'addon'=>'_FILTER_POINT_'),
            array('label' => '财务已记账','filter'=>['status'=>'11'],'optional'=>false,'addon'=>'_FILTER_POINT_'),
        );
        
        $mdl = app::get('vop')->model('vreturn_diff');
        foreach ($sub_menu as $k => $v)
        {
            if (!$v['addon']) $sub_menu[$k]['addon'] = $mdl->count($v['filter']);

            $sub_menu[$k]['href'] = $this->url . '&view=' . $k;
        }

        return $sub_menu;
    }

    /**
     * pageSync
     * @return mixed 返回值
     */
    public function pageSync()
    {
        $shopList  = app::get('ome')->model('shop')->getList('shop_id, shop_bn, name, node_id, tbbusiness_type', [
            'node_type'=>'vop', 
            'node_id|noequal'=>'', 
            'tbbusiness_type'=>'jit',
        ]);

        $this->pagedata['shop_list']    = $shopList;
        
        $this->display('admin/vop/vreturn/diff/download/search_step_1.html');
    }

    /**
     * doSync
     * @return mixed 返回值
     */
    public function doSync()
    {
        $shop_id = $_POST['shop_id']; $pageNo = $_GET['pageNo'];

        if (!$shop_id) {
            $this->splash('error', '', '请选择店铺');
        }

        $filter = array_filter($_POST);
        unset($filter['_DTYPE_DATE'], $_POST['_DTIME_']);

        if (!$filter) {
            $this->splash('error', '', '请选择查询条件');
        }

        list($result,$msg) = kernel::single('vop_vreturn_diff')->getPullPageIndex($filter,$shop_id, $pageNo);

        $this->splash($result ? 'success' : 'error', '', $msg);
    }

    /**
     * downloadPagePromise
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function downloadPagePromise($shop_id = '')
    {
        $post = $_POST;  

        if (!$post['shop_id']) {
            $this->splash('error', null, "请先选择店铺!");
        }
        
        $shop = app::get('ome')->model('shop')->dump($post['shop_id']);
        if (!$shop) {
            $this->splash('error', null, "店铺不存在!");
        }

        if (!$post['begin_time']) {
            $this->splash('error', null, "请先选择开始时间!");
        }

        if (!$post['end_time']) {
            $this->splash('error', null, "请先选择结束时间!");
        }

        $post['begin_time'] = sprintf('%s %s:%s:00', $post['begin_time'], $post['_DTIME_']['H']['begin_time'], $post['_DTIME_']['M']['begin_time']);
        $post['end_time'] = sprintf('%s %s:%s:00', $post['end_time'], $post['_DTIME_']['H']['end_time'], $post['_DTIME_']['M']['end_time']);
            
        // 注释掉数组
        unset($post['_DTIME_'], $post['_DTYPE_TIME'], $post['baseApiUrl']);
        
        $post = array_filter($post);

        $inputhtml = '';
        foreach ($post as $key => $value) {
            $params = array(
                'type' => 'hidden',
                'name' => $key,
                'value' => $value,
            );

            $inputhtml .= utils::buildTag($params, 'input');
        }

        $this->pagedata['inputhtml'] = $inputhtml;
        
        $obj = kernel::single('vop_vreturn_diff');
        

        list($result, $msg, $totalCount) = $obj->getPullCount($post,$post['shop_id']);
        if (!$result){
            $this->splash('error', null, $msg);
        }

        $customLimit = $obj->getReturnPageSize();

        parent::dialog_promise($totalCount, $customLimit);
    }
}