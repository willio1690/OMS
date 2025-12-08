<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/11/23 15:40:10
 * @describe: model层
 * ============================
 */
class console_mdl_material_package extends dbeav_model {

    private $templateColumn = array(
        '加工单名称' => 'mp_name',
        '仓库编码' => 'branch_bn',
        '单据备注' => 'memo',
        'MOVEMENT CODE' => 'movement_code',
        '礼盒物料编码' => 'bm_bn',
        '数量' => 'number',
    );

    /**
     * 获取TemplateColumn
     * @return mixed 返回结果
     */

    public function getTemplateColumn() {
        return array_keys($this->templateColumn);
    }
    /**
     * prepared_import_csv
     * @return mixed 返回值
     */
    public function prepared_import_csv(){
        $this->import_data =[];
        $this->import_data_bm_bn =[];
        $this->ioObj->cacheTime = time();
    }

    /**
     * prepared_import_csv_row
     * @param mixed $row row
     * @param mixed $title title
     * @param mixed $tmpl tmpl
     * @param mixed $mark mark
     * @param mixed $newObjFlag newObjFlag
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        if(empty($row) || empty(array_filter($row))) return false;
        if( $row[0] == '加工单名称' ){
            $this->nums = 1;
            $title = array_flip($row);
            foreach($this->templateColumn as $k => $val) {
                if(!isset($title[$k])) {
                    $msg['error'] = '请使用正确的模板';
                    return false;
                }
            }
            return false;
        }
        $this->nums++;
        if (empty($title)) {
            $msg['error'] = "请使用正确的模板格式！";
            return false;
        }
        $arrRequired = ['bm_bn','number'];
        if(!$this->import_data['main']) {
            $arrRequired = ['bm_bn','number','mp_name','branch_bn','movement_code'];
        }
        $arrData = array();
        foreach($this->templateColumn as $k => $val) {
            $arrData[$val] = trim($row[$title[$k]]);
            if(in_array($val, $arrRequired) && empty($arrData[$val])) {
                $msg['warning'][] = 'Line '.$this->nums.'：'.$k.'不能都为空！';
                return false;
            }
        }
        if($this->nums > 10000){
            $msg['error'] = "导入的数据量过大，请减少到10000条以下！";
            return false;
        }
        
        if(!$this->import_data['main']) {
            $main = [];
            $main['mp_name'] = $arrData['mp_name'];
            if($main['mp_name']) {
                if($this->db_dump(['mp_name'=>$main['mp_name']], 'id')) {
                    $msg['error'] = '加工单重复';
                    return false;
                }
            }
            $branch = app::get('ome')->model('branch')->db_dump(['branch_bn'=>$arrData['branch_bn']], 'branch_id');
            $main['branch_id'] = $branch['branch_id'];
            $main['memo'] = $arrData['memo'];
            $main['movement_code'] = $arrData['movement_code'];
            $this->import_data['main'] = $main;
        }
        if(in_array($arrData['bm_bn'], $this->import_data_bm_bn)) {
            $msg['error'] = 'Line '.$this->nums.'：'.$arrData['bm_bn'].' 基础物料重复';
            return false;
        }
        $bm = app::get('material')->model('basic_material')->db_dump(['material_bn'=>$arrData['bm_bn'], 'type'=>'4'], 'bm_id,material_name');
        if(empty($bm)) {
            $msg['error'] = 'Line '.$this->nums.'：'.$arrData['bm_bn'].' 礼盒物料不存在';
            return false;
        }
        $this->import_data_bm_bn[] = $arrData['bm_bn'];
        $item = [
            'bm_id' => $bm['bm_id'],
            'bm_bn' => $arrData['bm_bn'],
            'bm_name' => $bm['material_name'],
            'number' => $arrData['number'],
        ];
        $this->import_data['items'][] = $item;
        $mark = 'contents';
        return true;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    /**
     * finish_import_csv
     * @return mixed 返回值
     */
    public function finish_import_csv(){
        if(empty($this->import_data)) {
            return null;
        }
        $data = $this->import_data['main'];
        $items = $this->import_data['items'];
        $this->insertDataItems($data, $items, '导入');
    }

