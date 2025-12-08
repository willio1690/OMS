<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_store_inventory extends desktop_controller{
    var $name = "门店库存列表";
    var $workground = "o2o_center";
    
    function index()
    {
        $params = [];
        
        //列表新增门店搜索
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            
            $panel->setId('o2o_branch_finder_top');
            $panel->setTmpl('admin/finder/finder_store_panel_filter.html');
            
            $panel->show('o2o_mdl_branch_products', $params);
        }
        
        // 门店权限控制
        $base_filter = array('b_type' => '2');
        
        //商品可视状态
        if (!isset($_POST['visibility'])) {
            $base_filter['visibility'] = 'true';
        }elseif(empty($_POST['visibility'])){
            unset($_POST['visibility']);
        }
        
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            // 普通管理员：先设置默认无权限
            $base_filter['branch_id'] = array('false');
            
            // 获取有权限的门店（只获取门店类型且管控库存的）
            $mdlOmeBranch = app::get('ome')->model('branch');
            $branchList = $mdlOmeBranch->getList('branch_id', array(
                'b_type' => '2',
                'is_ctrl_store' => '1'
            ), 0, -1);
            
            if (!empty($branchList)) {
                $user_branch_ids = array_column($branchList, 'branch_id');
                
                // 如果用户选择了门店，验证权限
                if (!empty($_POST['branch_id'])) {
                    $base_filter['branch_id'] = in_array($_POST['branch_id'], $user_branch_ids) ? $_POST['branch_id'] : array('false');
                } else {
                    // 没选择门店，显示所有有权限的门店
                    $base_filter['branch_id'] = $user_branch_ids;
                }
            }
        } else {
            // 超管：如果选择了门店就显示选择的，否则显示所有
            if (!empty($_POST['branch_id'])) {
                $base_filter['branch_id'] = $_POST['branch_id'];
            }
        }
        
        $actions = array(
            array(
                'label'=>app::get('ome')->_('全部导出'),
                'class'=>'export',
                'icon'=>'add.gif',
                'href'=>'index.php?app=o2o&ctl=admin_store_inventory&act=export',
                'target'=>'dialog::{width:400,height:170,title:\'导出\'}'
            ),
        );
        
        $this->finder('o2o_mdl_branch_products',array(
            'title'=>'门店库存列表',
            'base_filter' => $base_filter,
            'actions' => $actions,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_filter' => true,
            'object_method'=>array('count'=>'countlist','getlist'=>'getlists')
        ));
    }
    
    /**
     * export
     * @return mixed 返回值
     */
    public function export()
    {
        $branch_id = $_GET['branch_id'];
        $bn = $_GET['bn'];
        $_actual_store_search = $_GET['_actual_store_search'];
        $actual_store = $_GET['actual_store'];
        $_enum_store_search = $_GET['_enum_store_search'];
        $enum_store = $_GET['enum_store'];
        $this->pagedata['branch_id'] = $branch_id;
        $this->pagedata['bn'] = $bn;
        $this->pagedata['_actual_store_search'] = $_actual_store_search;
        $this->pagedata['actual_store'] = $actual_store;
        $this->pagedata['_enum_store_search'] = $_enum_store_search;
        $this->pagedata['enum_store'] = $enum_store;

        $this->page('admin/store/inventory/export.html');
    }
}
?>