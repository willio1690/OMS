<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 赠品规则记录
 *
 * @author wangbiao@shopex.cn
 * @version v0.1
 */
class crm_finder_gift_rule_logs
{
    var $addon_cols = 'rule_id,gift_id';
    
    public $_giftObj = null;
    
    public $_ruleObj = null;
    
    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        $this->_giftObj = app::get('crm')->model('gift');
        $this->_ruleObj = app::get('crm')->model('gift_rule_base');
    }
    
    var $column_edit = '操作';
    var $column_edit_width = 100;
    var $column_edit_order = 1;
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $sid = $row['sid'];
        
        $url = sprintf('index.php?app=crm&ctl=admin_gift_rule_logs&act=edit&sid=%d&finder_id=%s', $sid, $finder_id);
        
        $button = '<a href="'. $url .'" target="dialog::{width:600,height:400,title:\'预警设置\'}">预警设置</a>';
        return $button;
    }
    
    var $column_rule_name = '规则名称';
    var $column_rule_name_width = 130;
    var $column_rule_name_order = 19;
    function column_rule_name($row)
    {
        $ruleInfo = $this->_ruleObj->dump(array('id'=>$row[$this->col_prefix.'rule_id']), 'title');
        
        return $ruleInfo['title'];
    }
    
    var $column_rule_status = '规则状态';
    var $column_rule_status_width = 90;
    var $column_rule_status_order = 20;
    function column_rule_status($row)
    {
        $ruleInfo = $this->_ruleObj->dump(array('id'=>$row[$this->col_prefix.'rule_id']), 'id');
        if($ruleInfo){
            return '使用中';
        }else{
            return '已删除';
        }
    }
    
    var $column_gift_name = '赠品名称';
    var $column_gift_name_width = 130;
    var $column_gift_name_order = 23;
    function column_gift_name($row)
    {
        $productInfo = $this->_giftObj->dump(array('gift_id'=>$row[$this->col_prefix.'gift_id']), 'gift_name');
        
        return $productInfo['gift_name'];
    }
    
    var $column_gift_status = '赠品状态';
    var $column_gift_status_width = 90;
    var $column_gift_status_order = 24;
    function column_gift_status($row)
    {
        $productInfo = $this->_giftObj->dump(array('gift_id'=>$row[$this->col_prefix.'gift_id']), 'gift_id,is_del');
        if($productInfo['is_del'] == '1'){
            return '禁用';
        }else{
            return '启用';
        }
    }
}