    /**
     * insertDataItems
     * @param mixed $data 数据
     * @param mixed $items items
     * @param mixed $source source
     * @return mixed 返回值
     */
    public function insertDataItems($data, $items, $source) {
        if(empty($data) || empty($items)) {
            return [false, '数据不全'];
        }
        $this->db->beginTransaction();
        $data['mp_bn'] = $this->gen_id();
        $this->insert($data);
        if(!$data['id']) {
            $this->db->rollBack();
            return [false, ['msg'=>'写入失败']];
        }
        app::get('ome')->model('operation_log')->write_log('material_package@console',$data['id'],"订单".$source."新建");
        foreach ($items as $k => $value) {
            $items[$k]['mp_id'] = $data['id'];
        }
        $itemObj = app::get('console')->model('material_package_items');
        $sql = ome_func::get_insert_sql($itemObj, $items);
        $this->db->exec($sql);
        $items = $itemObj->getList('*', ['mp_id'=>$data['id']]);
        $bmIds = array_column($items, 'bm_id');
        $basicMaterialCombinationItemsObj = app::get('material')->model('basic_material_combination_items');
        $bmsRows = $basicMaterialCombinationItemsObj->getList('pbm_id,bm_id,material_bn,material_name,material_num', array('pbm_id' => $bmIds), 0, -1);
        $bms = [];
        foreach ($bmsRows as $v) {
            $bms[$v['pbm_id']][] = $v;
        }
        $storeManage = kernel::single('ome_store_manage');
        $storeManage->loadBranch(array('branch_id'=>$data['branch_id']));
        $mpid = [];
        $storeLessMsg = '';
        foreach ($items as $v) {
            if(empty($bms[$v['bm_id']])) {
                $this->db->rollBack();
                return [false, ['msg'=>'缺少明细']];
            }
            if($data['service_type'] == '2') {
                $validNum  = $storeManage->processBranchStore(
                    array(
                        'node_type' =>'getAvailableStore',
                        'params'    =>array(
                            'branch_id' =>$data['branch_id'],
                            'product_id'=>$v['bm_id'],
                        ),
                    ), $err_msg);
                if($v['number'] > $validNum) {
                    $storeLessMsg .= $v['bm_bn'].'库存不足;';
                }
            }
            foreach ($bms[$v['bm_id']] as $vv) {
                $number = bcmul($v['number'], $vv['material_num']);
                $mpid[] = [
                    'mp_id' => $v['mp_id'],
                    'mpi_id' => $v['id'],
                    'bm_id' => $vv['bm_id'],
                    'bm_bn' => $vv['material_bn'],
                    'bm_name' => $vv['material_name'],
                    'number' => $number,
                ];
                if($data['service_type'] == '1') {
                    $validNum  = $storeManage->processBranchStore(
                        array(
                            'node_type' =>'getAvailableStore',
                            'params'    =>array(
                                'branch_id' =>$data['branch_id'],
                                'product_id'=>$vv['bm_id'],
                            ),
                        ), $err_msg);
                    if($number > $validNum) {
                        $storeLessMsg .= $vv['material_bn'].'库存不足;';
                    }
                }
            }
        }
        if($storeLessMsg) {
            $this->db->rollBack();
            return [false, ['msg'=>$storeLessMsg]];
        }
        $itemDetailObj = app::get('console')->model('material_package_items_detail');
        $sql = ome_func::get_insert_sql($itemDetailObj, $mpid);
        $this->db->exec($sql);
        $this->db->commit();
        return [true, ['msg'=>'操作成功']];
    }

    /**
     * gen_id
     * @return mixed 返回值
     */
    public function gen_id() {
        $prefix = 'MP'.date("ymd");
        $sign   = kernel::single('eccommon_guid')->incId('material_package', $prefix, 6);
        return $sign;
    }

