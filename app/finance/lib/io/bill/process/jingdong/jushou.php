<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process_jingdong_jushou{

    public function structure_import_data(&$mdl,$row,&$format_row=array(),&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                //组织数据
                $format_row = array(
                    'order_bn' => trim($row['data']['订单编号']),
                    'ship' => trim($row['data']['拒收配送费']),
                    'downtime' => trim($row['data']['下单时间']),
                );
            }
        }
    }

    /**
     * 检查ing_import_data
     * @param mixed $mdl mdl
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回验证结果
     */
    public function checking_import_data(&$mdl,$row,&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if($row['group'] == 1) {
                if($row['data']['order_bn'] != '合计'){
                    //基础数据验证
                    $result = $this->field_verify($row,$mdl);
                }
            }
        }
    }

    /**
     * finish_import_data
     * @param mixed $mdl mdl
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function finish_import_data(&$mdl,$row,&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                if($row['data']['order_bn'] != '合计'){
                    //数据验证
                    $result = $this->field_verify($row,$mdl);
                    //数据保存
                    $order = finance_io_bill_func::order_is_exists($row['data']['order_bn']);
                    $shop = finance_io_bill_func::getShopByShopID($order['shop_id']);

                    $sdf = array(
                        'order_bn' => $row['data']['order_bn'],
                        'channel_id' => $shop['shop_id'],
                        'channel_name' => $shop['name'],
                        'trade_time' => $row['data']['date'],
                        'fee_obj' => '京东',
                        'money' => '',
                        'fee_item' => '',
                        'member' => '京东',
                        'credential_number' => '',
                    );

                    //配送费
                    if($row['data']['ship'] != '' && $row['data']['ship'] != '0'){
                        $sdf1 = $sdf;
                        $sdf1['money'] = $row['data']['ship'];
                        $sdf1['fee_item'] = '物流费';
                        $sdf1['unique_id'] = finance_func::unique_id(array(
                            $row['data']['downtime'],
                            $row['data']['order_bn'],
                            $sdf1['fee_item'],
                        ));
                        $rs = finance_io_bill_func::save($sdf1);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }
                }
            }
        }
    }


    private function field_verify($row,$mdl){
        $result = array();
        $inline = $row['inline'];
        $data = $row['data'];

        #订单号是否存在验证
        $rs = finance_io_bill_verify::isOrder($data['order_bn'],'订单号不存在');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #拒收配送费验证
        $rs = finance_io_bill_verify::isPrice($data['ship'],'拒收配送费格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }   

        #账期验证
        $rs = finance_io_bill_verify::isTaskCheckInitTime($data['date'],$mdl->_import_func->task_id);
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }


        return $result;
    }

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
            $title = finance_io_bill_title::getTitle('jingdong_jushou');
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
        
        $mark = 'contents';

        # 读取数据
        foreach ($oldKey as $column => $pCol) {
            $tmp[$column] = $row[$pCol];
        }

        $sdf = array();

        $base_sdf = array(
            'order_bn'          => $tmp['order_bn'],
            'channel_id'        => '',
            'channel_name'      => '',
            'trade_time'        => $tmp['downtime'],
            'fee_obj'           => '京东',
            'money'             => '',
            'fee_item'          => '',
            'member'            => '京东',
            'credential_number' => '',
        );

        if ( !empty($tmp['ship']) && $tmp['order_bn'] != '合计') {
            $unique_id = finance_func::unique_id(array(
                $tmp['downtime'],
                $tmp['order_bn'],
                '物流费',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['ship'],'fee_item'=>'物流费','unique_id'=>$unique_id));
        }
        

        return $sdf;
    }

}
?>