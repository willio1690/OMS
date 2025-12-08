<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_warehouse extends erpapi_store_response_abstract
{
   
    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){
       
        $this->__apilog['title']       = $this->__channelObj->store['name'].'大仓库存列表查询';
        $this->__apilog['original_bn'] = '';
        if (isset($params['page_no']) && !is_numeric($params['page_no'])) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误', $params['page_no']);

            return false;
        }

        $page_no = $params['page_no'] && $params['page_no'] > 0 ? $params['page_no'] : 1;

        if (isset($params['page_size']) && $params['page_size'] > self::MAX_LIMIT) {
            $this->__apilog['result']['msg'] = sprintf('[%s]参数错误，最大允许[%s]', $params['page_size'], self::MAX_LIMIT);

            return false;
        }

        if ($params['bn']=='' && $params['branch_bn']=='') {
            $this->__apilog['result']['msg'] = '货号或仓库编码必须有一个不为空';

            return false;
        }
        $branchMdl = app::get('ome')->model('branch');

        $branchs = $branchMdl->db_dump(array('branch_bn'=>$params['branch_bn'], 'check_permission'=> 'false'), 'branch_id,name,branch_bn,b_type,type');

        if(!$branchs){
            $this->__apilog['result']['msg'] = $params['branch_bn'].'仓库不存在';

            return false;
        }
        $limit = $params['page_size'] ? $params['page_size'] : self::MAX_LIMIT;

        $filter = array(
            'offset'    => ($page_no - 1) * $limit,
            'limit'     => $limit,
            'branch_id' => $branchs['branch_id'],
            'is_store_sale'=>$params['is_store_sale'],
        );
        $bm_ids = array();
        if($params['barcode']){
            $codeObj = app::get('material')->model('codebase');
            $codes = $codeObj->dump(array('code' => $params['barcode']), 'bm_id');
            
            if($codes){
                $bm_ids = $codes['bm_id'];
            }
        }
        if ($params['bn'] ||  $params['barcode']) {
            $mfilter = array();
            if ($params['bn']){
                $mfilter['material_bn'] = explode(',',$params['bn']);
            }
            
            if($bm_ids){
                $mfilter['bm_id'] = $bm_ids;
            }
            

            $material = kernel::single('material_basic_select')->getlist_ext('bm_id', $mfilter);

            $filter['product_id'] = $material ? array_map('current',$material) : 0;
        }

        return $filter;

    }
}

?>