    /**
     * 更新DataItems
     * @param mixed $data 数据
     * @param mixed $items items
     * @param mixed $source source
     * @return mixed 返回值
     */
    public function updateDataItems($data, $items, $source) {
        if(empty($data) || empty($items)) {
            return [false, '数据不全'];
        }
        $this->db->beginTransaction();
        $upData = [
            'mp_name' => $data['mp_name'],
            'branch_id' => $data['branch_id'],
            'movement_code' => $data['movement_code'],
            'memo' => $data['memo'],
        ];
        $rs = $this->update($upData, ['id'=>$data['id']]);
        app::get('ome')->model('operation_log')->write_log('material_package@console',$data['id'],"订单".$source."更新");
        $itemObj = app::get('console')->model('material_package_items');
        $oldItems = $itemObj->getList('*', ['mp_id'=>$data['id']]);
        $bmIds = array_column($items, 'bm_id');
        $oldBmIds = array_column($oldItems, 'bm_id');
        $bmIds = array_merge($bmIds, $oldBmIds);
        $basicMaterialCombinationItemsObj = app::get('material')->model('basic_material_combination_items');
        $bmsRows = $basicMaterialCombinationItemsObj->getList('pbm_id,bm_id,material_bn,material_name,material_num', array('pbm_id' => $bmIds), 0, -1);
        $bms = [];
        foreach ($bmsRows as $v) {
            $bms[$v['pbm_id']][$v['bm_id']] = $v;
        }
        $storeManage = kernel::single('ome_store_manage');
        $storeManage->loadBranch(array('branch_id'=>$data['branch_id']));
        $storeLessMsg = '';
        $itemDetailObj = app::get('console')->model('material_package_items_detail');
        foreach ($oldItems as $value) {
            if($items[$value['bm_id']]) {
                $newItem = $items[$value['bm_id']];
                unset($items[$value['bm_id']]);
                if($newItem['number'] != $value['number']) {
                    $itemObj->update(['number'=>$newItem['number']], ['id'=>$value['id']]);
                    if($data['service_type'] == '2') {
                        $validNum  = $storeManage->processBranchStore(
                            array(
                                'node_type' =>'getAvailableStore',
                                'params'    =>array(
                                    'branch_id' =>$data['branch_id'],
                                    'product_id'=>$newItem['bm_id'],
                                ),
                            ), $err_msg);
                        if($newItem['number'] > $validNum) {
                            $storeLessMsg .= $newItem['bm_bn'].'库存不足;';
                        }
                    }
                    $itemDetail = $itemDetailObj->getList('id, bm_id, bm_bn', ['mpi_id'=>$value['id']]);
                    foreach ($itemDetail as $vv) {
                        $number = bcmul($newItem['number'], $bms[$value['bm_id']][$vv['bm_id']]['material_num']);
                        $itemDetailObj->update(['number' => $number], ['id'=>$vv['id']]);
                        if($data['service_type'] == '1') {  
                            $validNum  = $storeManage->processBranchStore(
                                array(
                                    'node_type' =>'getAvailableStore',
                                    'params'    =>array(
                                        'branch_id' =>$data['branch_id'],
                                        'product_id'=>$vv['bm_id'],
                                    ),
                                ), $err_msg);
                            if($number > $validNum) {
                                $storeLessMsg .= $vv['bm_bn'].'库存不足;';
                            }
                        }
                    }
                }
            } else {
                $itemObj->delete(['id'=>$value['id']]);
                $itemDetailObj->delete(['mpi_id'=>$value['id']]);
            }
        }
        if($storeLessMsg) {
            $this->db->rollBack();
            return [false, ['msg'=>$storeLessMsg]];
        }
        if(empty($items)) {
            $this->db->commit();
            return [true, ['msg'=>'操作成功']];
        }
        $mpid = [];
        foreach ($items as $v) {
            if(empty($bms[$v['bm_id']])) {
                $this->db->rollBack();
                return [false, ['msg'=>'缺少明细']];
            }
            $v['mp_id'] = $data['id'];
            $itemObj->insert($v);
            if($data['service_type'] == '2') {
                $validNum  = $storeManage->processBranchStore(
                    array(
                        'node_type' =>'getAvailableStore',
                        'params'    =>array(
                            'branch_id' =>$data['branch_id'],
                            'product_id'=>$v['bm_id'],
                        ),
                    ), $err_msg);
                if($v['number'] > $validNum) {
                    $storeLessMsg .= $v['bm_bn'].'库存不足;';
                }
            }
            foreach ($bms[$v['bm_id']] as $vv) {
                $number = bcmul($v['number'], $vv['material_num']);
                $mpid[] = [
                    'mp_id' => $v['mp_id'],
                    'mpi_id' => $v['id'],
                    'bm_id' => $vv['bm_id'],
                    'bm_bn' => $vv['material_bn'],
                    'bm_name' => $vv['material_name'],
                    'number' => $number,
                ];
                if($data['service_type'] == '1') {
                    $validNum  = $storeManage->processBranchStore(
                        array(
                            'node_type' =>'getAvailableStore',
                            'params'    =>array(
                                'branch_id' =>$data['branch_id'],
                                'product_id'=>$vv['bm_id'],
                            ),
                        ), $err_msg);
                    if($number > $validNum) {
                        $storeLessMsg .= $vv['material_bn'].'库存不足;';
                    }
                }
            }
        }
        if($storeLessMsg) {
            $this->db->rollBack();
            return [false, ['msg'=>$storeLessMsg]];
        }
        $itemDetailObj = app::get('console')->model('material_package_items_detail');
        $sql = ome_func::get_insert_sql($itemDetailObj, $mpid);
        $this->db->exec($sql);
        $this->db->commit();
        return [true, ['msg'=>'操作成功']];
    }
}