<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_branch extends openapi_api_function_abstract implements openapi_api_function_interface{

    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){

        $branch_bn = '';
        if($params['branch_bn']){
            $branch_bn = $params['branch_bn'];
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
        return  $this->get_all_branchs(1,$branch_bn,$offset,$limit); 
    }


    /**
     * 获取_all_branchs
     * @param mixed $type type
     * @param mixed $branch_bn branch_bn
     * @param mixed $page_no page_no
     * @param mixed $page_size page_size
     * @return mixed 返回结果
     */
    public function get_all_branchs($type = '1',$branch_bn='',$page_no=0,$page_size=-1){
       
        #基础条件，必须是自建仓库
        $sqlstr .= " AND disabled='false' and b_type=1 ";
      
     
        #按仓库编号
        if($branch_bn){
            $branch_bn = str_replace('，',',',$branch_bn);
            $arr_branch_bn = explode(',',$branch_bn);
            if(count($arr_branch_bn) > 1){
                $sqlstr .= " and branch_bn in ("."'".join("','",$arr_branch_bn)."'".")";
            }else{
                $sqlstr .= " AND branch_bn='". $branch_bn ."'";
            }
           
        }
        $branchObj = app::get('ome')->model('branch');
        $sql = "SELECT count(*) as _count FROM sdb_ome_branch WHERE  1=1 ". $sqlstr;
        $countList = $branchObj->db->selectrow($sql);

        if (intval($countList['_count']) > 0) {
            $sql = "SELECT branch_bn,name as 'branch_name',`type` as 'branch_type',area,address,zip,phone,uname,mobile,sex,cutoff_time,latest_delivery_time,ability,owner,is_deliv_branch,weight,attr,b_status FROM sdb_ome_branch WHERE 1=1 ". $sqlstr . "  limit " . $page_no . "," . $page_size;
            $dataList = $branchObj->db->select($sql);
           
        }else{
            $dataList = [];

        }
        $branch_arr = $branchObj->getList('branch_id,branch_bn,name', $filter, $page_no, $page_size);
        
        return array('lists' => $dataList,'count' => $countList['_count']);;
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params,&$code,&$sub_msg){
        
    }
    #按仓库，货位整理(注意：需要事先在OMS系统中事先添加货位)
    /**
     * position
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function position($params,&$code,&$sub_msg){
        #一次读取所有货位，下面程序需要检测货位是否存在
        $sql = "select  branch.branch_id, pos_id,store_position  from sdb_ome_branch_pos pos  left join sdb_ome_branch branch on pos.branch_id=branch.branch_id  where branch.branch_bn='".$params['branch_bn']."'";
        $_pos_info   =  kernel::database()->select($sql);
        if(empty($_pos_info)){
            $sub_msg = '仓库暂无货位,请先在OMS添加货位!';
            return false;
        }
        $current_branch_id = $branch_pos_list = null;
        foreach($_pos_info as $v){
            $branch_pos_list[$v['pos_id']] = $v['store_position'];
            if(is_null($current_branch_id)){
                #当前仓库branch_id,一次接口请求,只有一个branch_id
                $current_branch_id = $v['branch_id'];
            }
        }
        $_items = json_decode($params['items'],true);
        if(!$_items){
            $sub_msg = '货位整理明细有误！';
            return false;
        }
        $error_items = $no_exist_position = $pos_products_data = array();
        #检测明细
        foreach($_items as $v){
            $data = $v;
            #格式有误的数据
            if(empty($data['bn']) ||empty($data['position'])){
                $error_items[] = $v;
                break;
            }
            $position = $data['position'];
            $pos_id = array_search($position, $branch_pos_list);
            #货位不存在的数据
            if(!$pos_id){
                $no_exist_position[] =  $position;
                break;
            }
            $pos_products_data[$pos_id][$data['bn']] = $data['bn'];#一个货位，多个货号
        }
        if(!empty($error_items)){
            $sub_msg = '明细格式有误';
            return false;
        }
        if(!empty($no_exist_position)){
            $sub_msg = '货位'.implode('||', $no_exist_position).' 不存在,请先在OMS添加货位!';
            return false;
        }
        unset($_items);
        $Obj_product = app::get('material')->model('basic_material');
        $productPosLib = kernel::single('ome_branch_product_pos');
        $result = $have_no_product = $save_fail = array();
        #数据检测完毕，开始入库
        foreach($pos_products_data as $pos_id=>$product_bns){
            #一个货位，对应多个货品
            $product_infos = $Obj_product->getList('bm_id, material_bn',array('material_bn'=>$product_bns));
            #无货号的,立即退出
            if(!$product_infos){
                $have_no_product = $product_bns;
                break;
            }
            #建立货品和货位的关系
            foreach($product_infos as $product){
                $rs = $productPosLib->create_branch_pos($product['bm_id'], $current_branch_id, $pos_id);
                if(!$rs){
                    $save_fail = $product['material_bn'];
                    #保存失败，立即退出
                    break;
                }
            }
        }
        if($have_no_product){
            $sub_msg = implode(',', $have_no_product).'货号不存在,请先在OMS添加货号!';
            return false;
        }
        if($save_fail){
            $sub_msg  = implode(',', $save_fail).'货位保存失败';
            return false;
        }
        $result['msg'] = '更新成功';
        $result['message'] = 'success';
        return $result;
    }

    # 按仓库获取该仓库的货位详情
    /**
     * 获取Positions
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getPositions($params,&$code,&$sub_msg){
        $branch_bn = $params['branch_bn'];
        $branchObj = app::get('ome')->model('branch');
        $branchPosObj = app::get('ome')->model('branch_pos');

        $branches = $branchObj->getList('branch_bn,name',array('branch_bn'=>$branch_bn));
        # 为空则查询所有仓库
        if(empty($branch_bn)){
            $branches = $branchObj->getList('branch_bn,name',array());
        }

        # 没有查询到该仓库
        if(empty($branches)){
            $sub_msg = "没有该仓库编码信息";
            return false;
        }
        $result = array();

        foreach ($branches as $branch){
            # 关联branch  和 branch_pos
            $_pos_info = $branchPosObj->getPosByBranchBn(array('b.name','b.branch_bn','bp.pos_id'),$branch['branch_bn']);
            # 为空即没有该仓库编号的信息或者改仓库下没有货位
            $res = array();
            $res['branch_name'] = $_pos_info[0]['name'];
            $res['branch_bn'] = $_pos_info[0]['branch_bn'];
            # 为空则货位为空
            if(empty($_pos_info)){
                $res['branch_name'] = $branch['name'];
                $res['branch_bn'] = $branch['branch_bn'];
                $res['store_position'] = array();
            }else{
                foreach ($_pos_info as $_pos_info_item){
                    $res['store_position'][] = $this->getPositionItem($_pos_info_item['pos_id']);
                }
            }
            $result[] = $res;
        }

        return $result;

    }

    # 根绝货位id货物该货位的详情
    /**
     * 获取PositionItem
     * @param mixed $pos_id ID
     * @return mixed 返回结果
     */
    public function getPositionItem($pos_id){
        $branchPosObj = app::get('ome')->model('branch_pos');
        $branchProductPosObj = app::get('ome')->model('branch_product_pos');

        # 关联 branch_pos 和 branch
        $_pos_info = $branchPosObj->getDataByPosId(array('b.branch_id','b.branch_bn','b.name','bp.pos_id','bp.store_position'),$pos_id);
        # 关联 branch_product_pos 和 products
        $_pps_info = $branchProductPosObj->getProductsByPosId(array('p.bn'),$pos_id);

        # 进行货位字段组合
        $result = array();
        $result['name'] = $_pos_info[0]['store_position'];
        $result['products'] = $_pps_info;
        return $result;
    }
}