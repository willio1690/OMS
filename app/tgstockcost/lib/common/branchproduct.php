<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 仓库货品数据基类.不同产品继承此类。各自实现不同方法
 *
 * @date 2012-07-27
 * @version hanbingshu sanow@126.com
 */
class tgstockcost_common_branchproduct
{
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real=false)
    {
        return "sdb_ome_branch_product";
    }

    private function branchproduct_filter($filter = array())
    {
        $where = array(1);
        
        //仓库
        if(isset($filter['branch_id']) && $filter['branch_id'] && $filter['branch_id'] !=0){
            $where[] = " obp.branch_id=".intval($filter['branch_id']);
        }else{
            $Obranch = app::get('ome')->model('branch');
            $branchs = $Obranch->getList('branch_id');
            $branchs_id = array();
            foreach ($branchs as $v) {
                $branchs_id[] = $v['branch_id'];
            }
            $where[] = " obp.branch_id IN ('".implode('\',\'',$branchs_id)."')";
            unset($branchs_id,$branchs,$Obranch);
        }

        //货号
        if(isset($filter['p_bn']) && $filter['p_bn']){
            $where[] = " op.material_bn='". $filter['p_bn'] ."'";
        }

        //货品名称
        if(isset($filter['product_name']) && $filter['product_name']){
            $where[] = " op.material_name like '%".$filter['product_name']."%'";
        }
        
        //基础物料类型
        if(isset($filter['type_id']) && $filter['type_id']){
            // $where[] = ' g.cat_id = '.$filter['type_id'];
            $where[] = ' op.bm_id IN(SELECT bm_id FROM sdb_material_basic_material_ext WHERE cat_id="'.$filter['type_id'].'")';
        }
        
        //基础物料品牌
        if(isset($filter['brand']) && $filter['brand']){
            // $where[] = ' g.brand_id = '.$filter['brand'];
            $where[] = ' op.bm_id IN(SELECT bm_id FROM sdb_material_basic_material_ext WHERE brand_id="'.$filter['brand'].'")';
        }
        
        return implode(' and ', $where);
    }

    /**
     * 获取FINDER列表上仓库货品表数据(库存成本统计)
     * 
     * @param string $cols
     * @param unknown $filter
     * @param number $offset
     * @param unknown $limit
     * @param string $orderType
     * @return string
     */
    function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $brandProductMdl = app::get('ome')->model('branch_product');
        
        //商品品牌
        $brandList    = array();
        $oBrand       = app::get('ome')->model('brand');
        $tempData     = $oBrand->getList('brand_id, brand_name', '', 0, -1);
        foreach ($tempData as $key => $val)
        {
            $brandList[$val['brand_id']]    = $val['brand_name'];
        }
        
        //商品类型
        $goodsTypeList    = array();
        $oType        = app::get('ome')->model('goods_type');
        $tempData     = $oType->getList('type_id, name', '', 0, -1);
        foreach ($tempData as $key => $val)
        {
            $goodsTypeList[$val['type_id']]    = $val['name'];
        }
        unset($tempData, $oBrand, $oType);
        
        //select
        $sql = "select obp.*,op.material_bn AS bn,op.material_name AS name,b.name as branch_name, op.bm_id, g.specifications, g.brand_id, g.cat_id
                from sdb_ome_branch_product as obp 
                JOIN (sdb_material_basic_material as op,sdb_ome_branch as b) 
                ON obp.product_id=op.bm_id and obp.branch_id=b.branch_id 
                LEFT JOIN sdb_material_basic_material_ext AS g ON op.bm_id=g.bm_id
                where op.visibled=1 and ".$this->branchproduct_filter($filter);
        
        if($orderType) $sql = $sql." order by ".$orderType;

        $aData = $brandProductMdl->db->selectLimit($sql,$limit,$offset);

        foreach($aData as $a_k=>$a_v)
        {
            //基础物料扩展信息
            $aTmp['goods_specinfo']    = $a_v['specifications'];
            $aTmp['type_id']           = $goodsTypeList[$a_v['cat_id']];
            $aTmp['brand']             = $brandList[$a_v['brand_id']];
            
            $aTmp['p.bn'] = $a_v['bn']?$a_v['bn']:'-';
            $aTmp['product_name'] = $a_v['name']?$a_v['name']:'-';
            $aTmp['bp.store'] = $a_v['store']?$a_v['store']:0;
            $aTmp['unit_cost'] = $a_v['unit_cost']?$a_v['unit_cost']:0;
            
            //$aTmp['goods_bn'] = $a_v['goods_bn']?$a_v['goods_bn']:'-';
            
            $aTmp['inventory_cost'] = $a_v['inventory_cost']?$a_v['inventory_cost']:0;
            $aTmp['id'] = $a_v['product_id']."-".$a_v['branch_id'];
            $aTmp['branch_id'] = $a_v['branch_name']?$a_v['branch_name']:'-';
            $aTmp['entity_unit_cost'] = $a_v['entity_unit_cost']? '￥'.$a_v['entity_unit_cost']:'￥0.00';
            $list[]= $aTmp;
        }

        unset($aData,$aTmp);

        return $list;
    }

    function header_getlist($cols='*', $filter=array())
    {
        $brandProductMdl = app::get('ome')->model('branch_product');
        
        $sql = "select ".$cols." from sdb_ome_branch_product as obp JOIN (sdb_material_basic_material as op,sdb_ome_branch as b) ON obp.product_id=op.bm_id and obp.branch_id=b.branch_id where op.visibled=1 and ".$this->branchproduct_filter($filter);
        
        $aData = $brandProductMdl->db->select($sql);

        return $aData;
    }

    public function branchproduct_count($filter = array())
    {
        $brandProductMdl = app::get('ome')->model('branch_product');
        
        $sql = "select count(obp.branch_id) as _count from sdb_ome_branch_product as obp JOIN (sdb_material_basic_material as op,sdb_ome_branch as b) ON obp.product_id=op.bm_id and obp.branch_id=b.branch_id where op.visibled=1 and ".$this->branchproduct_filter($filter);

        return $brandProductMdl->db->count($sql);
    }

    /*进销存统计调用方法*/
    private function stock_filter($filter = array())
    {
        $where = array(1);
        
        //仓库
        if(isset($filter['branch_id']) && $filter['branch_id']){
            $where[] = " obp.branch_id=".intval($filter['branch_id']);
        }else{
            $Obranch = app::get('ome')->model('branch');
            $branchs = $Obranch->getList('branch_id');
            $branchs_id = array();
            foreach ($branchs as $v) {
                $branchs_id[] = $v['branch_id'];
            }
            $where[] = " obp.branch_id IN (".implode(',',$branchs_id).")";
            unset($branchs_id);
        }

        //货号
        if(isset($filter['product_bn']) && $filter['product_bn']){
            $where[] = " op.material_bn='". $filter['product_bn'] ."'";
        }
        
        //货品名称
        if(isset($filter['product_name']) && $filter['product_name']){
            $where[] = " op.material_name like '".trim($filter['product_name'])."%'";
        }
        
        //品牌
        if(isset($filter['brand']) && $filter['brand'] ){
            $where[] = " g.brand_id=".intval($filter['brand']); 
        }
        
        //商品类型
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' g.cat_id = '.$filter['type_id'];
        }
        
        return implode(' AND ', $where);
    }    

    function stock_getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $brandProductMdl = app::get('ome')->model('branch_product');
        $basicMaterialLib    = kernel::single('material_basic_material');

        if(empty($filter['time_from']) || empty($filter['time_to'])) return false;
        $stockcost_common_iostockrecord = $this->get_instance_iostockrecord();
        
        //select
        $sql = "SELECT obp.branch_id,obp.product_id,op.material_bn AS bn,op.material_name AS name FROM sdb_ome_branch_product AS obp ";
        $sql .= " LEFT JOIN sdb_material_basic_material AS op ON obp.product_id=op.bm_id LEFT JOIN sdb_material_basic_material_ext AS g ON op.bm_id=g.bm_id ";
        $sql .= " WHERE op.visibled=1 AND ".$this->stock_filter($filter);
        
        $data = $brandProductMdl->db->selectLimit($sql,$limit,$offset);

        $all_start = $all_in_data = $all_out_data = array();

        //$get_all_start = $this->get_start($filter['time_from'],'',$filter['branch_id'],true);//获取from_time时间段内的货品期初数据
        $get_all_start = $this->get_new_start($filter['time_from'],'',$filter['branch_id'],true);//获取from_time时间段内的货品期初数据

        foreach ($get_all_start as $k => $v) {
            $all_start[$v['branch_id']][$v['product_id']]['stock_num'] = $v['stock_num'];
            $all_start[$v['branch_id']][$v['product_id']]['unit_cost'] = $v['unit_cost'];
            $all_start[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }
        
        //获取期末数据
        $_get_end_data =  $this->get_end_data($filter['time_to'],'',$filter['branch_id'],true);
        foreach ($_get_end_data as $k => $v) {
            $get_end_data[$v['branch_id']][$v['product_id']]['stock_num'] = $v['stock_num'];
            $get_end_data[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }

        unset($get_all_start);

        $get_in_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],'',$filter['branch_id'],1,true);//获取from_time-to_time时间段内的货品入库数据

        foreach ($get_in_data as $k => $v) {
            $all_in_data[$v['branch_id']][$v['product_id']]['nums'] = $v['nums'];
            $all_in_data[$v['branch_id']][$v['product_id']]['unit_cost'] = $v['unit_cost'];
            $all_in_data[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }
        
        unset($get_in_data);

        $get_out_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],'',$filter['branch_id'],0,true);//获取from_time-to_time时间段内的货品出库数据
        foreach ($get_out_data as $k => $v) {
            $all_out_data[$v['branch_id']][$v['product_id']]['nums'] = $v['nums'];
            $all_out_data[$v['branch_id']][$v['product_id']]['unit_cost'] = $v['unit_cost'];
            $all_out_data[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }
        unset($get_out_data);

        foreach($data as $k=>$val)
        {
            $product_info    = $basicMaterialLib->getBasicMaterialExt($val['product_id']);
            
            $aTmp['product_bn'] = $val['bn']?$val['bn']:'-';
            $aTmp['product_name'] = $val['name']?$val['name']:'-';

            $aTmp['goods_bn'] = $val['goods_bn']?$val['goods_bn']:'-';
            $aTmp['type_name'] = $val['type_id']?$val['type_id']:'-';
            $aTmp['brand_name'] = $val['brand_id']?$val['brand_id']:'-';
            $aTmp['spec_info'] = $product_info['specifications']?$product_info['specifications']:'-';
            $aTmp['unit'] = $product_info['unit']?$product_info['unit']:'-';
            
            //货品期初数据
            $aTmp['start_nums'] = $all_start[$val['branch_id']][$val['product_id']]['stock_num']?$all_start[$val['branch_id']][$val['product_id']]['stock_num']:0;
            $aTmp['start_unit_cost'] = $all_start[$val['branch_id']][$val['product_id']]['unit_cost']?$all_start[$val['branch_id']][$val['product_id']]['unit_cost']:0;
            $aTmp['start_inventory_cost'] = $all_start[$val['branch_id']][$val['product_id']]['inventory_cost']?$all_start[$val['branch_id']][$val['product_id']]['inventory_cost']:0;
            
            //货品入库数据
            //$in_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],$val['bn'],$val['branch_id'],1);
            $aTmp['in_nums'] = $all_in_data[$val['branch_id']][$val['product_id']]['nums']?$all_in_data[$val['branch_id']][$val['product_id']]['nums']:0;
            $aTmp['in_unit_cost'] = $all_in_data[$val['branch_id']][$val['product_id']]['unit_cost']?$all_in_data[$val['branch_id']][$val['product_id']]['unit_cost']:0;
            $aTmp['in_inventory_cost'] = $all_in_data[$val['branch_id']][$val['product_id']]['inventory_cost']?$all_in_data[$val['branch_id']][$val['product_id']]['inventory_cost']:0;

            //货品出库数据
            //$out_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],$val['bn'],$val['branch_id'],0);
            $aTmp['out_nums'] = $all_out_data[$val['branch_id']][$val['product_id']]['nums']?$all_out_data[$val['branch_id']][$val['product_id']]['nums']:0;
            $aTmp['out_unit_cost'] = $all_out_data[$val['branch_id']][$val['product_id']]['unit_cost']?$all_out_data[$val['branch_id']][$val['product_id']]['unit_cost']:0;
            $aTmp['out_inventory_cost'] = $all_out_data[$val['branch_id']][$val['product_id']]['inventory_cost']?$all_out_data[$val['branch_id']][$val['product_id']]['inventory_cost']:0;
            
            //新的货品结存数据
            $aTmp['store'] = $get_end_data[$val['branch_id']][$val['product_id']]['stock_num']?$get_end_data[$val['branch_id']][$val['product_id']]['stock_num']:0;
            $aTmp['inventory_cost'] = $get_end_data[$val['branch_id']][$val['product_id']]['inventory_cost']?$get_end_data[$val['branch_id']][$val['product_id']]['inventory_cost']:0;
            if($aTmp['store']){
                $aTmp['unit_cost'] = round($aTmp['inventory_cost']/$aTmp['store'],2);
            }else{
              $aTmp['unit_cost'] = 0;
            }

            //仓库
            $aTmp['branch_id'] = $val['branch_id'];

            $list[] = $aTmp;
        }

        unset($aTmp,$all_out_data,$all_in_data,$all_start,$data);
        
        return $list;
    }

    function stock_count($filter = array())
    {
        $brandProductMdl = app::get('ome')->model('branch_product');
        
        //count
        $sql = "SELECT count(obp.branch_id) AS _count FROM sdb_ome_branch_product AS obp ";
        $sql .= " LEFT JOIN sdb_material_basic_material AS op ON obp.product_id=op.bm_id LEFT JOIN sdb_material_basic_material_ext AS g ON op.bm_id=g.bm_id ";
        $sql .= " WHERE op.visibled=1 AND ".$this->stock_filter($filter);
        
        return $brandProductMdl->db->count($sql);
    }

    /**
     * 获取货品期初数据
     * 
     * @param unknown $from_time
     * @param string $product_id
     * @param string $branch_id
     * @param string $is_all
     * @return unknown
     */
    function get_start($from_time,$product_id = '',$branch_id = '',$is_all = false)
    {
        $from_time = date("Y-m-d",strtotime($from_time));

        $filter['stock_date'] = $from_time;
        if( isset($product_id) && $product_id ){
            $filter['product_id'] = $product_id;
        }

        if( isset($branch_id) && $branch_id ){
            $filter['branch_id'] = $branch_id;
        }

        $dailystock  = app::get("ome")->model("dailystock");
        $daily_data = $dailystock->getList("branch_id,product_id,stock_num,unit_cost,inventory_cost",$filter);

        if($is_all){
            return $daily_data;
        }

        return $daily_data[0];
    }
    
    //获取货品的期初数据
    function get_new_start($from_time,$product_id = '',$branch_id = '',$is_all = false){
        #计算上一天的数据
        $from_time = strtotime($from_time)-86400;
        $from_time = date("Y-m-d", $from_time);
        $filter['stock_date'] =  $from_time;
        if( isset($product_id) && $product_id ){
            $filter['product_id'] = $product_id;
        }
        
        if( isset($branch_id) && $branch_id ){
            $filter['branch_id'] = $branch_id;
        }
        $dailystock  = app::get("ome")->model("dailystock");
        $daily_data = $dailystock->getList("branch_id,product_id,stock_num,inventory_cost",$filter);
        
        if($is_all){
            return $daily_data;
        }
    }
    
    //获取货品期末数据
    function get_end_data($to_time,$product_id = '',$branch_id = '',$is_all = false)
    {
        $to_time = date("Y-m-d",strtotime($to_time));
    
        $filter['stock_date'] = $to_time;
        if( isset($product_id) && $product_id ){
            $filter['product_id'] = $product_id;
        }
    
        if( isset($branch_id) && $branch_id ){
            $filter['branch_id'] = $branch_id;
        }
    
        $dailystock  = app::get("ome")->model("dailystock");
        $daily_data = $dailystock->getList("branch_id,product_id,stock_num,unit_cost,inventory_cost",$filter);
    
        if($is_all){
            return $daily_data;
        }
    
        return $daily_data[0];
    }
    
    /**
     * 获取指定时间范围内货品出入库数据
     * 
     * @param unknown $from_time 开始时间 2012-07-03,$to_time结束时间 2012-07-25,$product_id货品ID,$branch_id仓库ID
     * @param unknown $to_time
     * @param unknown $product_bn
     * @param unknown $branch_id
     * @param unknown $io_type
     * @return number 出库数量，单位成本，库存成本等
     */
    function get_out_stock($from_time,$to_time,$product_bn,$branch_id,$io_type)
    {
        $from_time = strtotime($from_time);
        $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
        if($from_time<$stockcost_install_time) $from_time = $stockcost_install_time;
        $to_time = strtotime($to_time)+(24*3600-1);
        $iostock_mdl = app::get("ome")->model("iostock");
        $stockcost_common_iostockrecord = $this->get_instance_iostockrecord();
        $iostock_type_arr = $stockcost_common_iostockrecord->get_type_id($io_type);//出库类型ID数组
        $out_data = $iostock_mdl->db->selectrow("select sum(nums) as nums,sum(inventory_cost) as inventory_cost from sdb_ome_iostock where bn='".$product_bn."' and branch_id=".intval($branch_id)." and iotime>".intval($from_time)." and iotime<".intval($to_time)." and type_id in (".implode(',',$iostock_type_arr).")");
        if(!$out_data){
            $out_data['nums']=0;
            $out_data['unit_cost']=0;
            $out_data['inventory_cost']=0;
        }
        else{
            if($out_data['nums'])
                $out_data['unit_cost']=round($out_data['inventory_cost']/$out_data['nums'],2);
            else $out_data['unit_cost']=0;
        }
        return $out_data;
    }

    function get_instance_iostockrecord()
    {
        return kernel::single("tgstockcost_common_iostockrecord");
    }

    /**
     * 导出链接URL
     * 
     * @param unknown $params
     * @return string
     */
    function get_export_href($params)
    {
        return '';
    }

    /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @param mixed $pass_data 数据
     * @return mixed 返回值
     */
    public function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false){
        return true;
    }

    function export_csv($data){
         return true;
    }
}