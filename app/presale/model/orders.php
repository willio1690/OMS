<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class presale_mdl_orders extends ome_mdl_orders{

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false){
        if($real){
            $table_name = 'sdb_ome_orders';
        }else{
            $table_name = 'orders';
        }
        return $table_name;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('ome')->model('orders')->get_schema();
    }
    
    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = 1;

        $extend_table_name = app::get('ome')->model('order_extend')->table_name(1);
        if(isset($filter['shop_pay']) && in_array($filter['shop_pay'],array('1','2'))){
            $where.= ' AND '.$extend_table_name.'.presale_pay_status =\''.$filter['shop_pay'].'\'';
            unset($filter['shop_pay']);
        }
        
        if(isset($filter['product_barcode'])){
            $itemsObj = app::get('ome')->model("order_items");
            $rows = $itemsObj->getOrderIdByPbarcode($filter['product_barcode']);
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[$row['order_id']] = $row['order_id'];
            }
            $where .= '  AND '.$this->table_name(1).'.order_id IN ('.implode(',', $orderId).')';
            unset($filter['product_barcode']);
        }
        
        if(isset($filter['product_bn'])){
            $orderId = array();
            $orderId[] = 0;
            
            //多销售物料查询
            if($filter['product_bn'] && is_string($filter['product_bn']) && strpos($filter['product_bn'], "\n") !== false){
                $filter['product_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['product_bn']))));
            }
            
            $itemsObj = app::get('ome')->model('order_items');
            $rows = $itemsObj->getOrderIdByFilterbnEq($filter);
            if($rows){
                foreach($rows as $row){
                    $temp_order_id = $row['order_id'];
                    $orderId[$temp_order_id] = $temp_order_id;
                }
            }
            
            $pkjrows = $itemsObj->getOrderIdByPkgbnEq($filter);
            if($pkjrows){
                foreach($pkjrows as $pkjrow){
                    $temp_order_id = $pkjrow['order_id'];
                    $orderId[$temp_order_id] = $temp_order_id;
                }
            }
            
            if ($filter['has_bn'] == 'false') {
                $where .= '  AND '.$this->table_name(1).'.order_id NOT IN ('.implode(',', $orderId).')';
            } else {
                $where .= '  AND '.$this->table_name(1).'.order_id IN ('.implode(',', $orderId).')';
            }
            unset($filter['product_bn']);
        }
        
        $parent_where = parent::_filter($filter,$tableAlias,$baseWhere);
        $parent_where = str_replace(array('AND 1 ', '1  AND'), '', $parent_where);
        
        return $where ." AND ". $parent_where;
    }

    function count($filter=null){
        $extend_table_name = app::get('ome')->model('order_extend')->table_name(1);
        $sql = 'SELECT count(sdb_ome_orders.order_id) as _count FROM `'.$this->table_name(true).'` LEFT JOIN  '.$extend_table_name.'  ON '.$this->table_name(1).'.order_id = '.$extend_table_name.'.order_id WHERE '.$this->_filter($filter,$this->table_name(1)) . $strWhere;
//echo $sql;
        $row = $this->db->selectrow($sql);
        return intval($row['_count']);
    }

    function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }

        $extend_table_name = app::get('ome')->model('order_extend')->table_name(1);
        $strWhere = '';


        $this->defaultOrder[0] = $this->table_name(true).'.createtime';
        $tmpCols = array();
        foreach(explode(',',$cols) as $col){
            if(strpos($col, 'as column')){
                $tmpCols[] = $col;
            }else{
                $tmpCols[] = $this->table_name(true).'.'.$col;
            }
        }
        $cols = implode(',',$tmpCols);
        unset($tmpCols);

        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` LEFT JOIN  '.$extend_table_name.'  ON '.$this->table_name(1).'.order_id = '.$extend_table_name.'.order_id WHERE '.$this->_filter($filter,$this->table_name(1)) . $strWhere;

        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode(' ',$orderType):$orderType);
        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        $this->_debcrypt($data);
        return $data;
    }

}



?>
