<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_iso extends erpapi_store_response_abstract
{
    

    
    /**
     * cancel
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function cancel($params){
        $this->__apilog['title']       = '出入库单取消';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }
        if (!$params['store_bn']){
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }
        if ($params['store_bn']){
            $branch = $this->getBranchIdByBn($params['store_bn']);
            if (!$branch){
                $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
                return false;
            }
            $branch_id = $branch['branch_id'];
           
        }

        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        $iso    = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn']));
        if (!$iso) {
            $this->__apilog['result']['msg'] = '取消失败：单据号不存在';

            return false;
        }

        if ($iso['iso_status'] != '1') {
            $columns                         = $isoMdl->_columns();
            $this->__apilog['result']['msg'] = '取消失败：' . $columns['iso_status'][$iso['iso_status']];

            return false;
        }

        if (!in_array($iso['branch_id'], (array) $branch_id)) {
            $this->__apilog['result']['msg'] = '取消失败：非本门店出入库单';

            return false;
        }

        $filter = array(
            'iso_id' => $iso['iso_id'],
            'iso_bn' => $iso['iso_bn'],
        );

        return $filter;

    }

    /**
     * 检查
     * @param mixed $params 参数
     * @return mixed 返回验证结果
     */
    public function check($params){

    }

    /**
     * 出入库单出库，method=store.iso.confirm
     * 
     * @return void
     * @author
     * */
    public function confirm($params)
    {


        $this->__apilog['title']       = $this->__channelObj->store['name'].'出入库单出库';
        $this->__apilog['original_bn'] = $params['iso_bn'];

        if (!$params['iso_bn']) {
            $this->__apilog['result']['msg'] = '缺少出入库单号';
            return false;
        }

        if (!$params['store_bn']){
            $this->__apilog['result']['msg'] = '缺少门店编码';
            return false;
        }
       
        if(!$params['branch_bn']){
            $this->__apilog['result']['msg'] = '缺少仓库编码';
            return false;
        }
        $branch = $this->getBranchIdByBn($params['branch_bn']);
        if (!$branch){
            $this->__apilog['result']['msg'] = sprintf('[%s]仓库不存在', $params['branch_bn']);
            return false;
        }
    
        //是否管控库存
        if ($branch && $branch['is_ctrl_store'] != '1') {
            $this->__apilog['result']['msg'] = sprintf('仓库[%s]：不管控库存', $params['branch_bn']);
            return false;
        }
        
        $branch_id = $branch['branch_id'];
           
        
        $isoMdl = app::get('taoguaniostockorder')->model('iso');

        $iso = $isoMdl->db_dump(array('iso_bn' => $params['iso_bn'], 'branch_id' => $branch_id));

        if (!$iso) {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]不存在', $params['iso_bn']);

            return false;
        }
       
        // 更新审核状态
        $io = kernel::single('ome_iostock')->getIoByType($iso['type_id']);
        if ($io == '1' && $iso['check_status'] == '1' && $iso['iso_status'] == '1') {
            $isoMdl->update(array('check_status' => '2'), array('iso_id' => $iso['iso_id']));

            $iso['check_status'] = '2';
        }
        

        if ($iso['iso_status'] == '2' || $iso['iso_status'] == '3') {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]已出库', $iso['iso_bn']);

            return false;
        }

        if ($iso['iso_status'] == '4') {
            $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]已取消', $iso['iso_bn']);

            return false;
        }
        
        $iso['io_status'] = $params['status'] ? $params['status'] : 'FINISH';

        $corp = array();
        // 只有出库的时候才需要呼叫物流
        if ($io == '0') {
            $params['logi_code'] = 'SF';
            if (!$params['logi_code']) {
                $this->__apilog['result']['msg'] = '缺少物流编码';

                return false;
            }

            $corp = app::get('ome')->model('dly_corp')->db_dump(array('type' => $params['logi_code'], 'tmpl_type' => 'electron'), 'corp_id,name,tmpl_type,channel_id,type');
            if (!$corp) {
                //$this->__apilog['result']['msg'] = '配货失败：物流公司编码不存在';

                //return false;
            }

            $iso['corp'] = $corp;

        }

        if (!$params['items']) {

            $this->__apilog['result']['msg'] = '出入库明细不可为空';

            return false;
        }

        $params['items'] = $params['items'] ? @json_decode($params['items'], true) : array();

        $items = array();
        foreach ($params['items'] as $key => $value) {
            if ($value['nums'] < 0 || !is_numeric($value['nums'])) {
                $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]库存非法[%s]', $value['bn'], $value['nums']);
                return false;
            }

            if($value['barcode'] && empty($value['bn'])){
                $bn = kernel::single('material_codebase')->getBnBybarcode($value['barcode']);
                $params['items'][$key]['bn'] = $bn;
                $value['bn'] = $bn;
                if(empty($bn)){
                    $this->__apilog['result']['msg'] = sprintf('行明细[%s]：条码不存在', $key);
                    return false;
                }
            }

            $items[$value['bn']] = array(
                'bn'         => $value['bn'],
                'normal_num' => $value['nums'],
            );
         
            if($value['batch_code']){
                $items[$value['bn']]['package_code'] = $value['batch_code'];
            }
            if($value['sn_list']['sn']) {

                $sn_list = json_decode($value['sn_list']['sn'],true);

               
                $items[$value['bn']]['sn_list'] = $sn_list;
                
            }
        }

       
        if ($items) {
            $items = array_column($items, null, 'bn');
        }

        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');
        $item_list  = $isoItemMdl->getList('*', array('iso_id' => $iso['iso_id']));

        $bpModel       = app::get('ome')->model('branch_product');
        $product_store =$bpModel->getList('product_id,branch_id,store,store_freeze', array('product_id' => array_column($item_list, 'product_id'), 'branch_id' => $iso['branch_id']));
        
        $product_store = array_column($product_store, null, 'product_id');

        // 如果传了明细，判断库存
        if ($items) {
            foreach ($item_list as $key => $value) {
                $nums = $value['nums'] - $value['normal_num'] - $value['defective_num'];

                if ($io == '0' && $nums < $items[$value['bn']]['normal_num']) {
                    $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]出入库数量不允许大于申请数量', $value['bn']);
                    return false;
                }

                // 判断是否超出库存
                if ($io == '0'
                    && $items[$value['bn']]
                    && $product_store[$value['product_id']]['store'] < $items[$value['bn']]['normal_num']
                ) {
                    $this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]出库数不允许大于系统库存数', $value['bn']);
                    return false;
                }

                if ($items[$value['bn']]['normal_num'] > $value['nums']) {
                    //$this->__apilog['result']['msg'] = sprintf('出入库失败：[%s]出入库数量不允许大于申请数量', $value['bn']);
                    //return false;
                }
            }

            $iso['items'] = $items;
        }

        return $iso;
    }

   

        /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){
        $this->__apilog['title']       =  '收货单查询接口';
        $this->__apilog['original_bn'] = $this->__channelObj->store['server_bn'];

        if(empty($params['task_bn']) && empty($params['start_time']) && empty($params['end_time'])){

            $this->__apilog['result']['msg'] = '查询条件里任务号或者时间至少有一个不为空!';

            return false;
        }

        if ($params['start_time'] &&  !strtotime($params['start_time'])) {
            $this->__apilog['result']['msg'] = '开始时间格式不正确';

            return false;
        }

        if ($params['end_time'] && !strtotime($params['end_time'])) {
            $this->__apilog['result']['msg'] = '结束时间格式不正确';

            return false;
        }

        if(empty($params['bill_type'])){

            $this->__apilog['result']['msg'] = '业务类型不能为空';
            return false;
        }

        if ($params['page_size'] <= 0 || $params['page_size'] > 100) {
            $this->__apilog['result']['msg'] = '每页数量必须大于0小于等于100';
            return false;
        }

        if ($params['page_no'] <= 0) {
            $this->__apilog['result']['msg'] = '页码必须大于0';
            return false;
        }

        $iso_status = $params['iso_status'];
        $task_bn = $params['task_bn'];

        if($task_bn){
            $filter = [
           
                'business_bn'   =>  $task_bn,
            ];
        }else{
            $filter = [
                'create_time|between' => [
                    strtotime($params['start_time']),
                    strtotime($params['end_time']),
                ],
            
            ];
        }
        
        if (!$params['store_bn']){
            //$this->__apilog['result']['msg'] = '缺少门店编码';
            //return false;
        }
        if ($params['store_bn']){
            $branch = $this->getBranchIdByBn($params['store_bn']);
            if (!$branch){
                $this->__apilog['result']['msg'] = sprintf('[%s]门店不存在', $params['store_bn']);
                return false;
            }
            $branch_id = $branch['branch_id'];
           
        }

        if($branch_id){
            $filter['branch_id'] = $branch_id;
        }
        
        //bill_type
        $filter['type_id'] = 4;
        if($params['bill_type']){
            $filter['bill_type'] = $params['bill_type'];
        }
        
        if($iso_status){
            $filter['iso_status'] = $iso_status;
            
        }
        //$bill_type = '';
        $limit  = $params['page_size'];
        $offset = ($params['page_no'] - 1) * $limit;
       
        return ['filter' => $filter, 'limit' => $limit, 'offset' => $offset];

    }
   
}

?>