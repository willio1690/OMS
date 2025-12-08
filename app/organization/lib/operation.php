<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class organization_operation{
    // 应用实例对象
    static private $app='organization';

    // 模型实例
    static private $model;
    
    // 外部可调用的地区数组
    public $regions;
    
    //组织结构
    public $_org_html    = '';
    
    // 构造方法
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app){
        if(!isset(self::$model)){
            self::$model = app::get(self::$app)->model('organization');
        }
    }

    /**
     * 得到组织信息 
     */
    public function getGropById($orgId='',$orgType=''){
        if ($orgId){
            $base_filter = array('parent_id' => $orgId,'del_mark' => 0);
        }else{
            $base_filter = array('org_level_num' => '1','del_mark' => 0);
        }

        if($orgType){
            $filter = array_merge($base_filter,array('org_type'=>$orgType));
            $aTemp = self::$model->getList('org_id,org_no,org_type,org_name,status,org_level_num', $filter, 0, -1, 'org_id ASC');
        }else{
            //门店管理页面重新获取$base_filter 显示启用的组织层级和相应的所属门店
            if($orgId){
                $base_filter = "parent_id=".$orgId;
            }else{
                //页面首次加载 显示最高组织层级
                $base_filter = "org_level_num=1";
            }
            $base_filter = $base_filter." and del_mark=0";
            $aTemp = self::$model->db->select("select org_id,org_no,org_type,org_name,status,org_level_num from ".DB_PREFIX."organization_organization where ".$base_filter." and (org_type in (2,3) or (org_type=1 and status=1)) order by org_id ASC");
        }
        
        if (is_array($aTemp)&&count($aTemp) > 0){
            foreach($aTemp as $key => $val){
                $aTemp[$key]['status']=intval($val['status']);
                $aTemp[$key]['child_count'] = $this->getChildCount($val['org_id'],$orgType);
                $aTemp[$key]['step'] = intval($val['org_level_num'])-1;
            }
        }
        return $aTemp;
    }
    
    /**
     * 获取指定的下级组织数量
     */
    private function getChildCount($org_id, $org_type=''){
        if($org_type){
            $filter = array('parent_id' => $org_id,'del_mark' => 0,'org_type'=>$org_type);
            $cnt = self::$model->count($filter);
        }else{
            //门店管理页面显示启用的组织层级和相应的所属门店
            $cnt = self::$model->db->count("select count(*) from ".DB_PREFIX."organization_organization where parent_id=".intval($org_id)." and del_mark=0 and (org_type in (2,3) or (org_type=1 and status=1))");
        }
        return $cnt;
    }

    /**
     * 获取用于组织树选择的组织数据
     * @param int $parent_id 父级组织ID，默认为0获取顶级组织
     * @return array 组织数据数组
     */
    public function getOrgForTreeSelect($parent_id = 0) {
        if (!$parent_id) {
            $parent_id = 0;
        }
        
        $filter = array(
            'parent_id' => $parent_id,
            'del_mark' => 0,
            'status' => 1  // 只获取启用的组织
        );
        
        $orgList = self::$model->getList('org_id,org_name,parent_id', $filter, 0, -1, 'org_id ASC');
        
        $result = [];
        if (is_array($orgList) && count($orgList) > 0) {
            foreach ($orgList as $org) {
                $childCount = $this->getChildCount($org['org_id']);
                $result[] = array(
                    'parent_id' => $org['parent_id'],
                    'org_id' => $org['org_id'],
                    'org_name' => $org['org_name'],
                    'child_count' => $childCount
                );
            }
        }
        
        return $result;
    }

    
    function getShowData($orgId)
    {
        $this->getAllChildNode($orgId);
        
        if($this->_data)
        {
            $this->formatChildNode($orgId);
        }
        
        
        
        return $this->_data;
    }
    
    /**
     * 获取所有下级组织
     * 
     * @param $orgId intval
     * @param $org_type 默认1 只取组织层级  当传入其他值时（举例2） 组织和门店都取
     * @return Array
     */
    function getAllChildNode($orgId,$org_type="1")
    {
        $data      = array();
        if ($org_type == "1"){
            //默认只取组织层级
            $filter = array('parent_id' => $orgId, 'org_type'=>1, 'del_mark' =>0);
        }else{
            //组织和门店都取
            $filter = array('parent_id' => $orgId, 'del_mark' =>0);
        }
        
        $dataList  = self::$model->getList('org_id, org_no, org_name, status, org_level_num, org_type', $filter, 0, -1, 'org_id ASC');
        
        if($dataList)
        {
            while($rows = array_shift($dataList))
            {
                $rows['items']    = $this->getAllChildNode($rows['org_id'],$org_type);
                
                $rows['child_count']    = count($rows['items']);//下级组织数量
                $rows['step']           = intval($rows['org_level_num'])-1;
                
                $data[]           = $rows;
            }
        }
        
        return $data;
    }
    
    /**
     * 格式化html代码展示所有下级组织
     * 
     * @param Array $data
     * @return string
     */
    function getAllChildNodeHtml($data)
    {
        if($data)
        {
            foreach ($data as $key => $val)
            {
                $this->_org_html    .= $this->formatHtml($val);
                
                //递归
                if($val['items'])
                {
                    $this->getAllChildNodeHtml($val['items']);
                }
                
            }
        }
        
        return $this->_org_html;
    }
    
    function formatHtml($val)
    {
        $html    = '<tr parentid="'. $val['org_id'] .'" class="provice-bg">';
        
        $html    .= '<td style="text-align:left; width:320px;"><div style="padding-left:'. ($val['step'] * 25) .'px">';
        if($val['child_count'] > 0)
        {
            $html    .= '<span class="imgTree tree_open" id="'. $val['org_id'] .'"> &nbsp;&nbsp; </span>';
        }
        else
        {
            $html    .= '<span class="imgTree tree_open"> &nbsp;&nbsp; </span>';
        }
        $html    .= '<span style="font-weight:700; color:#000; text-decoration:none;padding-right:15px;" >'. $val['org_name'] .'</span></div></td>';
        
        $html    .= '<td style="width:60px;"><span>'. ($val['status'] == 1 ? '启用' : '停用') .'</span></td>';
        
        $html    .= '<td style="width:350px">';
        if($val['status'] == 1)
        {
            $html    .= '<a href="javascript:if(confirm(\'确认停用该组织？\')){W.page(\'index.php?app=organization&ctl=admin_management&act=doUnactiveGropItem&org_id='. $val['org_id'] .'\', $extend({method: \'get\'}, JSON.decode({})), this);setTimeout(\'location.reload()\',500);}void(0);" target="">停用</a>';
            
            if($val['org_level_num'] < 5)
            {
                $html    .= '&nbsp;|&nbsp;<a class="i" href=\'index.php?app=organization&ctl=admin_management&act=addChildGropItem&org_id='. $val['org_id'] .'\' target="dialog::{title:\'<{t}>添加下级组织架构 <{/t}>\',width:800,height:300}">添加下级</a>';
            }
        }
        else
        {
            $html    .= '<a href="javascript:W.page(\'index.php?app=organization&ctl=admin_management&act=doActiveGropItem&org_id='. $val['org_id'] .'\', $extend({method: \'get\'}, JSON.decode({})), this);setTimeout(\'location.reload()\',500);void(0);" target="">启用</a>';
        }
        $html    .= '&nbsp;|&nbsp;<a class="i" href=\'index.php?app=organization&ctl=admin_management&act=editGropItem&org_id='. $val['org_id'] .'&from_page_act=org_show\' target="dialog::{title:\'<{t}>组织架构 <{/t}>\',width:800,height:300}">编辑</a>';
        $html    .= '&nbsp;|&nbsp;<a href="javascript:if(confirm(\'确认删除该组织？\')){W.page(\'index.php?app=organization&ctl=admin_management&act=doDelGropItem&org_id='. $val['org_id'] .'\', $extend({method: \'get\'}, JSON.decode({})), this);setTimeout(\'location.reload()\',500);}void(0);" target="">删除</a>';
        $html    .= '</td>';
        
        $html    .= '<td></td></tr>';
        
        return $html;
    }
    
    /*
     * 获取门店管理和门店优选规则的全部子类的html字符串
     * $data 数据
     * $type 默认store门店管理  autostore门店优选规则
     */
    function getAllChildNodeHtml_store($data,$type="store"){
        if($data){
            $render = kernel::single('base_render');
            foreach ($data as $key => &$val){
                $val["child_count"] = 0; //全部展开所以默认给0
                $dataList = array($val); //格式化传入view的数组
                $view_file = "admin/organization/sub_treeList.html";
                if ($type == "autostore"){
                    $view_file = "admin/autostore/sub_store_treeList.html";
                    $dataList = $this->formatOrgByStoreRule($dataList);
                }
                $render->pagedata['organization'] = $dataList;
                $this->_org_html .= $render->fetch($view_file,'o2o');
                //递归
                if($val['items']){
                    $this->getAllChildNodeHtml_store($val['items'],$type);
                }
            }
            unset($val);
        }
        return $this->_org_html;
    }
    
    /**
     * 根据组织类型获取门店设置的规则
     * 
     * @param Array  $dataList
     * @return Array
     */
    function formatOrgByStoreRule($dataList)
    {
        $orgNoList    = array();
    
        if(empty($dataList))
        {
            return array();
        }
    
        #查询有规则的门店
        $storeObj  = app::get("o2o")->model('store');
        foreach ($dataList as $key => $val)
        {
            if($val['org_type'] == 2)
            {
                $orgNoList[]    = $val['org_no'];
            }
        }
    
        $sql    = "SELECT a.store_bn, b.rule_id FROM sdb_o2o_store AS a LEFT JOIN sdb_o2o_autostore_rule AS b ON a.branch_id=b.branch_id ";
        $sql    .= " WHERE a.store_bn IN('". implode("','", $orgNoList) ."') AND b.rule_id !='' GROUP BY a.store_id";
        $storeList    = $storeObj->db->select($sql);
        if(empty($storeList))
        {
            return $dataList;
        }
    
        $orgNoList    = array();
        foreach ($storeList as $key => $val)
        {
            $orgNoList[$val['store_bn']]    = $val['rule_id'];
        }
    
        #格式化组织结构数据_加入已设置规则属性
        foreach ($dataList as $key => $val)
        {
            if($orgNoList[$val['org_no']])
            {
                $dataList[$key]['rule_id']    = $orgNoList[$val['org_no']];#规则ID
            }
        }
    
        return $dataList;
    }
    
}
