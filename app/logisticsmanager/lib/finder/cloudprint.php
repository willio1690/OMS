<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_finder_cloudprint
{
    public $addon_cols = "id,node_id";
    public $column_control = '操作';
    public $column_control_width = '100';
    public $column_control_order = COLUMN_IN_HEAD;
    
    /**
     * column_control
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_control($row)
    {
        $id = $row[$this->col_prefix . 'id'];
        $finder_id    = $_GET['_finder']['finder_id'];
        $node_id = $row[$this->col_prefix . 'node_id'];
        $button='';
        $button =$node_id ? '' :"<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=logisticsmanager&ctl=admin_cloudprint&act=add&p[0]={$id}&finder_id={$_GET['_finder']['finder_id']}',{width:800,height:600,title:'打印机编辑'}); \">编辑</a>";
        
        $button.= $node_id ? sprintf("  <a class='c-red' href='javascirpt:void(0);' url='index.php?app=logisticsmanager&ctl=admin_cloudprint&act=bindNodeId&&p[0]=%s&p[1]=%s&finder_id=%s' onclick='javascript:if(confirm(\"确认取消绑定？\")){W.page(this.get(\"url\"));}'>取消绑定</a>", $id,'unbind',$finder_id) : sprintf("  <a href='javascirpt:void(0);' url='index.php?app=logisticsmanager&ctl=admin_cloudprint&act=bindNodeId&&p[0]=%s&p[1]=%s&finder_id=%s' onclick='javascript:if(confirm(\"确认绑定？\")){W.page(this.get(\"url\"));}'>绑定</a>", $id,'bind',$finder_id);


        return $button;
    }
    
    
   
    
}
