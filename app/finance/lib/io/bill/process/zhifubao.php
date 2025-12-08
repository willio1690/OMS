<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process_zhifubao{

    /**
     * 读取到的数据格式化
     *
     * @param Object $mdl MODEL层对象
     * @param Array $row 读取一行
     * @return void
     * @author 
     **/
    public function getSDf(&$mdl,$row,&$mark)
    {
        if(!$row) return false;

        static $oldKey;
        static $title;
        if (!$oldKey) {
            $title = finance_io_bill_title::getTitle('zhifubao');
            # 读取文件标题，并记录它的位置
            foreach ($title as $key => $value) {
                $pCol = array_search($value, $row,true);
                if ($pCol === false) {
                    $oldKey = '';
                    return false;
                }

                $oldKey[$key] = $pCol;
            }
            $mark = 'title';

            return $title;
        }

        
        $isReturn = $row[$oldKey['expenditure']] != 0 || $row[$oldKey['business_type']] != '交易付款';

        if($isReturn) {
            return false;
        }
        $mark = 'contents';
        # 读取数据
        $tmp = array();
        foreach ($oldKey as $column => $pCol) {
            $tmp[$column] = $row[$pCol];
        }
        $tmp['fee_obj'] = '淘宝';
        $tmp['fee_item'] = '销售收款';
        $base_sdf = array(
            'order_bn'          => str_replace('T200P','',$tmp['order_bn']),
            'channel_id'        => '',
            'channel_name'      => '',
            'trade_time'        => $tmp['date'],
            'fee_obj'           => $tmp['fee_obj'],
            'money'             => '',
            'fee_item'          => '',
            'credential_number' => $tmp['credential_number'],
            'member'            => $tmp['member'],
            'memo'              => $tmp['memo'],
        );

        if ( !empty($tmp['price']) ) {
            $unique_id = finance_func::unique_id(array(
                $tmp['credential_number'],
                $tmp['fee_obj'],
                $tmp['fee_item'],
            ));
            
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['price'],'fee_item'=>$tmp['fee_item'],'unique_id'=>$unique_id));   
        }

        return $sdf;
    }

}
?>