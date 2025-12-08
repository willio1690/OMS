<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_branch_flow extends desktop_controller
{
    public $name = "仓业务设置";

    public $workground = "goods_manager";

    /**
     * 货物流转类型
     * 
     * @var string
     * */
    // private const FLOW_TYPE = [
    //     'purchasein' => 'ASN入库',
    //     'b2bout'     => 'B2B出库',
    // ];

        /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $params = [
            'title'                  => '仓业务设置',
            'actions'                => [
                [
                    'label'  => '添加',
                    'href'   => $this->url . '&act=add',
                    'target' => 'dialog::{width:600,height:300,title:\'仓业务设置\'}',
                ],
            ],
            'use_buildin_new_dialog' => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
        ];

        $this->finder('ome_mdl_branch_flow', $params);
    }

    /**
     * 添加货物流转
     *
     * @return void
     * @author
     **/
    public function add()
    {
        $columns = app::get('ome')->model('branch_flow')->_columns();

        $this->pagedata['flowTypes'] = $columns['flow_type']['type'];

        $branchTypes                   = kernel::single('ome_branch')->getBranchTypes();

        $this->pagedata['branchTypes'] = $branchTypes;
        $this->display('admin/branch/flow.html');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function edit($id)
    {
        $this->pagedata['id'] = $id;

        $columns = app::get('ome')->model('branch_flow')->_columns();

        $this->pagedata['flowTypes'] = $columns['flow_type']['type'];

        $flow = app::get('ome')->model('branch_flow')->dump($id);

        $this->pagedata['flow'] = $flow;

        $this->display('admin/branch/flow.html');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function save()
    {
        $this->begin();

        $flowMdl = app::get('ome')->model('branch_flow');
        if(empty($_POST['id']) && $flowMdl->db_dump(['flow_type'=>$_POST['flow_type']])) {
            $this->end(false, '已存在该业务');
        }
        $_POST['content'] = json_encode((array)$_POST['content']);

        $rs = $flowMdl->save($_POST);

        $this->end($rs, $rs?'保存成功':'保存失败：'.$flowMdl->db->errorinfo());
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function loadFlow($id, $type)
    {
        $res = kernel::single('ome_branch_flow_router',$type)->getContent($id);

        echo $res;
    }
}
