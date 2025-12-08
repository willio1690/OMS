<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 补货建议单finder类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class console_finder_replenish_suggest
{
    public $addon_cols = 'sug_status,branch_id';
    var $column_edit = '操作';
    var $column_edit_width = 180;
    var $column_edit_order = 1;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */

    public function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $sug_id = ($row['sug_id'] ? $row['sug_id'] : $row[$this->col_prefix.'sug_id']);
        $branch_id = $row[$this->col_prefix.'branch_id'];
        $sug_status = $row[$this->col_prefix.'sug_status'];
        //url
        $url = '<a href="index.php?app=console&ctl=admin_replenish_suggest&act=exportTemplate&p[0]='. $sug_id .'&finder_id='.$finder_id.' " target="_blank">下载</a>';
        
        $confirmbutton= <<<EOF
        &nbsp;&nbsp;<a class="lnk" href="index.php?app=console&ctl=admin_replenish_task&act=dispose&p[0]={$sug_id}" target="dialog::{width:550,height:250,title:'单据确认'}" >审核</a> &nbsp;&nbsp;
EOF;
        $branchObj     = kernel::single('o2o_store_branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids    = $branchObj->getO2OBranchIds();
        if(in_array($sug_status,array('0'))){
            if($is_super || (!$is_super && in_array($branch_id,$branch_ids)) ){
                $url.= " ".$confirmbutton;
            }
        }
        
        return $url;
    }
    
    var $detail_basic = '补货任务详情';
    /**
     * detail_basic
     * @param mixed $sug_id ID
     * @return mixed 返回值
     */
    public function detail_basic($sug_id)
    {
        $render = app::get('console')->render();
        
        $suggestMdl = app::get('console')->model('replenish_suggest');
        
        //补货任务详情
        $suggests = $suggestMdl->dump(array('sug_id'=>$sug_id), '*');
        $out_branch_id = $suggests['out_branch_id'];
        if($out_branch_id){
            //调出仓库
            $sql = "SELECT branch_id,branch_bn,name FROM sdb_ome_branch WHERE branch_id=".$out_branch_id;
            $branchInfo = $suggestMdl->db->selectrow($sql);
            $suggests['branch_name'] = $branchInfo['name'];
        }
        
        $render->pagedata['suggests'] = $suggests;
        
        return $render->fetch('admin/replenish/suggest_detail.html');
    }

  
    var $detail_log = '日志详情';
    /**
     * detail_log
     * @param mixed $sug_id ID
     * @return mixed 返回值
     */
    public function detail_log($sug_id)
    {
       
        $render = app::get('console')->render();
        $opObj  = app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>$sug_id,'obj_type'=>'replenish_suggest@console'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['logs'] = $logdata;
        return $render->fetch('admin/replenish/suggest_log.html');
    }
}
?>