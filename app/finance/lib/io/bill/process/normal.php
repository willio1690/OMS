<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process_normal{

    public function structure_import_data(&$mdl,$row,&$format_row=array(),&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                //组织数据
                $format_row = array(
                    'order_bn' => trim($row['data']['*:订单号']),
                    'credential_number' => trim($row['data']['*:凭据号']),
                    'price' => trim($row['data']['*:金额']),
                    'fee_obj' => trim($row['data']['*:费用对象']),
                    'member' => trim($row['data']['*:交易对方']),
                    'fee_item' => trim($row['data']['*:费用项']),
                    'date' => trim($row['data']['*:日期']),
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
                //基础数据验证
                $result = $this->field_verify($row,$mdl,true);
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
                    'fee_obj' => $row['data']['fee_obj'],
                    'money' => '',
                    'fee_item' => '',
                    'credential_number' => $row['data']['credential_number'],
                    'member' => $row['data']['member'],
                );

                //金额
                if($row['data']['price'] != '' && $row['data']['price'] != '0'){
                    $sdf1 = $sdf;
                    $sdf1['money'] = $row['data']['price'];
                    $sdf1['fee_item'] = $row['data']['fee_item'];
                    $sdf1['unique_id'] = finance_func::unique_id(array(
                        $sdf['credential_number'],
                        $sdf['fee_obj'],
                        $sdf1['fee_item']
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


    private function field_verify($row,$mdl,$is_judge_repeat = false){
        $result = array();
        $inline = $row['inline'];
        $data = $row['data'];

        #订单号是否存在验证
        $rs = finance_io_bill_verify::isOrder($data['order_bn'],'订单号不存在');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #凭据号是否为空验证
        if($data['credential_number'] == ''){
            $result['msg'][ $inline ] .= '凭据号不能为空|';
            $result['status'] = 'fail';
        }

        #费用项验证
        $rs = kernel::single('finance_bill')->is_exist_item_by_table($data['fee_item']);
        if($rs == false){
            $result['msg'][ $inline ] .= '费用项'.$data['fee_item'].'不存在|';
            $result['status'] = 'fail';
        }

        #金额验证
        $rs = finance_io_bill_verify::isPrice($data['price'],'金额格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #日期验证
        if($data['date']!=''){
            $rs = finance_io_bill_verify::isDate($data['date'],'日期格式错误');
            if($rs['status'] == 'fail'){
                $result['msg'][ $inline ] .= $rs['msg'].'|';
                $result['status'] = 'fail';
            }
        }

        #账期验证
        $rs = finance_io_bill_verify::isTaskCheckInitTime($data['date'],$mdl->_import_func->task_id);
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #主信息判重
        if($is_judge_repeat == true){
            $mdl->_import_func->set_unique($data['credential_number'],$inline,false,'');
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
            $title = finance_io_bill_title::getTitle('normal');
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

        $base_sdf = array(
            'order_bn'          => $tmp['order_bn'],
            'channel_id'        => '',
            'channel_name'      => '',
            'trade_time'        => $tmp['date'],
            'fee_obj'           => $tmp['fee_obj'],
            'money'             => '',
            'fee_item'          => '',
            'credential_number' => $tmp['credential_number'],
            'member'            => $tmp['member'],
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