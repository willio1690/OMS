<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/7/29 14:08:52
 * @describe: 库存调整单
 * ============================
 */
class console_ctl_admin_o2oadjust extends desktop_controller
{
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
        $actions   = [];
        $actions[] = [
            'label'  => '新增',
            'href'   => $this->url . '&act=add',
            'target' => '_blank',
        ];
        $actions[] = [
            'label'  => '导入模板',
            'href'   => $this->url . '&act=exportTemplate',
            'target' => '_blank',
        ];
        $base_filter                   = [];
        $base_filter['adjust_channel'] = 'storeadjust';
        $is_super = kernel::single('desktop_user')->is_super();
        
        if (!$is_super){
            // 普通管理员：默认无权限
            $base_filter['branch_id'] = array('false');
            
            // 获取有权限的门店仓库（b_type=2 表示门店）
            $mdlOmeBranch = app::get('ome')->model('branch');
            $branchList = $mdlOmeBranch->getList('branch_id', array(
                'b_type' => '2',
                'is_ctrl_store' => '1'
            ), 0, -1);
            
            if (!empty($branchList)) {
                $base_filter['branch_id'] = array_column($branchList, 'branch_id');
            }
        }
        $params = [
            'title'                 => '门店调整单',
            'use_buildin_set_tag'   => false,
            'use_buildin_filter'    => true,
            'use_buildin_export'    => true,
            'use_buildin_import'    => true,
            'use_buildin_importxls' => true,
            'use_buildin_recycle'   => false,
            'actions'               => $actions,
            'base_filter'           => $base_filter,
            'orderBy'               => 'id desc',
        ];

        $this->finder('console_mdl_adjust', $params);
    }

    /**
     * exportTemplate
     * @return mixed 返回值
     */
    public function exportTemplate()
    {
        $row  = app::get('console')->model('adjust')->getTemplateColumn();
        $lib  = kernel::single('omecsv_phpexcel');
        $data = [
            ['增量/全量', '门店编码','库存初始化', '', '是/否', '审核/完成', 'code001', '测试商品001', '1'],
            ['', '', '', '', '','', 'code002', '测试商品002', '-1'],
        ];
        $lib->newExportExcel($data, '门店调整单模板-' . date('Y-m-d'), 'xls', $row);
    }

    /**
     * 添加
     * @return mixed 返回值
     */
    public function add() {
        // 获取用户有权限的门店仓库

        $mdlOmeBranch = app::get('ome')->model('branch');
        $branchList = $mdlOmeBranch->getList('branch_id,name,branch_bn', array(
            'b_type' => '2',
            'is_ctrl_store' => '1'
        ), 0, -1);
        
        if (empty($branchList)) {
            $this->splash('error', null, '您没有门店调整权限');
        }

        $branchOptions = [];
        foreach ($branchList as $branch) {
            $branchOptions[$branch['branch_id']] = '[' . $branch['branch_bn'] . ']' . $branch['name'];
        }

        $this->pagedata['branchOptions'] = $branchOptions;
        // 标识这是门店调整单，用于模板中显示不同的标签文本
        $this->pagedata['isStoreAdjust'] = true;
       
        $this->singlepage('admin/adjust/add.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        // 权限验证：检查用户是否有权限操作选择的门店仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            // 获取有权限的门店仓库
            $mdlOmeBranch = app::get('ome')->model('branch');
            $branchList = $mdlOmeBranch->getList('branch_id', array(
                'b_type' => '2',
                'is_ctrl_store' => '1'
            ), 0, -1);
            $user_branch_ids = array_column($branchList, 'branch_id');
            
            if (empty($user_branch_ids) || !in_array($_POST['branch_id'], $user_branch_ids)) {
                $this->splash('error', null, '您没有权限操作该门店仓库');
            }
        }
        
        $sn = [];
        if($_POST['sn']) {
            foreach($_POST['sn'] as $k => $val) {
                if($val) {
                    $sn[$k] = explode(',', $val);
                }
            }
        }
        $data = [
            'adjust_mode' => $_POST['adjust_mode'],
            'is_check' => '0',//调整自动完成
            'iso_status'=>'confirm',//调整自动完成
            'branch_id' => $_POST['branch_id'],
            'negative_branch_id' => [$_POST['branch_id']],
            'memo' => $_POST['memo'],
            'source'=>'门店新增',
            'adjust_channel' => 'storeadjust', // 注意：这个值必须完全匹配，否则会被设置为branchadjust
            'items' => $_POST['number'],
            'sn' => $sn,
        ];
       
        list($rs, $rsData) = kernel::single('console_adjust')->dealSave($data);
        $this->splash(($rs?'success':'error'), null, $rsData['msg']);
    }

}
