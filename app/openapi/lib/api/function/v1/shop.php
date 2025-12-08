<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_shop extends openapi_api_function_abstract implements openapi_api_function_interface
{
    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params, &$code, &$sub_msg){
        $shop_bn = $sqlstr = '';
        if($params['shop_code']){
            $shop_bn = $params['shop_code'];
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 1000 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }

        $sqlstr .= " AND disabled='false' and s_type='1' ";
        $shoptype = ome_shop_type::get_shop_type();
        #按店铺编号
        if($shop_bn){
            $shop_bn = str_replace('，',',',$shop_bn);
            $arr_shop_bn = explode(',',$shop_bn);
            if(count($arr_shop_bn) > 1){
                $sqlstr .= " and shop_bn in ("."'".join("','",$arr_shop_bn)."'".")";
            }else{
                $sqlstr .= " AND shop_bn='". $shop_bn ."'";
            }
        }

        $shopObj = app::get('ome')->model('shop');
        $sql = "SELECT count(*) as _count FROM sdb_ome_shop WHERE  1=1 ". $sqlstr;
        $countList = $shopObj->db->selectrow($sql);

        if (intval($countList['_count']) > 0) {
            $sql = "SELECT shop_bn as shop_code,name as shop_name,shop_type,active,area,zip,addr,mobile,tel,default_sender,shop_id FROM sdb_ome_shop WHERE 1=1 ". $sqlstr . "  limit " . $offset . "," . $limit;
            $dataList = $shopObj->db->select($sql);
            foreach($dataList as &$data){
                kernel::single('ome_func')->split_area($data['area']);
                $data['province'] = $data['area'][0];
                $data['city'] = $data['area'][1];
                $data['district'] = $data['area'][2];
                $data['shop_type_name'] = $shoptype[$data['shop_type']];
                unset($data['area']);

                //
                $shop_id = $data['shop_id'];
                unset($data['shop_id']);
                $props = array();

                $props = kernel::single('ome_shop')->getPropsList($shop_id);
                if($props){
                    $data['props'] = $props;
                }
            }

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
        return kernel::single('openapi_data_original_shop')->add($params,$code,$sub_msg);
    }
}