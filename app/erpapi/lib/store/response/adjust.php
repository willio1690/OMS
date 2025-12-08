<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_adjust extends erpapi_store_response_abstract
{
    

    /**
     * 
     * @param  $params [参数] method store.adjust.add
     * @return array
     */
    public function add($params){
        $this->__apilog['title']       = $this->__channelObj->store['name'].'库存调整单';
        $this->__apilog['original_bn'] = $params['task_bn'];

    
        if (!$params['task_bn']) {
            $sub_msg = '缺少任务单号';
         
            $this->__apilog['result']['msg'] = $sub_msg;
            return false;
        }
        $branchMdl = app::get('ome')->model('branch');


        $branchs = $branchMdl->db_dump(array('branch_bn' => $params['branch_bn'], 'check_permission' => 'false'), 'branch_id,name,branch_bn,b_type,is_ctrl_store');
        if (!$branchs) {
            $this->__apilog['result']['msg'] = sprintf('仓库[%s]：未维护', $params['branch_bn']);
            return false;
        }
    
        //是否管控库存
        if ($branchs && $branchs['is_ctrl_store'] != '1') {
            $this->__apilog['result']['msg'] = sprintf('仓库[%s]：不管控库存', $params['branch_bn']);
            return false;
        }
        
        $adjustMdl = app::get('console')->model('adjust');
        if($adjustMdl->db_dump(['adjust_bn'=>$params['task_bn']], 'id')) {
            $sub_msg = '单据已存在';
            $this->__apilog['result']['msg'] = $sub_msg;
            return false;
        }
        $params['task_type'] = 'tz';
        $data = [];
        $data['source'] = 'store api';
        $data['adjust_channel'] = 'storeadjust';
        $data['adjust_bn'] = $params['task_bn'];
        $data['adjust_type'] = $params['task_type'];
        $data['is_check'] = '1';
        $data['source'] = 'store';
        $data['create_time'] = $params['create_time'];
        $data['check_auto'] = $params['check_auto'];
        $data['application_name'] = $params['application_name'];
        if($data['check_auto'] == '1'){
            $data['is_check'] = '0';
            $data['iso_status'] = 'confirm';
        }
        $data['branch_id'] = $branchs['branch_id'];
        $data['negative_branch_id'] = $branchs['branch_id'];
        if(empty($data['branch_id'])) {
            $sub_msg = '没有仓库';
            $this->__apilog['result']['msg'] = $sub_msg;
            return false;
        }

        $productMdl = app::get('ome')->model('branch_product');
      
        $data['memo'] = trim($params['memo']);
        $data['adjust_mode'] = '';
        if($data['memo'] == '库存初始化'){
            $data['adjust_bill_type'] = $data['memo'];
            $data['adjust_mode'] = 'ql';
        }
        $items = json_decode($params['items'], 1);
        $data['items'] = [];
        foreach ($items as $k=>$val) {
            if($val['nums'] == 0 && $data['adjust_mode']!='ql') {
                $sub_msg = '数量不能为0';
                $this->__apilog['result']['msg'] = $sub_msg;
                return false;
            }

            if($val['barcode'] && empty($val['bn'])){
                $bn = kernel::single('material_codebase')->getBnBybarcode($val['barcode']);
                $items[$k]['bn'] = $bn;
                $val['bn'] = $bn;
                if(empty($bn)){
                    $this->__apilog['result']['msg'] = sprintf('行明细[%s]：条码不存在', $val['barcode']);
                    return false;
                }
            }
            $i = app::get('material')->model('basic_material')->db_dump(['material_bn'=>$val['bn']], 'bm_id,material_bn');
            if (empty($i)) {
                $sub_msg = $val['bn'].':编码不存在';
                $this->__apilog['result']['msg'] = $sub_msg;
                return false;
            }
            $product_store = $productMdl->db_dump(array(
            'branch_id' => $data['branch_id'],
            'product_id'     => $i['bm_id']),'*');
            if(!$product_store && $val['nums']<0){
                $sub_msg = $val['bn'].':库存没有出入库记录,不可调账数量小于0';
                $this->__apilog['result']['msg'] = $sub_msg;
                return false;
            }
            $data['items'][$i['bm_id']] = $val['nums'];
        }
        
        return $data;

    }

    
}

?>