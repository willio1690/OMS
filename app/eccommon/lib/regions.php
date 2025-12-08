<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_regions
{
    // 应用实例对象
    static private $app='eccommon';

    // 模型实例
    static private $model;

    public $regions;

    // 构造方法
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        if(!isset(self::$model)){
            self::$model = app::get(self::$app)->model('regions');
        }
    }

    /**
     * 获取OneById
     * @param mixed $region_id ID
     * @param mixed $cols cols
     * @return mixed 返回结果
     */
    public function getOneById($region_id, $cols = '*'){
        $res = self::$model->dump(intval($region_id), $cols);
        return $res;
    }

    /**
     * 获取ListByIds
     * @param mixed $region_ids ID
     * @return mixed 返回结果
     */
    public function getListByIds($region_ids){
        $res = self::$model->getList('*',array('region_id'=>$region_ids));
        return $res;
    }

    public function getList($cols = '*',$filter=array(),$offset=0,$limit=-1,$orderType=null){
        $res = self::$model->getList($cols,$filter,$offset,$limit,$orderType);
        return $res;
    }

    /**
     * 获取AllChildById
     * @param mixed $region_id ID
     * @param mixed $include include
     * @return mixed 返回结果
     */
    public function getAllChildById($region_id,$include = 'containSelf'){
        $tmpRow = self::$model->dump(intval($region_id), 'region_path');

        if($include == 'containSelf'){
            $ext_sql = '';
        }else{
            $ext_sql = " AND region_id!=".$region_id."";
        }

        $sql = "select * from sdb_eccommon_regions where region_path like '".$tmpRow['region_path']."%' ".$ext_sql."";
        $row = self::$model->db->select($sql);

        return $row;
    }

    /**
     * 获取OneByName
     * @param mixed $local_name local_name
     * @return mixed 返回结果
     */
    public function getOneByName($local_name){
        $res = self::$model->dump(array('local_name'=>$local_name),'*');
        return $res;
    }

    /**
     * 获取RegionById
     * @param mixed $regionId ID
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getRegionById($regionId='',$type='array')
    {
        if ($regionId){
			$aTemp = self::$model->getList('region_id,p_region_id,local_name,ordernum,region_path', array('p_region_id' => $regionId), 0, -1, 'ordernum ASC,region_id ASC');
        }else{
			$aTemp = self::$model->getList('region_id,p_region_id,local_name,ordernum,region_path', array('region_grade' => '1'), 0, -1, 'ordernum ASC,region_id ASC');
        }

        if (is_array($aTemp)&&count($aTemp) > 0)
        {
            foreach($aTemp as $key => $val)
            {
                $aTemp[$key]['p_region_id']=intval($val['p_region_id']);
                $aTemp[$key]['step'] = intval(substr_count($val['region_path'],','))-1;
                $aTemp[$key]['child_count'] = $this->getChildCount($val['region_id']);
            }
        }

        if($type =='array'){
            return $aTemp;
        }else{
            return json_encode($aTemp);
        }
    }

    /**
     * 获取Map
     * @param mixed $prId ID
     * @return mixed 返回结果
     */
    public function getMap($prId='')
	{
        if ($prId){
            $sql="select region_id,region_grade,local_name,ordernum from sdb_eccommon_regions as r where r.p_region_id=".intval($prId)." order by ordernum asc,region_id";
        }else{
            $sql="select region_id,region_grade,local_name,ordernum from sdb_eccommon_regions as r where r.p_region_id is null order by ordernum asc,region_id";
        }

        $row = self::$model->db->select($sql);

        if (isset($row) && $row)
        {
            foreach ($row as $key => $val)
			{
                $this->regions[] = array(
                    "local_name"=>$val['local_name'],
                    "region_id"=>$val['region_id'],
                    "region_grade"=>$val['region_grade'],
                    "ordernum"=>$val['ordernum']
                );

                $val['child_count'] = $this->getChildCount($val['region_id']);
                if ($val['child_count'] > 0 ){
                    $this->getMap($val['region_id']);
                }
            }
        }
    }

    private function getChildCount($region_id)
    {
		$cnt = self::$model->count(array('p_region_id' => intval($region_id)));
		return $cnt;
    }

	/**
	 * 地区字符串格式验证
	 * 正则匹配地区是否为本系统的标准地区格式，标准格式原样返回，非标准格式试图转换标准格式返回，否则
	 * @access static
	 * @param string $area 待验证地区字符串
	 * @return string 转换后的本系统标准格式地区
	 */
	public function region_validate(&$area){
		$is_correct_area = $this->is_correct_region($area);
		if (!$is_correct_area){
			//非标准格式进行转换
			$this->local_region($area);
		}
	}

	/**
	 * ECOS本地标准地区格式判断
	 * @access public
	 * @param string $area 地区字符串，如：malind:上海/徐汇区:22
	 * @return boolean
	 */
	public function is_correct_region($area){
	    $pattrn = "/^([a-zA-Z]+)\:(\S+)\:(\d+)$/";
	    if (preg_match($pattrn, $area)){
	        return true;
	    }else{
	        return false;
	    }
	}

	/**
	 * 本系统标准地区格式转换
	 * 正则匹配地区是否为本系统的标准地区格式，转换成功返回标准地区格式，转换失败原地区字符串返回
	 * @access static
	 * @param string $area 待转换地区字符串
	 * @return string  转换后的本系统标准格式地区
	 */
	public function local_region(&$area)
	{
	    $tmp_area = explode("/",$area);
	    
	    //地区初始值临时存储
        $ini_first_name = trim($tmp_area[0]); //省
        $ini_second_name = trim($tmp_area[1]); //市
        $ini_third_name = trim($tmp_area[2]); //区
        
        //新增四、五级地区
        $ini_four_name    = trim($tmp_area[3]); //镇、街道
        $ini_five_name    = trim($tmp_area[4]); //村
        
        $tmp_area2 = preg_replace("/省|市|县|区|镇|乡/","",$tmp_area);
	    $first_name = trim($tmp_area2[0]);
	    
	    //自治区兼容
	    $tmp_first_name = $this->area_format($first_name);
	    if ($tmp_first_name) $first_name = $tmp_first_name;
        $second_name = trim($tmp_area2[1]);
        $third_name = trim($tmp_area2[2]);

        //新增四、五级地区
        $four_name = trim($tmp_area2[3]); //镇、街道
        $five_name = trim($tmp_area2[4]); //村
        
	    $region_first = $region_second = $region_third = array();
	    
	    //获取省region_id
	    if ($first_name){
	        //TODO：针对北京省份数据存在BOM头进行兼容
            if (strstr($first_name, '北京')){
                $bom_first_name = chr(239).chr(187).chr(191).$first_name;
                $region_first = self::$model->dump(array('local_name|head'=>$bom_first_name,'region_grade'=>'1'), 'package,region_id,local_name');
                if (empty($region_first)){
                  $region_first = self::$model->dump(array('local_name|head'=>$first_name,'region_grade'=>'1'), 'package,region_id,local_name');
                }
            }else{
                $region_first = self::$model->dump(array('local_name|head'=>$first_name,'region_grade'=>'1'), 'package,region_id,local_name');
            }
            $first_name = $region_first['local_name'];
			if (!$first_name){
			    $region_first = array(
			        'local_name' =>$ini_first_name,
                    'package' =>'mainland',
                    'region_grade' =>'1',
			    );
                $region_first['source'] = 'platform';
			    self::$model->save($region_first);
			    $first_name = $region_first['local_name'];
			    $region_path = ",".$region_first['region_id'].",";
                //更新region_path字段
                self::$model->update(array('region_path'=>$region_path), array('region_id'=>$region_first['region_id']));
			}
	    }
	    
	    //获取市region_id
	    if ($second_name){
	        //精确查找
	        $second_filter = array('local_name'=>trim($tmp_area[1]),'region_grade'=>'2','p_region_id'=>$region_first['region_id']);
            $region_second = self::$model->dump($second_filter, 'package,region_id,p_region_id,local_name');
            if (empty($region_second['local_name'])){
                //模糊查找
    	        $second_filter = array('local_name|head'=>$second_name,'region_grade'=>'2','p_region_id'=>$region_first['region_id']);
    	        $region_second = self::$model->dump($second_filter, 'package,region_id,p_region_id,local_name');
            }
	        $second_name = $region_second['local_name'];
            if (!$second_name){
                $region_second = array(
                    'local_name' =>$ini_second_name,
                    'p_region_id' =>$region_first['region_id'],
                    'package' =>'mainland',
                    'region_grade' =>'2',
                );
                $region_second['source'] = 'platform';
                self::$model->save($region_second);
                $second_name = $region_second['local_name'];
                $region_path = ",".$region_first['region_id'].",".$region_second['region_id'].",";
                //更新region_path字段
                self::$model->update(array('region_path'=>$region_path), array('region_id'=>$region_second['region_id']));
                //更新省级haschild字段
                self::$model->update(array("haschild"=>"1"), array('region_id'=>$region_first['region_id']));
            }
	    }
	    
	    //获取区、县region_id
	    if ($third_name){
	        if (!$region_second['region_id']){
	    	    //先根据第三级查出所有第二级
	            $filter = array('local_name|head'=>$third_name);
                $regions = self::$model->getList('p_region_id', $filter, 0, -1);
                if ($regions){
                    foreach ($regions as $k=>$v){
                        $region_second_tmp = self::$model->dump(array('region_id'=>$v['p_region_id'],'region_grade'=>'2'), 'region_path,package,region_id,p_region_id,local_name');
                        $tmp = explode(",",$region_second_tmp['region_path']);
                        if (in_array($region_first['region_id'],$tmp)){
                            $region_second = $region_second_tmp;
        	                $second_name = $region_second['local_name'];
                            break;
                        }
                    }
                }
            }
            
            //精确查找
            $third_filter = array('local_name'=>trim($tmp_area[2]),'region_grade'=>'3','p_region_id'=>$region_second['region_id']);
            $region_third = self::$model->dump($third_filter, 'package,region_id,p_region_id,local_name');
            if (empty($region_third['local_name'])){
                //模糊查找
                $third_filter = array('local_name|head'=>$third_name,'region_grade'=>'3','p_region_id'=>$region_second['region_id']);
                $region_third = self::$model->dump($third_filter, 'package,region_id,p_region_id,local_name');
            }
            
            $third_name = $region_third['local_name'];
	        if (!$third_name){
                $region_third = array(
                    'local_name' =>$ini_third_name,
                    'p_region_id' =>$region_second['region_id'],
                    'package' =>'mainland',
                    'region_grade' =>'3',
                );
                $region_third['source'] = 'platform';
                self::$model->save($region_third);
                $third_name = $region_third['local_name'];
                $region_path = ",".$region_first['region_id'].",".$region_second['region_id'].",".$region_third['region_id'].",";
                
                //更新region_path字段
                self::$model->update(array('region_path'=>$region_path), array('region_id'=>$region_third['region_id']));
                
                //更新市级haschild字段
                self::$model->update(array("haschild"=>"1"), array('region_id'=>$region_second['region_id']));
            }
        }
        
        //[新增四级地区]获取镇、街道region_id
        if($four_name && $region_third['region_id']){
            //精确查找
            $four_filter = array('local_name'=>trim($tmp_area[3]), 'region_grade'=>'4', 'p_region_id'=>$region_third['region_id']);
            $region_four = self::$model->dump($four_filter, 'package,region_id,p_region_id,local_name');
            if (empty($region_four['local_name'])){
                //模糊查找
                $four_filter = array('local_name|head'=>$four_name, 'region_grade'=>'4', 'p_region_id'=>$region_third['region_id']);
                $region_four = self::$model->dump($four_filter, 'package,region_id,p_region_id,local_name');
            }
            $four_name = $region_four['local_name'];
            
            //新添加四级地区
            if (!$four_name){
                $region_four = array(
                        'local_name' =>$ini_four_name,
                        'p_region_id' =>$region_third['region_id'],
                        'package' =>'mainland',
                        'region_grade' =>'4',
                        'ordernum' => '99',
                );
                $region_four['source'] = 'platform';
                self::$model->save($region_four);
                
                $four_name = $region_four['local_name'];
                
                //更新region_path字段
                $region_path = ','.$region_first['region_id'].','.$region_second['region_id'].','.$region_third['region_id'] .',';
                $region_path .= $region_four['region_id'] .',';
                self::$model->update(array('region_path'=>$region_path), array('region_id'=>$region_four['region_id']));
                
                //更新三级地区有子记录
                self::$model->update(array('haschild'=>1), array('region_id'=>$region_third['region_id']));
            }
        }
        
        //[新增五级地区]获取乡region_id
        if($five_name && $region_four['region_id']){
            //精确查找
            $five_filter = array('local_name'=>trim($tmp_area[4]), 'region_grade'=>'5', 'p_region_id'=>$region_four['region_id']);
            $region_five = self::$model->dump($five_filter, 'package,region_id,p_region_id,local_name');
            if (empty($region_five['local_name'])){
                //模糊查找
                $five_filter = array('local_name|head'=>$five_name, 'region_grade'=>'5', 'p_region_id'=>$region_four['region_id']);
                $region_five = self::$model->dump($five_filter, 'package,region_id,p_region_id,local_name');
            }
            $five_name = $region_five['local_name'];
            
            //新添加五级地区
            if (!$five_name){
                $region_five = array(
                        'local_name' =>$ini_five_name,
                        'p_region_id' =>$region_four['region_id'],
                        'package' =>'mainland',
                        'region_grade' =>'5',
                        'ordernum' => '99',
                );
                $region_five['source'] = 'platform';
                self::$model->save($region_five);
                
                $five_name = $region_five['local_name'];
                
                //更新region_path字段
                $region_path = ','.$region_first['region_id'].','.$region_second['region_id'].','.$region_third['region_id'].',';
                $region_path .= $region_four['region_id'] . ',' . $region_five['region_id'] . ',';
                self::$model->update(array('region_path'=>$region_path), array('region_id'=>$region_five['region_id']));
                
                //更新四级地区有子记录
                self::$model->update(array('haschild'=>1), array('region_id'=>$region_four['region_id']));
            }
        }
        
    	$return = false;
    	
    	//region_id
        if ($region_five['region_id']){
            //乡region_id
            $region_id = $region_five['region_id'];
            $package = $region_five['package'];
        }elseif($region_four['region_id']){
            //镇、街道region_id
            $region_id = $region_four['region_id'];
            $package = $region_four['package'];
        }elseif($region_third['region_id']){
            $region_id = $region_third['region_id'];
            $package = $region_third['package'];
        }elseif ($region_second['region_id']){
            $region_id = $region_second['region_id'];
            $package = $region_second['package'];
        }
        
        //四、五级地区
        if($four_name && $five_name){
            $region_area = array_filter(array($first_name, $second_name, $third_name, $four_name, $five_name));
        }elseif($four_name){
            $region_area = array_filter(array($first_name, $second_name, $third_name, $four_name));
        }else{
            $region_area = array_filter(array($first_name,$second_name,$third_name));
        }
        
        $region_area = implode("/", $region_area);
        if ($region_area || $region_id){
            $area = $package.":".$region_area.":".$region_id;
            $return = true;
        }

    	//去除多余分隔符“/”
    	if ($return==false){
            $area = implode("/", array_filter($tmp_area));
    	}
    }

    /**
     * 前端店铺三级地区本地临时转换
     * @param $area
     */
    public function area_format($area){
        $area_format = array(
            '内蒙古自治' => '内蒙古',
            '广西壮族自治' => '广西',
            '西藏自治' => '西藏',
            '宁夏回族自治' => '宁夏',
            '新疆维吾尔自治' => '新疆',
            '香港特别行政' => '香港',
            '澳门特别行政' => '澳门',
        );
        if ($area_format[$area]){
            return $area_format[$area];
        }else{
            return false;
        }
    }

    /**
     * 拆分标准格式为：省市县
     * @param string $area
     * @return array 下标从0开始，依次代表：省、市、县
     */
    public function split_area(&$area){
       preg_match("/:(.*):/", $area,$tmp_area);
       if($tmp_area[1]){
           $tmp_area = explode('/', $tmp_area[1]);
           $area = $tmp_area;
       }
    }
    
    /**
     * 检测省市区/县是否在地址库
     * @param $area
     * @return array
     * @date 2024-12-18 2:39 下午
     */
    public function checkRegion($area)
    {
        $regionObj = app::get('eccommon')->model('regions');
        
        $tmp_area = explode("/", $area);
        $tmp_area2  = preg_replace("/省|市|县|区/", "", $tmp_area);
        $first_name = trim($tmp_area2[0]);
    
        //自治区兼容
        $tmp_first_name = kernel::single('ome_func')->area_format($first_name);
        if ($tmp_first_name) $first_name = $tmp_first_name;
        
        $second_name = trim($tmp_area2[1]);
        $third_name  = trim($tmp_area2[2]);
        
        //获取省region_id
        $region_first = $region_second = $region_third = [];
        
        if ($first_name) {
            if (strstr($first_name, '北京')) {
                $bom_first_name = chr(239) . chr(187) . chr(191) . $first_name;
                $region_first   = $regionObj->db_dump(array('local_name|head' => $bom_first_name, 'region_grade' => '1'), 'package,region_id,local_name');
                if (empty($region_first)) {
                    $region_first = $regionObj->db_dump(array('local_name|head' => $first_name, 'region_grade' => '1'), 'package,region_id,local_name');
                }
            } else {
                $region_first = $regionObj->db_dump(array('local_name|head' => $first_name, 'region_grade' => '1'), 'package,region_id,local_name');
            }
            //未查到省份
            if (!$region_first) {
                return [false, '省份：'. $tmp_area[0]];
            }
        }
        
        //获取市region_id
        if ($second_name) {
            //精确查找
            $second_filter = array('local_name' => trim($tmp_area[1]), 'region_grade' => '2', 'p_region_id' => $region_first['region_id']);
            $region_second = $regionObj->db_dump($second_filter, 'package,region_id,p_region_id,local_name');
            
            if (empty($region_second['local_name'])) {
                //模糊查找
                $second_filter = array('local_name|head' => $second_name, 'region_grade' => '2', 'p_region_id' => $region_first['region_id']);
                $region_second = $regionObj->db_dump($second_filter, 'package,region_id,p_region_id,local_name');
            }
            if (!$region_second) {
                return [false, '城市：'. $tmp_area[1]];
            }
        }
        
        //获取区、县region_id
        if ($third_name) {
            //精确查找
            $third_filter = array('local_name' => trim($tmp_area[2]), 'region_grade' => '3', 'p_region_id' => $region_second['region_id']);
            $region_third = $regionObj->db_dump($third_filter, 'package,region_id,p_region_id,local_name');
            if (empty($region_third['local_name'])) {
                //模糊查找
                $third_filter = array('local_name|head' => $third_name, 'region_grade' => '3', 'p_region_id' => $region_second['region_id']);
                
                $region_third = $regionObj->db_dump($third_filter, 'package,region_id,p_region_id,local_name');
            }
            if (!$region_third) {
                return [false, '区/县：'. $tmp_area[2]];
            }
        }
    
        if ($region_third['region_id']){
            $region_id = $region_third['region_id'];
            $package = $region_third['package'];
        }elseif ($region_second['region_id']){
            $region_id = $region_second['region_id'];
            $package = $region_second['package'];
        }
        $region_area = array_filter(array($first_name,$second_name,$third_name));
        $region_area = implode("/", $region_area);
    
        if ($region_area || $region_id){
            $area = $package.":".$region_area.":".$region_id;
        }
        
        return [true, '检测通过', $area];
    }

}
