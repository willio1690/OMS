<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_mdl_ome_type extends dbeav_model{
    /**
     * 获取_shop
     * @return mixed 返回结果
     */
    public function get_shop(){
        //店铺 目前写死过滤掉o2o门店
        $sql = 'SELECT shop_id as type_id,name,relate_id FROM '.
            kernel::database()->prefix.'ome_shop as S LEFT JOIN '.
            kernel::database()->prefix.'omeanalysts_relate as R ON R.relate_key=S.shop_id where S.s_type=1';
        $row = $this->db->select($sql);
        return $row;
    }

    /**
     * 获取_dly_corp
     * @return mixed 返回结果
     */
    public function get_dly_corp(){//物流公司
        $logi_ids = array('全部');
        $dlyCorpObj = app::get('ome')->model('dly_corp');
        $dlyCorps = $dlyCorpObj->getList('corp_id,name');
        if($_POST['branch_id']){
            //根据仓库获取指定的物流
            $branch_corp_lib = kernel::single("ome_branch_corp");
            foreach($dlyCorps as $dlyCorp){
                $corp_ids = $branch_corp_lib->getCorpIdsByBranchId(array($_POST['branch_id']));
                if(in_array($dlyCorp['corp_id'],$corp_ids)){
                    $logi_ids[$dlyCorp['corp_id']] = $dlyCorp['name'];
                }
            }
        }else{
            foreach($dlyCorps as $dlyCorp){
                $logi_ids[$dlyCorp['corp_id']] = $dlyCorp['name'];
            }
        }
        return $logi_ids;
    }

    /**
     * 获取_branch
     * @return mixed 返回结果
     */
    public function get_branch(){//仓库
        $branchObj = app::get('ome')->model('branch');
        $branchs = $branchObj->getList('branch_id,name',array('is_deliv_branch'=>'true'));
        foreach($branchs as $key=>$val){
            $branchs[$key]['type_id'] = $branchs[$key]['branch_id'];
        }
        return $branchs;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'order_id' => array (),
            ),
            'idColumn' => 'order_id',
        );
        return $schema;
    }

    /**
     * 获取品牌
     * 
     * @return void
     * @author 
     * */
    function get_brand()
    {
        $sql = 'SELECT brand_id as type_id,brand_name as name FROM '.kernel::database()->prefix.'ome_brand';
        $row = $this->db->select($sql);
        return $row;
    }

    /**
     * 获取商品类型
     * 
     * @return void
     * @author 
     * */
    function get_gtype()
    {
        $sql = 'SELECT type_id,name FROM '.kernel::database()->prefix.'ome_goods_type';
        $row = $this->db->select($sql);
        return $row;
    }

    /**
     * 获取售后类型
     * 
     * @return void
     * @author 
     * */
    function get_return_type()
    {
        $data[0]['type_id'] = 'return';
        $data[0]['name'] = '退货';
        $data[1]['type_id'] = 'change';
        $data[1]['name'] = '换货';
        //$data[2]['type_id'] = 'refund';
        //$data[2]['name'] = '退款';                                 
        return $data;
    }

    /**
     * 获取售后服务类型
     * 
     * @return void
     * @author 
     * */
    function get_problem_type()
    {
        $Oreturn_problem = app::get('ome')->model('return_product_problem');
        $catlist = $Oreturn_problem->getList('problem_id as type_id,problem_name as name');
        return $catlist;
    } 
   function getProductType(){
       $product_type[0]['type_id'] = 'normal';
       $product_type[0]['name'] = '普通商品';
       $product_type[1]['type_id'] = 'pkg';
       $product_type[1]['name'] = '捆绑商品'; 
       return $product_type;
   }

   /**
     * 获取店铺类型
     * 
     * @return array
     */
    public function getShopType()
    {
        $shop_mdl = app::get("ome")->model("shop");
        $shop_datas = $shop_mdl->getList("shop_id,name,shop_type");
        
        //店铺类型列表
        $shoptype = ome_shop_type::get_shop_type();
        
        //list
        $shop_types = array();
        foreach($shop_datas as $v)
        {
            $shop_type = $v['shop_type'];
            
            //check
            if(empty($shop_type)){
                continue;
            }
            
            if(empty($shoptype[$shop_type])){
                continue;
            }
            
            $shop_types[$shop_type] = array(
                    'type_id' => $v['shop_type'],
                    'name' => $shoptype[$shop_type],
            );
        }
        
        return $shop_types;
    }

    /**
     * 获取Org
     * @return mixed 返回结果
     */
    public function getOrg()
    {
        $orgModel = app::get('ome')->model('operation_organization');
        $orgs = $orgModel->getList('org_id,code,name', ['disabled' => 'false', 'status' => 1]);
        $org_data = [];
        foreach ($orgs as $org) {
            $org_data[$org['org_id']] = [
                'name' => '[' . $org['code'] . ']' . $org['name'],
                'type_id'=>$org['org_id'],
            ];
        }
        return $org_data;
    }
}
