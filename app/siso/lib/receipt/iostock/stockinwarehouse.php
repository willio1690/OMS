<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class siso_receipt_iostock_stockinwarehouse extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface{
    
    /**
     * 
     * 出入库类型id
     * @var int
     */
    public $_typeId = 1000;
    
    /**
     * 
     * 出库/入库动作
     * @var int
     */
    protected $_io_type = 1;
    /**
     * 
     * 根据调拨入库组织出入库单明细内容
     * @param int $iso_id
     * 
     */
    function get_io_data($data){
        $iso_id = $data['iso_id'];
        $isoObj = app::get('warehouse')->model('iso');
        $iostock_data = array();
        $iso_detail = $isoObj->dump(array('iso_id'=>$iso_id,'type_id'=>$this->_typeId),'*');
        if ($data['items']){
            $iso_items_detail = $data['items'];;
        }
        $supplier_id = $data['supplier_id'] ? $data['supplier_id'] : $iso_detail['supplier_id'];
        if ($supplier_id && !$data['supplier_name']){
            $supplierObj = app::get('purchase')->model("supplier");
            $supplier = $supplierObj->dump($supplier_id,'name');
            $data['supplier_name'] = $supplier['name'];
        }
        $operator = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $oper = $iso_detail['oper']== '' ? $data['operator'] : $iso_detail['oper'];
        $create_time = $data['operate_time'] == '' ? $iso_detail['create_time']: $data['operate_time'];
        if ($iso_items_detail){
            foreach ($iso_items_detail as $k=>$v){
                $data['branch_id'] = $data['branch_id'] ? $data['branch_id'] : $iso_detail['branch_id'];
                $branch_id = $v['branch_id'] ? $v['branch_id'] : $data['branch_id'];
                $memo = $v['memo'] ? $v['memo'] : $data['memo'];
                $iostock_data[] = array(
                    'branch_id'        => $branch_id,
                    'original_bn'      => $data['original_bn']!='' ? $data['original_bn'] :$iso_detail['iso_bn'],
                    'original_id'      => $data['original_id']!='' ? $data['original_id'] : $iso_id,
                    'original_item_id' => $v['iso_items_id'],
                    'supplier_id'      => $supplier_id,
                    'supplier_name'    => $data['supplier_name'],
                    'bn'               => $v['bn'],
                    'iostock_price'    => $v['price']!='' ? $v['price']: '0',
                    'nums'             => $v['nums'],
                    'cost_tax'         => $iso_detail['cost_tax'],
                    'oper'             => $oper,
                    'create_time'      => $create_time,
                    'operator'         => $operator,
                    'memo'              =>$memo,
                    'orig_type_id'       =>$data['orig_type_id'] ? $data['orig_type_id'] : 0,
                );
            }
        }
        return $iostock_data;
    }
    
    /**
     * 创建
     * @param mixed $params 参数
     * @param mixed $data 数据
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function create($params, &$data, &$msg=null){
        $result = parent::create($params, $data, $msg);
        return $result;
    }
}
