<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/27 16:19:20
 * @describe: 控制器
 * ============================
 */
class financebase_ctl_admin_expenses_splititem extends desktop_controller {

    /**
     * _views
     * @return mixed 返回值
     */

    public function _views() {
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('拆分'),'filter'=>array(),'optional'=>false,'addon'=>'showtab','href'=>'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view=0'),
            1 => array('label'=>app::get('base')->_('不拆仅呈现'),'filter'=>array(),'optional'=>false,'addon'=>'showtab','href'=>'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view=1'),
            2 => array('label'=>app::get('base')->_('拆分失败'),'filter'=>array(),'optional'=>false,'addon'=>'showtab','href'=>'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view=2'),
            3 => array('label'=>app::get('base')->_('红冲'),'filter'=>array(),'optional'=>false,'addon'=>'showtab','href'=>'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view=3'),
        );
        return $sub_menu;
    }

    /**
     * index
     * @return mixed 返回值
     */
    public function index() {
        $actions = array();
        $params = array(
                'title'=>'拆分结果明细',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>true,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'use_view_tab'=>true,
                'actions'=>$actions,
                'orderBy'=>'id desc'
        );
        $view = isset($_GET['view']) ? intval($_GET['view']) : 0;
        if(in_array($view,['1','2'])) {
            $modelName = 'financebase_mdl_expenses_unsplit';
            if($view == 1) {
                $params['base_filter'] = array('split_status' => '2');
            } else {
                $params['base_filter'] = array('split_status' => '4');
            }
            $params['actions'][] = array(
                'label'  => '重新拆分',
                'submit'   => 'index.php?app=financebase&ctl=admin_expenses_splititem&act=batchSplit&view='.$view,
                'target' => "dialog::{width:500,height:200,title:'重新拆分'}",
            );
        } else {
            $modelName = 'financebase_mdl_expenses_split';
            if($view == 3) {
                $params['base_filter'] = array('split_status' => ['1','2']);
            } else if($view == 0) {
                $params['actions'][] = array(
                    'label'  => '导入对账状态',
                    'href'   => 'index.php?app=financebase&ctl=admin_expenses_splititem&act=importReconciled',
                    'target' => "dialog::{width:500,height:200,title:'导入对账状态'}",
                );
            }
        }
        $shopdata = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['shopdata']= $shopdata;
        $this->pagedata['billCategory']= app::get('financebase')->model('expenses_rule')->getBillCategory();
        $this->finder($modelName, $params);
    }

    /**
     * split
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function split($id) {
        $row = app::get('financebase')->model('bill')->db_dump($id, 'bill_category');
        if($row['bill_category']) {
            $billCategory = array(array('bill_category'=>$row['bill_category']));
        } else {
            $billCategory = app::get('financebase')->model('expenses_rule')->getList('bill_category');
        }
        $this->pagedata['id']= $id;
        $this->pagedata['billCategory']= $billCategory;
        $this->display('admin/expenses/split_items.html');
    }

    /**
     * doSplit
     * @return mixed 返回值
     */
    public function doSplit() {
        $id = $_POST['id'];
        $data = array(
            'split_status'=>'0',
            'bill_category' => $_POST['bill_category']
        );
        $url = 'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act=index&view='.intval($_GET['view']);
        app::get('financebase')->model('bill')->update($data, array('id'=>$id));
        $this->splash('success',$url);
    }

    /**
     * 同步仓储库存进度条页
     * 
     * @return void
     * @author 
     */
    public function batchSplit()
    {
        if($_GET['view'] == '1') {
            $_POST['split_status'] = '2';
        } elseif($_GET['view'] == '2') {
            $_POST['split_status'] = '4';
        }
        foreach ($_POST as $k => $v) {
            if (!is_array($v) && $v !== false)
                $_POST[$k] = trim($v);
            if ($_POST[$k] === '') {
                unset($_POST[$k]);
            }
        }
        $this->pagedata['request_url'] = 'index.php?app='.$_GET['app'].'&ctl='.$_GET['ctl'].'&act=ajaxBatchSplit';

        parent::dialog_batch('financebase_mdl_expenses_unsplit', true);
    }

    /**
     * 同步仓储库存处理逻辑
     * 
     * @return void
     * @author 
     * */
    public function ajaxBatchSplit()
    {
        parse_str($_POST['primary_id'], $postdata);

        if (!$postdata) { echo 'Error: 请先选择流水';exit;}

        $retArr = array(
            'itotal'  => 0,
            'isucc'   => 0,
            'ifail'   => 0,
            'err_msg' => array(),
        );

        $mdl = app::get('financebase')->model("expenses_unsplit");
        $mdl->filter_use_like = true;

        $list = $mdl->getList('id',$postdata['f'],$postdata['f']['offset'],$postdata['f']['limit']);

        foreach ($list as $value) {

            $data = array(
                'split_status'=>'0',
            );
            $mdl->update($data, array('id'=>$value['id'],'split_status'=>['2','4']));

            $retArr['isucc']++;
        }

        echo json_encode($retArr),'ok.';exit;
    }

    /**
     * 导入对账状态页面
     */
    public function importReconciled()
    {
        $this->display('admin/expenses/import_reconciled.html');
    }

    /**
     * 处理导入对账状态
     */
    public function doImportReconciled()
    {
        $this->begin('index.php?app=financebase&ctl=admin_expenses_splititem&act=index');

        if (!$_FILES['import_file']['tmp_name']) {
            $this->end(false, '请选择要导入的文件');
        }

        $file = fopen($_FILES['import_file']['tmp_name'], 'r');
        if (!$file) {
            $this->end(false, '文件打开失败');
        }

        // 读取标题行
        $header = fgetcsv($file);
        if (!$header || !in_array('id', $header) || !in_array('是否对账', $header)) {
            fclose($file);
            $this->end(false, '文件格式不正确，必须包含"id"和"是否对账"列');
        }

        // 获取列的索引
        $idIndex = array_search('id', $header);
        $reconciledIndex = array_search('是否对账', $header);

        $mdl = app::get('financebase')->model('expenses_split');
        $success = 0;
        $error = 0;

        while (($data = fgetcsv($file)) !== false) {
            $id = $data[$idIndex];
            $isReconciled = $data[$reconciledIndex];

            // 验证数据
            if (!$id || !in_array($isReconciled, ['是', '否'])) {
                $error++;
                continue;
            }

            // 更新数据
            $result = $mdl->update(
                ['confirm_status' => $isReconciled === '是' ? '1' : '0'],
                ['id' => $id]
            );

            if ($result) {
                $success++;
            } else {
                $error++;
            }
        }

        fclose($file);

        if ($success > 0) {
            $this->end(true, sprintf('导入完成：成功 %d 条，失败 %d 条', $success, $error));
        } else {
            $this->end(false, '导入失败');
        }
    }
}