<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会仓库管理
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 */
class console_ctl_admin_warehouse extends desktop_controller{
    
    var $workground = "console_purchasecenter";
    
    function index()
    {
        $this->title = '仓库列表';
        $filter      = array();
        
        $params = array(
            'title'               => $this->title,
            'use_buildin_set_tag' => false,
            'use_buildin_filter'  => true,
            'use_buildin_export'  => false,
            'use_buildin_import'  => false,
            'use_buildin_recycle' => false,
            'actions'             => array(
                array(
                    'label'  => '添加仓库',
                    'href'   => 'index.php?app=console&ctl=admin_warehouse&act=add&singlepage=false&finder_id=' . $_GET['finder_id'],
                    'target' => '_blank',
                ),
                array(
                    'label' => '获取唯品会仓',
                    'href'  => 'index.php?app=console&ctl=admin_warehouse&act=getWarehouses&finder_id=' . $_GET['finder_id'],
                ),
            ),
            'base_filter'         => $filter,
        );
        
        $this->finder('console_mdl_warehouse', $params);
    }
    
    //新增弹窗页
    function add()
    {
        $this->_edit('add');
    }
    
    //编辑弹窗页
    function edit($id){
        $this->_edit('edit', $id);
    }
    
    //新增和编辑弹窗页的展示
    function _edit($action, $id=0)
    {
        $warehouseObj    = app::get('purchase')->model('warehouse');
        $data            = array();
        
        if($id)
        {
            $data    = $warehouseObj->dump(array('branch_id'=>$id), '*');
        }
        
        $this->pagedata['data']    = $data;
        $this->singlepage('admin/vop/warehouse_edit.html');
    }
    
    //保存
    function save()
    {
        $this->begin('index.php?app=console&ctl=admin_warehouse&act=index');
        
        $warehouseObj    = app::get('purchase')->model('warehouse');
        
        $data = $_POST;
        
        if(empty($data['branch_bn']) || empty($data['branch_name']))
        {
            $this->end(false, '请填写仓库名称和仓库编号，请检查!');
        }
        
        if(empty($data['uname']) || empty($data['email']) || empty($data['zip']))
        {
            $this->end(false, '联系人、Email、邮编必须填写，请检查!');
        }
        
        if(empty($data['mobile']) && empty($data['phone']))
        {
            $this->end(false, '手机号与电话至少填写一项，请检查!');
        }
        
        if(empty($data['area']) || empty($data['address']))
        {
            $this->end(false, '地区、地址必须填写，请检查!');
        }
        
        if($data['branch_id'])
        {
            $data['branch_id']    = intval($data['branch_id']);
        }
        
        $row    = $warehouseObj->dump(array('branch_bn'=>$data['branch_bn']), '*');
        
        if($row)
        {
            if($data['branch_id'] && ($row['branch_id'] != $data['branch_id']))
            {
                $this->end(false, '仓库编号不能重复，请检查!');
            }
            elseif(empty($data['branch_id']))
            {
                $this->end(false, '仓库编号不能重复，请检查!');
            }
        }
        
        $result    = $warehouseObj->save($data);
        if(!$result)
        {
            $this->end(false, '保存失败');
        }
        
        $this->end(true, '保存成功');
    }

    /**
     * 获取Warehouses
     * @return mixed 返回结果
     */

    public function getWarehouses()
    {
        $this->begin('index.php?app=console&ctl=admin_warehouse&act=index');

        //唯品会店铺
        $purchaseLib  = kernel::single('purchase_purchase_order');
        $shopList     = $purchaseLib->get_vop_shop_list();

        if ($shopList) {
            $warehouseLib = kernel::single('purchase_warehouse');
            foreach ($shopList as $key => $val) {
                $warehouseLib->getVopWarehouse($val['shop_id']);
            }
        }

        $this->end(true, app::get('base')->_('下载成功'));
    }
}
