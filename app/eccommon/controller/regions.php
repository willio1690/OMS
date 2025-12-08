<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_ctl_regions extends desktop_controller{

    var $workground = 'eccommon_center';

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
	{
		parent::__construct($app);
		header("cache-control: no-store, no-cache, must-revalidate");
	}

    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
        $obj_regions_op = kernel::single('eccommon_regions_operation');
        $this->path[]=array('text'=>'配送地区列表');

        if ($obj_regions_op->getTreeSize())
		{
			//超过100条
            $this->pagedata['area'] = $obj_regions_op->getRegionById();
            $this->page('regions/area_treeList.html');
        }
		else
		{
            $obj_regions_op->getMap();
            $this->pagedata['area'] = $obj_regions_op->regions;
            $this->page('regions/area_map.html');
        }
    }

    /**
     * showRegionTreeList
     * @param mixed $serid ID
     * @param mixed $multi multi
     * @return mixed 返回值
     */
    public function showRegionTreeList($serid,$multi=false)
	{
         if ($serid)
		 {
			$this->pagedata['sid'] = $serid;
         }
		 else
		 {
			$this->pagedata['sid'] = substr(time(),6,4);
         }

         $this->pagedata['multi'] =  $multi;
         $this->singlepage('regions/regionSelect.html');
    }

    /**
     * 获取ChildNode
     * @return mixed 返回结果
     */
    public function getChildNode()
	{
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        $this->pagedata['area'] = $obj_regions_op->getRegionById($_POST['regionId']);
        $this->display('regions/area_sub_treeList.html');
    }

    /**
     * 获取RegionById
     * @param mixed $pregionid ID
     * @return mixed 返回结果
     */
    public function getRegionById($pregionid)
	{
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        echo json_encode($obj_regions_op->getRegionById($pregionid));
    }

    /**
     * 添加新地区界面
     * @params string 父级region id
     * @return null
     */
    public function showNewArea($pRegionId=null)
	{
        if ($pRegionId){
            $dArea = app::get('eccommon')->model('regions');
            $this->pagedata['parent'] = $dArea->getRegionByParentId($pRegionId);
        }
        $this->path[] = array('text'=>'添加配送地区');
        $this->display('regions/area_new.html');
    }

    /**
     * 添加新地区
     * @params null
     * @return null
     */
    public function addDlArea()
	{
		$this->begin('index.php?app=eccommon&ctl=regions&act=index');
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        if(!$obj_regions_op->insertDlArea($_POST,$msg)){
            $this->end(false, '新建失败，'.$msg);
        }else
            $this->end(true, '新建成功，地区名称：'.$_POST['local_name']);

    }

    /**
     * 修改地区信息的入口
     * @params null
     * @return null
     */
    public function saveDlArea()
	{
		$this->begin('index.php?app=eccommon&ctl=regions&act=index');
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        if(!$obj_regions_op->updateDlArea($_POST,$msg)){
			$this->end(false, '修改失败，'.$msg);
        }
		else
		{
			$this->end(true, '修改成功，地区名称：'.$_POST['local_name']);
		}
    }

    /**
     * 编辑显示页面
     * @params string 地区的regions id
     * @return null
     */
    public function detailDlArea($aRegionId)
	{
        $this->path[] = array('text'=>'配送地区编辑');
        $oObj = app::get('eccommon')->model('regions');
        $this->pagedata['area'] = $oObj->getDlAreaById($aRegionId);
        $this->display('regions/area_edit.html');
    }

    /**
     * 删除对应regions id 的地区
     * @params string region id
     * @return null
     */
    public function toRemoveArea($regionId)
	{
        $this->begin('index.php?app=eccommon&ctl=regions&act=index');
        $regions = app::get('eccommon')->model('regions');

        //判断地区ID是否已使用
        $region_detail = $regions->getDlAreaById($regionId);
       
        //
        $area = '';
        if ($region_detail['region_grade'] == '3'){
            $area = '%'.$region_detail['parent_name'].'/'.$region_detail['local_name'].':'.$region_detail['region_id'].'';
        }else if($region_detail['region_grade'] == '2'){
            $area = 'mainland:'.$region_detail['parent_name'].'/'.$region_detail['local_name'].'/%';
        }else if($region_detail['region_grade'] == '1'){
            $area = 'mainland:'.$region_detail['local_name'].'/%';
        }
        $orders = $regions->db->selectrow("SELECT order_id FROM sdb_ome_orders WHERE ship_area LIKE '".$area."'");
        if ($orders){
            $this->end(false,'该地区已有订单使用,不可以删除!');
        }
		$obj_regions_op = kernel::single('eccommon_regions_operation');
		if ($obj_regions_op->toRemoveArea($regionId))
			$this->end(true,'删除地区成功！');
		else
			$this->end(false,'删除地区失败！');
    }

    /**
     * 更新地区排序数据
     * @params null
     * @return null
     */
    public function updateOrderNum()
	{
        $this->begin('index.php?app=eccommon&ctl=regions&act=index');

        $is_update = true;
        $dArea = app::get('eccommon')->model('regions');
        $arrPOdr = $_POST['p_order'];

        $arrRegions = array();
        if ($arrPOdr)
        {
            foreach ($arrPOdr as $key=>$strPOdr)
            {
                $arrdArea = $dArea->dump($key, 'region_id,ordernum');
                $arrdArea['ordernum'] = $strPOdr ? $strPOdr : '50';
                $arrRegions[] = $arrdArea;
            }
        }

        if ($arrRegions)
        {
            foreach ($arrRegions as $arrRegionsinfo)
            {
                $is_update = $dArea->save($arrRegionsinfo);
            }
        }

        $this->end($is_update,'排序成功！');
    }

    /**
     * 获取ChlidById
     * @param mixed $pId ID
     * @return mixed 返回结果
     */
    public function getChlidById($pId)
	{
        $obj_regions_op = kernel::single('eccommon_regions_operation');
        $this->pagedata['area'] = $obj_regions_op->getRegionById($pId);
        $this->pagedata['area_pid'] = $pId;
        $this->display('regions/area_child_treeList.html');
    }

    /**
     * 获取ChildSub
     * @return mixed 返回结果
     */
    public function getChildSub()
	{
		$obj_regions_op = kernel::single('eccommon_regions_operation');
        $this->pagedata['area'] = $obj_regions_op->getRegionById($_POST['regionId']);
        $this->display('regions/area_child_sub_treeList.html');
    }
}
