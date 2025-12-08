<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_ctl_admin_bbu extends desktop_controller
{

    /**
     * index
     * @return mixed 返回值
     */
    public function index()
    {
        $actions = array(
            array(
                'label'  => '添加公司业务组织',
                'href'   => 'index.php?app=dealer&ctl=admin_bbu&act=create',
                'target' => "dialog::{width:500,height:200,title:'添加公司业务组织'}",
            ),
        );
        $params = array(
            'title'               => '公司业务组织',
            'use_buildin_set_tag' => false,
            'use_buildin_filter'  => false,
            'use_buildin_export'  => false,
            'use_buildin_import'  => false,
            'use_buildin_recycle' => false,
            'actions'             => $actions,
            // 'orderBy'             => 'status asc, up_time desc',
        );

        $this->finder('dealer_mdl_bbu', $params);
    }

    /**
     * 创建
     * @return mixed 返回值
     */
    public function create()
    {
        $this->display('admin/bbu.html');
    }

    /**
     * edit
     * @param mixed $bbuId ID
     * @return mixed 返回值
     */
    public function edit($bbuId)
    {
        $row = app::get('dealer')->model('bbu')->db_dump(array('bbu_id' => $bbuId));

        $this->pagedata['bbu_id']   = $row['bbu_id'];
        $this->pagedata['bbu_name'] = $row['bbu_name'];
        $this->pagedata['bbu_code'] = $row['bbu_code'];
        $this->display('admin/bbu.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save()
    {
        $bbuId   = $_POST['bbu_id'];
        $bbuCode = trim($_POST['bbu_code']);
        $bbuName = trim($_POST['bbu_name']);

        $this->begin('index.php?app=dealer&ctl=admin_bbu&act=index');
        if (!$bbuName) {
            $this->end(false, '销售团队名称必填');
        }
        if (!$bbuCode) {
            $this->end(false, '销售团队编码必填');
        }

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $bbuMdl = app::get('dealer')->model('bbu');
        $data   = [
            'bbu_name' => $bbuName,
            'bbu_code' => $bbuCode,
            'status'   => 'active',
            'op_name'  => $opInfo['op_name'],
            // 'cos_id'   => '',
        ];
        $filter = [
            'bbu_code' => $bbuCode,
            // 'bbu_id|noequal' => $bbuId,
        ];
        $bbuList = $bbuMdl->getList('*', $filter);
        if (count($bbuList) > 1 || $bbuList[0]['bbu_id'] != $bbuId) {
            $this->end(false, '销售团队编码已被使用');
        }

        $snapshoot = [];
        if ($bbuId) {
            unset($data['bbu_code']);
            $bbuMdl->update($data, ['bbu_id' => $bbuId]);
            $data['bbu_id']   = $bbuId;
            $data['bbu_code'] = $bbuList[0]['bbu_code'];
            $logInfo          = '更新销售团队。';
            $operation        = 'dealer_bbu_edit@dealer';
            $snapshoot        = $bbuList[0];
        } else {
            $bbuMdl->insert($data);
            $logInfo   = '添加销售团队。';
            $operation = 'dealer_bbu_add@dealer';
        }
        // 创建/更新 企业组织表,得到返回再去更新销售团队表
        $cosData = [
            'cos_type'  => 'bbu',
            'cos_code'  => $data['bbu_code'],
            'cos_name'  => $data['bbu_name'],
            'op_name'   => $data['op_name'],
            'parent_id' => 1,
            'is_leaf'   => '1',
        ];
        $cosId = kernel::single('organization_cos')->saveCos($cosData);
        $cosId && $bbuMdl->update(['cos_id' => $cosId], ['bbu_id' => $data['bbu_id']]);

        $omeLogMdl = app::get('ome')->model('operation_log');
        $log_id    = $omeLogMdl->write_log($operation, $data['bbu_id'], $logInfo);
        if ($log_id && $snapshoot) {
            $shootMdl  = app::get('ome')->model('operation_log_snapshoot');
            $snapshoot = json_encode($snapshoot, JSON_UNESCAPED_UNICODE);
            $tmp       = ['log_id' => $log_id, 'snapshoot' => $snapshoot];
            $shootMdl->insert($tmp);
        }
        $this->end(true, '操作成功');
    }

    /**
     * 查看快照
     */
    public function show_history($log_id)
    {
        $logSnapshootMdl    = app::get('ome')->model('operation_log_snapshoot');
        //日志
        $log = $logSnapshootMdl->db_dump(['log_id'=>$log_id]);
        $row = json_decode($log['snapshoot'],1);

        $this->pagedata['bbu_id']   = $row['bbu_id'];
        $this->pagedata['bbu_name'] = $row['bbu_name'];
        $this->pagedata['bbu_code'] = $row['bbu_code'];
        $this->pagedata['history']  = true;
        $this->singlepage('admin/bbu.html');
    }

}
