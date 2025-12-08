<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_dly_corp extends dbeav_model{

    function  save(&$data,$mustUpdate = null){
        $Odly_corp_area = $this->app->model('dly_corp_area');

        if($data['protect']) $data['protect_rate'] = $data['protect_rate']/100;
        $data['ordernum'] = intval($data['ordernum']);
        if($data['area_fee_conf'] && is_array($data['area_fee_conf'])){
            $data['dt_expressions'] = '';
            foreach($data['area_fee_conf'] as $key=>$value){
                if($value['dt_useexp']==0){//如果未使用公式则使用默认
                    $data['area_fee_conf'][$key]['dt_expressions'] = "{{w-0}-0.4}*{{{".$value['firstunit']."-w}-0.4}+1}*".$value['firstprice']."+ {{w-".$value['firstunit']."}-0.6}*[(w-".$value['firstunit'].")/".$value['continueunit']."]*".$value['continueprice']."";
                }
                if($data['corp_id']!=''){
                    $Odly_corp_area->get_corp_area($data['corp_id'],$value['areaGroupId']);
                }
            }
            $data['area_fee_conf'] = serialize($data['area_fee_conf']);
        }else{
            if($data['dt_useexp']==0){//如果未使用公式则使用默认
                $data['dt_expressions'] = "{{w-0}-0.4}*{{{".$data['firstunit']."-w}-0.4}+1}*".$data['firstprice']."+ {{w-".$data['firstunit']."}-0.6}*[(w-".$data['firstunit'].")/".$data['continueunit']."]*".$data['continueprice']."";
            }
        }

      return parent::save($data,$mustUpdate);
    }

    //获取物流公司指定的配送地区
    function get_corp_region(){
        $corpAreaObj = $this->app->model('dly_corp_area');
        $rows = $corpAreaObj->getList('corp_id,region_id');
        $corp_region = array();
        foreach($rows as $v){
            $corp_region[$v['corp_id']][] = $v['region_id'];
        }
        return $corp_region;
    }

    function getRegionById($parent_id){
        $res = kernel::single('eccommon_regions')->getRegionById($parent_id);
        return $res;
    }

    function corp_default($model=''){
        // 根据配送模式加载对应的配置文件
        if($model == 'instatnt'){
            // 同城配送 - 加载tongcheng.php
            $dly_corp = require_once(app::get('ome')->app_dir.'/resource/carriers/tongcheng.php');
        }elseif($model == 'seller'){
            // 商家配送 - 加载seller.php
            $dly_corp = require_once(app::get('ome')->app_dir.'/resource/carriers/seller.php');
        }else{
            // 其他配送模式 - 加载express.php
            $dly_corp = require_once(app::get('ome')->app_dir.'/resource/carriers/express.php');
        }
        
        return $dly_corp;
    }

    function set_area($region_ids,$dly_corp_id){
        $areaObj = $this->app->model('dly_corp_area');
        $region_ids = kernel::single('ome_region')->get_region_node($region_ids);
        foreach ($region_ids as $area_id){
            $data['corp_id'] = $dly_corp_id;
            $data['region_id'] = $area_id;
            $areaObj->save($data);
        }
    }

    /**
     * 新增价格等设置存入明细表
     * 
     */
    function set_areaConf($region_ids,$dly_corp_id,$area_conf){
        $areaObj = $this->app->model('dly_corp_items');
        $region_ids = kernel::single('ome_region')->get_region_node($region_ids);
        foreach ($region_ids as $area_id){
            $data['corp_id'] = $dly_corp_id;
            $data['region_id'] = $area_id;
            $data['firstunit']=$area_conf['firstunit'];
            $data['continueunit']=$area_conf['continueunit'];
            $data['firstprice']=$area_conf['firstprice'];
            $data['continueprice']=$area_conf['continueprice'];
            $data['dt_expressions']=$area_conf['dt_expressions'];
            $data['dt_useexp']=$area_conf['dt_useexp'];

            $areaObj->save($data);
        }

    }

    /**
     * 获取物流公司信息
     *
     * @return void
     * @author 
     **/
    public function getCorpInfo($corp_id,$cols = '*')
    {
        static $corps;

        if ($corps[$cols][$corp_id]) return $corps[$cols][$corp_id];

        $corps[$cols][$corp_id] = $this->dump($corp_id,$cols);

        return $corps[$cols][$corp_id];
    }
}

?>
