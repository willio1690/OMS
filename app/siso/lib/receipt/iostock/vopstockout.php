<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 出入库单据相关处理
 */
class siso_receipt_iostock_vopstockout extends siso_receipt_iostock_abstract implements siso_receipt_iostock_interface
{
     /**
      * 出入库类型id
      */
    public $_typeId = 900;
    
    /**
     * 出库/入库动作
     */
    protected $_io_type = 0;
    
    /**
     * 根据调拨出库组织出入库单明细内容
     * 
     * @param Array $data
     * @return Array
     */
    function get_io_data($data)
    {
        $iso_id    = $data['iso_id'];
        
        $stockoutObj    = app::get('purchase')->model('pick_stockout_bills');
        
        //出库单信息
        $iso_detail    = $stockoutObj->dump(array('stockout_id'=>$iso_id), '*');
        
        //出库明细
        if($data['items']){
            $iso_items_detail    = $data['items'];
        }
        
        //组织数据
        $iostock_data = array();
        if ($iso_items_detail)
        {
            $branch_id    = $iso_detail['branch_id'];
            $original_bn  = $iso_detail['stockout_no'];
            $supplier_id  = 0;//供应商id
            $supplier_name = '';//供应商名称
            $cost_tax      = 0;//税率
            $create_time   = (empty($data['operate_time']) ? $iso_detail['create_time'] : $data['operate_time']);
            $memo          = $data['memo'];
            
            //操作员
            $operator    = kernel::single('desktop_user')->get_name();
            $operator    = (empty($operator) ? 'system' : $operator);
            
            $oper          = $operator;//经手人
            
            foreach ($iso_items_detail as $k => $v)
            {
                $iostock_data[] = array(
                        'branch_id'        => $branch_id,
                        'original_bn'      => $original_bn,
                        'original_id'      => $iso_id,
                        'original_item_id' => $v['iso_items_id'],
                        'supplier_id'      => $supplier_id,
                        'supplier_name'    => $supplier_name,
                        'bn'               => $v['bn'],
                        'iostock_price'    => $v['price'],
                        'nums'             => $v['nums'],
                        'cost_tax'         => $cost_tax,
                        'oper'             => $oper,
                        'create_time'      => $create_time,
                        'operator'         => $operator,
                        'memo'             => $memo,
                );
            }
        }
        
        return $iostock_data;
    }

    /**
     * 生成出入库明细
     */
    public function create($params, &$data, &$msg=null)
    {
        $result    = parent::create($params, $data, $msg);
        
        /***
         * 唯品会出库不需要生成赊购单/退款单
         * 
         if(in_array($this->_typeId,array('900'))){
            $io = '0';
            kernel::single('console_iostockorder')->do_iostock_refunds($params['iso_id'],$io,$other_msg);
                        
        }
        ***/
        
        return $result;
    }
}