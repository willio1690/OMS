<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料保质期明细生成Lib
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class material_receipt_storagelife{

    /**
     *
     * 保质期明细生成方法
     * @param array $sdf 批次信息
     * bill_type follow siso_receipt_iostock_abstract iosotck_types,例如:1-采购入库
     * bill_io_type 1-入库 2-出库
     */
    public function generate($sdf,&$msg){
        //校验传入参数
        if(!$this->checkParams($sdf,$msg)){
            return false;
        }

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $codebaseObj = app::get('material')->model('codebase');
        $operationLogObj  = app::get('ome')->model('operation_log');

        //事务开启
        $basicMaterialStorageLifeObj->db->beginTransaction();

        //循环每行批次明细进行保存
        foreach($sdf as $row){
            $data = array();

            $data['bm_id']  = $row['bm_id'];
            $data['material_bn']  = $row['material_bn'];
            $data['material_bn_crc32'] = sprintf('%u',crc32($row['material_bn']));
            $data['expire_bn']  = $row['expire_bn'];
            $data['production_date']  = strtotime($row['production_date']);
            $data['guarantee_period']  = $row['guarantee_period'];

            switch($row['date_type']){
                case 'day':
                    $data['expiring_date']  = strtotime('+'.$row['guarantee_period'].' days',$data['production_date'])+86399;
                    $data['date_type'] = 1;
                    break;
                case 'month':
                    $data['expiring_date']  = strtotime('+'.$row['guarantee_period'].' months',$data['production_date'])+86399;
                    $data['date_type'] = 2;
                    break;
                case 'year':
                    $data['expiring_date']  = strtotime('+'.$row['guarantee_period'].' years',$data['production_date'])+86399;
                    $data['date_type'] = 3;
                    break;
                case 'date':
                    $data['guarantee_period'] = (strtotime($row['expiring_date']) - $data['production_date'])/86400;
                    $data['expiring_date']  = strtotime($row['expiring_date'])+86399;
                    $data['date_type'] = 1;
                    break;
                case 'change':
                    $data['expiring_date']       = $row['expiring_date'];
                    $data['date_type']           = ($data['set_date_type'] ? $data['set_date_type'] : 1);
                    break;
            }

            $data['warn_day']  = $row['warn_day'];
            $data['warn_date']  = $data['expiring_date'] - $row['warn_day']*86400;
            $data['quit_day']  = $row['quit_day'];
            $data['quit_date']  = $data['expiring_date'] - $row['quit_day']*86400;
            $data['in_num']  = $row['in_num'];
            $data['balance_num']  = $row['in_num'];
            $data['branch_id']  = $row['branch_id'];

            //保质期批次号已存在_则更新
            $storageLifeInfo = $basicMaterialStorageLifeObj->dump(array('branch_id'=>$row['branch_id'], 'bm_id'=>$row['bm_id'], 'expire_bn'=>$row['expire_bn']),'*');
            if($storageLifeInfo)
            {
                $update['difference_num']    = $row['in_num'];#累加数量
                $update['bill_id']           = $row['bill_id'];
                $update['bill_bn']           = $row['bill_bn'];
                $update['bill_type']         = $row['bill_type'];
                $update['bill_io_type']      = $row['bill_io_type'];

                $update_num = "in_num=in_num+".$update['difference_num'].",balance_num=balance_num+".$update['difference_num'];

                $sql = 'UPDATE sdb_material_basic_material_storage_life SET '.$update_num.' WHERE bmsl_id='.$storageLifeInfo['bmsl_id'];
                if(!$basicMaterialStorageLifeObj->db->exec($sql)){
                    $basicMaterialStorageLifeObj->db->rollBack();
                    return false;
                }else{
                    //生成相应的采购入库流水单据信息
                    $tmp_bill_data = array(
                        'bmsl_id' => $storageLifeInfo['bmsl_id'],
                        'bm_id'  => $storageLifeInfo['bm_id'],
                        'material_bn'  => $storageLifeInfo['material_bn'],
                        'material_bn_crc32' => $storageLifeInfo['material_bn_crc32'],
                        'expire_bn'  => $storageLifeInfo['expire_bn'],
                        'nums'  => $update['difference_num'],
                        'branch_id' => $storageLifeInfo['branch_id'],
                        'bill_id'  => $update['bill_id'],
                        'bill_bn'  => $update['bill_bn'],
                        'bill_type' => $update['bill_type'],
                        'bill_io_type' => $update['bill_io_type'],
                    );

                    if(!$basicMaterialStorageLifeBillsObj->save($tmp_bill_data)){
                        $basicMaterialStorageLifeObj->db->rollBack();
                        return false;
                    }
                    unset($tmp_bill_data);
                }

                $iostock_types = kernel::single('siso_receipt_iostock')->get_iostock_types();
                $operation_msg = isset($iostock_types[$update['bill_type']]) ? $iostock_types[$update['bill_type']]['info'] : '入库';

                //记录日志
                $operationLogObj->write_log('storage_life_chg@wms', $storageLifeInfo['bmsl_id'], "物料保质期批次变更,关联单据号:".$update['bill_bn'].",".$operation_msg.":".$update['difference_num']);

            }else{
                //新增保质期处理逻辑
                if(!$basicMaterialStorageLifeObj->save($data)){
                    $basicMaterialStorageLifeObj->db->rollBack();
                    return false;
                }else{
                    //生成相应的采购入库流水单据信息
                    $tmp_bill_data = array(
                        'bmsl_id' => $data['bmsl_id'],
                        'bm_id'  => $data['bm_id'],
                        'material_bn'  => $data['material_bn'],
                        'material_bn_crc32' => $data['material_bn_crc32'],
                        'expire_bn'  => $data['expire_bn'],
                        'nums'  => $data['in_num'],
                        'branch_id' => $data['branch_id'],
                        'bill_id'  => $row['bill_id'],
                        'bill_bn'  => $row['bill_bn'],
                        'bill_type' => $row['bill_type'],
                        'bill_io_type' => $row['bill_io_type'],
                    );

                    if(!$basicMaterialStorageLifeBillsObj->save($tmp_bill_data)){
                        $basicMaterialStorageLifeObj->db->rollBack();
                        return false;
                    }
                    unset($tmp_bill_data);

                    //码表存入对应物料批次码
                    $code_type = material_codebase::getStorageListType();
                    $codeInfo    = $codebaseObj->dump(array('code'=>$data['expire_bn'], 'type'=>$code_type), '*');
                    if(empty($codeInfo)){
                        $tmp_code = array(
                            'bm_id' => $data['bm_id'],
                            'type' => $code_type,
                            'code' => $data['expire_bn'],
                        );

                        if(!$codebaseObj->insert($tmp_code)){
                            $msg[] = '批次码已被占用:'.$data['expire_bn'];
                            $basicMaterialStorageLifeObj->db->rollBack();
                            return false;
                        }
                    }else{
                        if($codeInfo['bm_id'] !=$data['bm_id']){
                            $msg[] = '批次码已被占用:'.$data['expire_bn'];
                            $basicMaterialStorageLifeObj->db->rollBack();
                            return false;
                        }
                    }
                }

                //记录日志
                $operationLogObj->write_log('storage_life_add@wms', $data['bmsl_id'], "物料保质期批次新增,关联单据号".$row['bill_bn']."");
            }
        }

        $basicMaterialStorageLifeObj->db->commit();
        return true;
    }

    /**
     * 批次明细参数校验
     * @param array $params 批次明细参数信息
     * @param array $msg 批次明细校验错误信息
     */
    private function checkParams($params,&$msg){
        $msg = is_array($msg) ? $msg : array();
        $mustHas = array('material_bn','bm_id','expire_bn','production_date','guarantee_period','warn_day','quit_day','in_num','branch_id','bill_id','bill_bn','bill_type','bill_io_type');
        if($params){
            foreach($params as $key=>$row){
                $arrI = array_keys($row);
                if(count(array_diff($mustHas,$arrI)) > 0){
                    $msg[] ='行' . $key .'-' . '必填字段不全';
                }else{
                    foreach($row as $field=>$value){
                        if(in_array($field,$mustHas)){
                            if(is_null($value) || $value === ''){
                                $msg[] = '行:'.$key .'-字段:'. $field.'-不能为空';
                            }
                        }

                        switch ($field){
                            case 'in_num':
                                if(is_numeric($value) && $value>0){
                                    //do nothing
                                } else{
                                    $msg[] = '行:'.$key .'-字段:'. $field.'-非数字类型';
                                }
                                break;
                        }
                    }
                }
            }

            if(count($msg)>0){
                return false;
            }
        }else{
            $msg[] = '没有传入参数';
            return false;
        }

        return true;
    }

    /**
     *
     * 保质期明细生成方法
     * @param array $data 批次信息
     */
    public function update($data,&$msg){
        //校验传入参数
        if(!$this->checkUpdateParams($data,$msg)){
            return false;
        }

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $operationLogObj  = app::get('ome')->model('operation_log');

        //事务开启
        $basicMaterialStorageLifeObj->db->beginTransaction();

        foreach($data as $row){
            $storageLifeInfo = $basicMaterialStorageLifeObj->dump(array('branch_id'=>$row['branch_id'], 'bm_id'=>$row['bm_id'], 'expire_bn'=>$row['expire_bn']),'*');
            if($storageLifeInfo){
                if($row['bill_io_type'] == 1){
                    $update_num = "in_num=in_num+".$row['difference_num'].",balance_num=balance_num+".$row['difference_num'];
                }else{
                    $update_num = "out_num=out_num+".$row['difference_num'].",balance_num=balance_num-".$row['difference_num'];
                }

                $sql = 'UPDATE sdb_material_basic_material_storage_life SET '.$update_num.' WHERE bmsl_id='.$storageLifeInfo['bmsl_id'];
                if(!$basicMaterialStorageLifeObj->db->exec($sql)){
                    $basicMaterialStorageLifeObj->db->rollBack();
                    return false;
                }else{
                    //生成相应的采购入库流水单据信息
                    $tmp_bill_data = array(
                        'bmsl_id' => $storageLifeInfo['bmsl_id'],
                        'bm_id'  => $row['bm_id'],
                        'material_bn'  => $storageLifeInfo['material_bn'],
                        'material_bn_crc32' => $storageLifeInfo['material_bn_crc32'],
                        'expire_bn'  => $row['expire_bn'],
                        'nums'  => $row['difference_num'],
                        'branch_id' => $row['branch_id'],
                        'bill_id'  => $row['bill_id'],
                        'bill_bn'  => $row['bill_bn'],
                        'bill_type' => $row['bill_type'],
                        'bill_io_type' => $row['bill_io_type'],
                    );

                    if(!$basicMaterialStorageLifeBillsObj->save($tmp_bill_data)){
                        $basicMaterialStorageLifeObj->db->rollBack();
                        return false;
                    }
                    unset($tmp_bill_data);
                }

                //记录日志
                $event_type = $row['bill_io_type'] == 1 ? '入库' : '出库';
                $operationLogObj->write_log('storage_life_chg@wms', $storageLifeInfo['bmsl_id'], "物料保质期批次变更,关联单据号:".$row['bill_bn'].",".$event_type.":".$row['difference_num']);
            }else{
                $msg[] = '批次信息不存在:'.$row['expire_bn'];
                $basicMaterialStorageLifeObj->db->rollBack();
                return false;
            }
        }

        $basicMaterialStorageLifeObj->db->commit();
        return true;
    }

    /**
     * 更新时批次明细参数校验
     * @param array $params 批次明细参数信息
     * @param string $msg 批次明细校验错误信息
     */
    private function checkUpdateParams($params,&$msg){
        $mustHas = array('branch_id','bm_id','expire_bn','difference_num','bill_id','bill_bn','bill_type','bill_io_type');
        if($params){
            foreach($params as $key=>$row){
                $arrI = array_keys($row);
                if(count(array_diff($mustHas,$arrI)) > 0){
                    $msg[] ='行' . $key .'-' . '必填字段不全';
                }else{
                    foreach($row as $field=>$value){
                        if(in_array($field,$mustHas)){
                            if(is_null($value) || $value === ''){
                                $msg[] = '行:'.$key .'-字段:'. $field.'-不能为空';
                            }
                        }

                        switch ($field){
                            case 'difference_num':
                                if(is_numeric($value) && $value>0){
                                    //do nothing
                                } else{
                                    $msg[] = '行:'.$key .'-字段:'. $field.'-非数字类型';
                                }
                                break;
                        }
                    }
                }
            }

            if(count($msg)>0){
                return false;
            }
        }else{
            $msg[] = '没有传入参数';
            return false;
        }

        return true;
    }

    /**
     *
     * 预占保质期明细物料数量
     * @param array $data 批次信息
     */
    public function freeze(&$data,&$msg){
        //校验传入参数
        if(!$this->checkFreezeParams($data,$msg)){
            return false;
        }

        $nowTime = time();

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $operationLogObj  = app::get('ome')->model('operation_log');

        foreach($data['items'] as $k => $item){
            $now_bm_num = $item['num'];
            $is_end = false;

            while($now_bm_num > 0){
                $sql = 'SELECT * FROM sdb_material_basic_material_storage_life WHERE branch_id='.$data['branch_id'].' and bm_id='.$item['bm_id'].' and balance_num - freeze_num >0 and quit_date > '.$nowTime.' ORDER BY expiring_date ASC';
                $row = $basicMaterialStorageLifeObj->db->selectrow($sql);
                if($row){
                    $can_use_store = $row['balance_num'] - $row['freeze_num'];
                    if($can_use_store >= $now_bm_num){
                        $freeze_num = $now_bm_num;
                        $is_end = true;
                    }else{
                        $freeze_num = $can_use_store;
                        $now_bm_num -= $can_use_store;
                    }

                    $update_sql = 'UPDATE sdb_material_basic_material_storage_life SET freeze_num=freeze_num+'.$freeze_num.' WHERE bmsl_id='.$row['bmsl_id'];
                    $basicMaterialStorageLifeObj->db->exec($update_sql);
                    $affect_row = $basicMaterialStorageLifeObj->db->affect_row();
                    if(!(is_numeric($affect_row) && $affect_row > 0)){
                        return false;
                    }else{
                        //生成相应的采购入库流水单据信息
                        $tmp_bill_data = array(
                            'bmsl_id' => $row['bmsl_id'],
                            'bm_id'  => $row['bm_id'],
                            'material_bn'  => $row['material_bn'],
                            'material_bn_crc32' => $row['material_bn_crc32'],
                            'expire_bn'  => $row['expire_bn'],
                            'nums'  => $freeze_num,
                            'branch_id' => $row['branch_id'],
                            'bill_id'  => $data['bill_id'],
                            'bill_bn'  => $data['bill_bn'],
                            'bill_type' => $data['bill_type'],
                            'bill_io_type' => 2,//写死类型为2，冻结批次类型单据
                        );

                        if(!$basicMaterialStorageLifeBillsObj->save($tmp_bill_data)){
                            return false;
                        }
                        unset($tmp_bill_data);

                        //重组原数据明细的关联批次信息引用返回，做应用层程序的暂存所用
                        $data['items'][$k]['expire_bns_info'][] = array('expire_bn'=>$row['expire_bn'], 'nums'=>$freeze_num);
                    }

                    //记日志
                    $operationLogObj->write_log('storage_life_freeze@wms', $row['bmsl_id'], "物料保质期批次预占,关联单据号:".$data['bill_bn'].",数量:".$freeze_num);

                    //跳出死循环处理下一个保质期物料
                    if($is_end){
                        break;
                    }

                }else{
                    $msg = "物料找不到对应的仓库保质期批次信息";
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 冻结时批次明细参数校验
     * @param array $params 批次明细参数信息
     * @param string $msg 批次明细校验错误信息
     */
    private function checkFreezeParams($params,&$msg){
        $mustHas = array('branch_id','bill_id','bill_bn','bill_type','items');
        $itemMustHas = array('bm_id','num');

        if($params){
            $arrI = array_keys($params);
            if(count(array_diff($mustHas,$arrI)) > 0){
                $msg[] ='必填字段不全';
            }

            foreach($params as $field => $val){
                switch ($field){
                    case 'items':
                        if(!is_array($val)){
                            $msg[] = '字段:'. $field.'-非数组类型';
                        }

                        foreach($params['items'] as $key =>$item){
                            $arrI = array_keys($item);
                            if(count(array_diff($itemMustHas,$arrI)) > 0){
                                $msg[] ='明细行' . $key .'-' . '必填字段不全';
                            }else{
                                foreach($item as $ifield=>$value){
                                    switch ($ifield){
                                        case 'num':
                                                if(is_numeric($value) && $value>0){
                                                    //do nothing
                                                } else{
                                                    $msg[] = '行:'.$key .'-字段:'. $ifield.'-非数字类型';
                                                }
                                            break;
                                    }
                                }
                            }
                        }

                        break;
                }
            }


            if ($msg && count($msg) > 0){
                return false;
            }
        }else{
            $msg[] = '没有传入参数';
            return false;
        }

        return true;
    }

    /**
     *
     * 释放保质期明细物料数量
     * @param array $data 批次信息
     */
    public function unfreeze($data,&$msg){
        //校验传入参数
        if(!$this->checkUnfreezeParams($data,$msg)){
            return false;
        }

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $operationLogObj  = app::get('ome')->model('operation_log');

        $need_freeze = array();
        $storageLifeBills = $basicMaterialStorageLifeBillsObj->getList('bmsl_id,nums,bill_bn',array('branch_id'=>$data['branch_id'],'bill_id'=>$data['bill_id'],'bill_type'=>$data['bill_type'],'bill_io_type'=>2));
        if($storageLifeBills){
            foreach($storageLifeBills as $storageLifeBill){
                if(isset($need_freeze[$storageLifeBill['bmsl_id']])){
                    $need_freeze[$storageLifeBill['bmsl_id']] += $storageLifeBill['nums'];
                }else{
                    $need_freeze[$storageLifeBill['bmsl_id']] = $storageLifeBill['nums'];
                }
            }

            if($need_freeze){
                foreach($need_freeze as $id => $num){
                    $update_str = "freeze_num=IF(CAST((freeze_num -$num) AS SIGNED)>0,freeze_num-$num,0)";
                    $update_sql = 'UPDATE sdb_material_basic_material_storage_life SET '.$update_str.' WHERE bmsl_id='.$id;
                    $basicMaterialStorageLifeObj->db->exec($update_sql);
                    $affect_row = $basicMaterialStorageLifeObj->db->affect_row();
                    if(!(is_numeric($affect_row) && $affect_row > 0)){
                        return false;
                    }

                    //记日志
                    $operationLogObj->write_log('storage_life_unfreeze@wms', $id, "物料保质期批次释放预占,关联单据号:".$storageLifeBills[0]['bill_bn'].",数量:".$num);
                }

                $basicMaterialStorageLifeBillsObj->update(array('bill_io_type'=>3),array('branch_id'=>$data['branch_id'],'bill_id'=>$data['bill_id'],'bill_type'=>$data['bill_type'],'bill_io_type'=>2));
            }
        }

        return true;
    }

    /**
     * 释放冻结时批次明细参数校验
     * @param array $params 批次明细参数信息
     * @param string $msg 批次明细校验错误信息
     */
    private function checkUnfreezeParams($params,&$msg){
        $mustHas = array('branch_id','bill_id','bill_type');

        if($params){
            $arrI = array_keys($params);
            if(count(array_diff($mustHas,$arrI)) > 0){
                $msg[] ='必填字段不全';
            }

            if(is_array($msg) && count($msg)>0){
                return false;
            }
        }else{
            $msg[] = '没有传入参数';
            return false;
        }

        return true;
    }

    /**
     *
     * 保质期明细物料数量扣减，冻结释放
     * @param array $data 批次信息
     */
    public function consign($data, &$out_storagelife, &$msg){
        //校验传入参数
        if(!$this->checkConsignParams($data,$msg)){
            return false;
        }

        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $basicMaterialStorageLifeBillsObj = app::get('material')->model('basic_material_storage_life_bills');
        $operationLogObj  = app::get('ome')->model('operation_log');

        $consign_num = array();
        $storageLifeBills = $basicMaterialStorageLifeBillsObj->getList('bmsl_id,bm_id,material_bn,expire_bn,nums,bill_bn',array('branch_id'=>$data['branch_id'],'bill_id'=>$data['bill_id'],'bill_type'=>$data['bill_type'],'bill_io_type'=>2), 0, -1);
        if($storageLifeBills){
            foreach($storageLifeBills as $k => $storageLifeBill){
                $basicMaterialInfo = $basicMaterialObj->getList('material_name', array('material_bn' => $storageLifeBill['material_bn']), 0, 1);
                if($basicMaterialInfo){
                    $storageLifeBills[$k]['product_name'] = $basicMaterialInfo[0]['material_name'];
                }
                unset($basicMaterialInfo);

                if(isset($consign_num[$storageLifeBill['bmsl_id']])){
                    $consign_num[$storageLifeBill['bmsl_id']] += $storageLifeBill['nums'];
                }else{
                    $consign_num[$storageLifeBill['bmsl_id']] = $storageLifeBill['nums'];
                }
            }

            if($consign_num){
                foreach($consign_num as $id => $num){
                    $update_str = "balance_num=IF(CAST((balance_num -$num) AS SIGNED)>0,balance_num-$num,0), freeze_num=IF(CAST((freeze_num -$num) AS SIGNED)>0,freeze_num-$num,0), out_num=out_num+$num";
                    $update_sql = 'UPDATE sdb_material_basic_material_storage_life SET '.$update_str.' WHERE bmsl_id='.$id;
                    $basicMaterialStorageLifeObj->db->exec($update_sql);
                    $affect_row = $basicMaterialStorageLifeObj->db->affect_row();
                    if(!(is_numeric($affect_row) && $affect_row > 0)){
                        return false;
                    }

                    //记日志
                    $operationLogObj->write_log('storage_life_consign@wms', $id, "物料保质期批次出库,关联单据号:".$storageLifeBills[0]['bill_bn'].",数量:".$num);
                }
                unset($consign_num);

                $basicMaterialStorageLifeBillsObj->update(array('bill_io_type'=>0),array('branch_id'=>$data['branch_id'],'bill_id'=>$data['bill_id'],'bill_type'=>$data['bill_type'],'bill_io_type'=>2));
            }

            $out_storagelife = $storageLifeBills;
        }

        return true;
    }

    /**
     * 出库时批次明细参数校验
     * @param array $params 批次明细参数信息
     * @param string $msg 批次明细校验错误信息
     */
    private function checkConsignParams($params,&$msg){
        $mustHas = array('branch_id','bill_id','bill_type');

        if($params){
            $arrI = array_keys($params);
            if(count(array_diff($mustHas,$arrI)) > 0){
                $msg[] ='必填字段不全';
            }

            if(is_array($msg) && count($msg)>0){
                return false;
            }
        }else{
            $msg[] = '没有传入参数';
            return false;
        }

        return true;
    }

    /**
     *
     * 保质期延保日期更新
     * @param array $data 批次延保信息
     *
     */
    public function updatePeriodValidity($data, &$msg)
    {
        #校验传入参数
        if(!$this->checkPeriodValidityParams($data, $msg)){
            return false;
        }

        $basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
        $oOperation_log  = app::get('ome')->model('operation_log');

        $date_type    = array(1=>'天', '月', '年');

        #数据处理
        $bmsl_ids    = $data['bmsl_ids'];

        $data['production_date']  = strtotime($data['production_date']);
        $data['guarantee_period']  = $data['guarantee_period'];

        switch($data['date_type']){
            case 'day':
                $data['expiring_date']  = strtotime('+'.$data['guarantee_period'].' days',$data['production_date'])+86399;
                $data['date_type'] = 1;
                break;
            case 'month':
                $data['expiring_date']  = strtotime('+'.$data['guarantee_period'].' months',$data['production_date'])+86399;
                $data['date_type'] = 2;
                break;
            case 'year':
                $data['expiring_date']  = strtotime('+'.$data['guarantee_period'].' years',$data['production_date'])+86399;
                $data['date_type'] = 3;
                break;
            case 'date':
                $data['guarantee_period'] = (strtotime($data['expiring_date']) - $data['production_date'])/86400;
                $data['expiring_date']  = strtotime($data['expiring_date'])+86399;
                $data['date_type'] = 1;
                break;
        }

        $field       = 'bmsl_id, expire_bn, bm_id, material_bn, production_date, guarantee_period, date_type, expiring_date, warn_date, quit_date, warn_day, quit_day';
        $dataList    = $basicMaterialStorageLifeObj->getList($field, array('bmsl_id'=>$bmsl_ids));
        if(empty($dataList))
        {
            $msg[] = '没有找到相关记录';
            return false;
        }

        #检验保质期天数是否有效
        $fail_array   = array();
        $updateData   = array();
        $logsData     = array();

        $diff_date    = ($data['expiring_date'] - $data['production_date']) / 86400;
        $diff_date    = intval($diff_date);
        foreach ($dataList as $key => $row)
        {
            if($diff_date <= $row['quit_day'])
            {
                $fail_array[$row['bm_id']]    = $row['material_bn'];
            }

            # [更新]预警日期&&自动退出库存日期
            $warn_date    = $data['expiring_date'] - $row['warn_day']*86400;
            $quit_date    = $data['expiring_date'] - $row['quit_day']*86400;

            $updateData[$row['bmsl_id']]    = array(
                                                    'production_date' => $data['production_date'],
                                                    'guarantee_period' => $data['guarantee_period'],
                                                    'date_type' => $data['date_type'],
                                                    'expiring_date' => $data['expiring_date'],
                                                    'warn_date' => $warn_date,
                                                    'quit_date' => $quit_date,
                                                );
            $logsData[$row['bmsl_id']]    = array(
                                                    'expire_bn' => $row['expire_bn'],
                                                    'production_date' => $row['production_date'],
                                                    'guarantee_period' => $row['guarantee_period'],
                                                    'date_type' => $row['date_type'],
                                                    'expiring_date' => $row['expiring_date'],
                                                    'warn_date' => $row['warn_date'],
                                                    'quit_date' => $row['quit_date'],
                                                );
        }

        if($fail_array)
        {
            $msg[] = '物料编码：'. implode(',', $fail_array) .'的保质期天数必须大于自动退出库存天数';
            return false;
        }

        #事务开启
        $basicMaterialStorageLifeObj->db->beginTransaction();

        #保存
        foreach ($updateData as $update_id => $update_row)
        {
            $logsRow    = $logsData[$update_id];#日志数据

            if(!$basicMaterialStorageLifeObj->update($update_row, array('bmsl_id'=>$update_id)))
            {
                $basicMaterialStorageLifeObj->db->rollBack();

                $msg[] = '保质期编码：'. $logsRow['expire_bn'] .'更新失败';
                return false;
            }

            #记录日志
            $log_msg   = "保质期编码：". $logsRow['expire_bn'] ."更新成功，生产日期(". date('Y-m-d', $logsRow['production_date']) ." => ". date('Y-m-d', $update_row['production_date']) .")";
            $log_msg   .= ", 保质期时长(". $logsRow['guarantee_period'] . $date_type[$logsRow['date_type']] ." => ". $update_row['guarantee_period'] . $date_type[$update_row['date_type']] .")";
            $log_msg   .= ",<br> 过期日期(". date('Y-m-d', $logsRow['expiring_date']) ." => ". date('Y-m-d', $update_row['expiring_date']) .")";
            $log_msg   .= ", 预警日期(". date('Y-m-d', $logsRow['warn_date']) ." => ". date('Y-m-d', $update_row['warn_date']) .")";
            $log_msg   .= ", 自动退出库存日期(". date('Y-m-d', $logsRow['quit_date']) ." => ". date('Y-m-d', $update_row['quit_date']) .")";

            $oOperation_log->write_log('storage_life_edit@wms', $update_id, $log_msg);
        }

        #销毁
        unset($data, $dataList, $updateData, $logsData, $logsRow, $log_msg);

        $basicMaterialStorageLifeObj->db->commit();
        return true;
    }

    /**
     * 更新保质期状态
     * @param string $msg 校验错误信息
     */
    function updateStatusPeriodValidity($data,$action, &$msg){
    	$bmsl_id=$data["bmsl_ids"];
    	if(empty($bmsl_id)){
    		$msg[] = '无效操作';
    		return false;
    	}

    	$basicMaterialStorageLifeObj = app::get('material')->model('basic_material_storage_life');
    	$oOperation_log = app::get('ome')->model('operation_log');

    	$row = $basicMaterialStorageLifeObj->dump(array('bmsl_id'=>$bmsl_id));
    	if(empty($row)){
    		$msg[] = '没有找到相关记录';
    		return false;
    	}

    	//"status" field => 1:active 2:deactive
    	$updateStatus=2;
    	$log_msg="物料保质期批次状态关闭";
    	if($action=="active"){
    		$updateStatus=1;
    		$log_msg="物料保质期批次状态激活";
    	}

    	$basicMaterialStorageLifeObj->db->beginTransaction();

        $update_sql = 'UPDATE sdb_material_basic_material_storage_life SET status='.$updateStatus.' WHERE bmsl_id='.$bmsl_id;
    	$basicMaterialStorageLifeObj->db->exec($update_sql);

    	$oOperation_log->write_log('storage_life_statusUpdate@wms', $bmsl_id, $log_msg);

    	$basicMaterialStorageLifeObj->db->commit();

    	return true;
    }

    /**
     * 保质期延保参数校验
     * @param array $params 延保参数
     * @param string $msg 校验错误信息
     *
     */
    private function checkPeriodValidityParams($params, &$msg)
    {
        $mustHas = array('bmsl_ids','production_date','date_type','guarantee_period','expiring_date');

        if($params)
        {
            $arrI = array_keys($params);

            if(count(array_diff($mustHas,$arrI)) > 0){
                $msg[] ='必填字段不全';
            }

            if(is_array($msg) && count($msg)>0){
                return false;
            }
        }else{
            $msg[] = '没有传入参数';
            return false;
        }

        return true;
    }
}
