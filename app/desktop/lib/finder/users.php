<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


/* TODO: Add code here */
class desktop_finder_users{
    var $column_control = '操作';
    function __construct($app){
        $this->app=$app;
    }
    var $addon_cols = 'is_lock';
    function column_control($row){
        $list['edit'] = '<a href="index.php?app=desktop&ctl=users&act=edit&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['user_id'].'" target="dialog::{title:\''.app::get('desktop')->_('编辑操作员').'\', width:750, height:650}">'.app::get('desktop')->_('编辑').'</a>';
        if($row[$this->col_prefix.'is_lock'] == '1') {
            $list['unLock'] = '<a href="index.php?app=desktop&ctl=users&act=unLock&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['user_id'].'">'.app::get('desktop')->_('解锁').'</a>';
        }
        $is_operator_edit = kernel::single('desktop_user')->has_permission('operator_edit');
        if(!$is_operator_edit){
            unset($list['edit']);
        } else {
            if($row['user_id'] != 1) {
                $list['clone'] = '<a href="index.php?app=desktop&ctl=users&act=edit&clone=true&_finder[finder_id]='.$_GET['_finder']['finder_id'].'&p[0]='.$row['user_id'].'" target="dialog::{title:\''.app::get('desktop')->_('复制操作员').'\', width:750, height:650}">'.app::get('desktop')->_('复制').'</a>';
            }
        }
        return implode(' | ',$list);
    }
    
    public $column_roles = '角色';
    public $column_roles_width = 260;
    public $column_roles_order = 300;
    
    private $_roles = null;
    /**
     * column_roles
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_roles($row, $list)
    {
        $html_s =  '<div style="overflow: auto;word-break: break-word;white-space: normal;width: 100%;flex-wrap: wrap;display: flex;">';
        $html_e = '</div>';
        if (isset($this->_roles)){
           $text = implode('、', (array)$this->_roles[$row['user_id']]);
           
           return $html_s.$text.$html_e;
        }
        
        $filter = ['user_id' => array_column($list, 'user_id')];
        $hasRoles = app::get('desktop')->model('hasrole')->getList('*', $filter);
        
        if (!$hasRoles) {
            
            $this->_roles = [];
            return '';
        }
        
        $roles = app::get('desktop')->model('roles')->getList('role_id,role_name', ['role_id' => array_column($hasRoles, 'role_id')]);
        $roles = array_column($roles, 'role_name', 'role_id');
        
        foreach ($hasRoles as $hasRole) {
            $this->_roles[$hasRole['user_id']][] = $roles[$hasRole['role_id']];
        }
        
        $text = implode('、', (array)$this->_roles[$row['user_id']]);
        
        return $html_s.$text.$html_e;
    }
}