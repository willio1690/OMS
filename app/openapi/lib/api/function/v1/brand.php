<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_brand extends openapi_api_function_abstract implements openapi_api_function_interface
{
    /**
     * openapi获取品牌列表
     * 
     * @param $params
     * @param $code
     * @param $sub_msg
     * @return array
     * author : Joe
     * Date : 2022-02-24 14:25
     */
    public function getList($params, &$code, &$sub_msg)
    {
        $brand_code = $sqlstr = '';
        if($params['brand_code']){
            $brand_code = $params['brand_code'];
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }

        $sqlstr .= " AND disabled='false' ";

        #按仓库编号
        if($brand_code){
            $brand_code = str_replace('，',',',$brand_code);
            $arr_brand_code = explode(',',$brand_code);
            if(count($arr_brand_code) > 1){
                $sqlstr .= " and brand_code in ("."'".join("','",$arr_brand_code)."'".")";
            }else{
                $sqlstr .= " AND brand_code='". $brand_code ."'";
            }
        }

        $brandObj = app::get('ome')->model('brand');
        $sql = "SELECT count(*) as _count FROM sdb_ome_brand WHERE  1=1 ". $sqlstr;
        $countList = $brandObj->db->selectrow($sql);

        if (intval($countList['_count']) > 0) {
            $sql = "SELECT brand_code,brand_name,brand_keywords,brand_url,brand_desc,brand_logo FROM sdb_ome_brand WHERE 1=1 ". $sqlstr . "  limit " . $offset . "," . $limit;
            $dataList = $brandObj->db->select($sql);

        }else{
            $dataList = [];

        }

        return array('lists' => $dataList,'count' => $countList['_count']);

    }

    /**
     * 添加
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function add($params, &$code, &$sub_msg)
    {

    }
}