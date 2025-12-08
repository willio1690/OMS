<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/7/9 16:32:55
 * @describe: 退款原因
 * ============================
 */
class ome_ctl_admin_refund_reason extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $actions[] = array(
            'label'  => '添加',
            'href' => $this->url.'&act=edit',
            'target' => "dialog::{width:350,height:200,title:'添加退款原因'}",
        );
        $params = array(
                'title'=>'退款原因',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>true,
                'actions'=>$actions,
                'orderBy'=>'id desc',
        );
        
        $this->finder('ome_mdl_refund_reason', $params);
    }

    /**
     * edit
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function edit($id='') {
        if($id) {
            $this->pagedata['data'] = app::get('ome')->model('refund_reason')->db_dump($id);
        }
        $this->display('admin/refund/reason.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        $this->begin($this->url);
        $id = $_POST['id'];
        $reason = $_POST['reason'];
        if(!$reason) {
            $this->end(false, '退款原因必填');
        }
        if(!$id) {
            $data = ['reason'=>$reason, 'create_time'=>time()];
            app::get('ome')->model('refund_reason')->insert($data);
        } else {
            app::get('ome')->model('refund_reason')->update(['reason'=>$reason], ['id'=>$id]);
        }
        $this->end(true, '操作完成');
    }
}