<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process_jingdong_tuotou{

    public function structure_import_data(&$mdl,$row,&$format_row=array(),&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                //组织数据
                $format_row = array(
                    'order_bn' => trim($row['data']['订单编号']),
                    'sale' => trim($row['data']['货款']),
                    'fee' => trim($row['data']['佣金']),
                    'service' => trim($row['data']['打包服务费']),
                    'ship' => trim($row['data']['配送费']),
                    // 'procedure' => trim($row['data']['手续费']),
                    'date' => trim($row['data']['关单时间']),
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
                if($row['data']['date'] != '' && $row['data']['order_bn'] != '合计'){
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
                if($row['data']['date'] != '' && $row['data']['order_bn'] != '合计'){
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

                    //货款
                    if($row['data']['sale'] != '' && $row['data']['sale'] != '0'){
                        $sdf1 = $sdf;
                        $sdf1['money'] = $row['data']['sale'];
                        if( $row['data']['sale']>0 ){
                            $sdf1['fee_item'] = '销售收款';
                        }elseif( $row['data']['sale']<0 ){
                            $sdf1['fee_item'] = '销售退款';
                        }
                        $sdf1['unique_id'] = finance_io_bill_func::unique_id(array(
                            $row['data']['date'],
                            $row['data']['order_bn'],
                            $sdf1['fee_item']
                        ));

                        $rs = finance_io_bill_func::save($sdf1);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    //佣金
                    if($row['data']['fee'] != '' && $row['data']['fee'] != '0'){
                        $sdf2 = $sdf;
                        $sdf2['money'] = $row['data']['fee'];
                        $sdf2['fee_item'] = '佣金';
                        $sdf2['unique_id'] = finance_io_bill_func::unique_id(array(
                            $row['data']['date'],
                            $row['data']['order_bn'],
                            $sdf2['fee_item']
                        ));

                        $rs = finance_io_bill_func::save($sdf2);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    //打包服务费
                    if($row['data']['service'] != '' && $row['data']['service'] != '0'){
                        $sdf3 = $sdf;
                        $sdf3['money'] = $row['data']['service'];
                        $sdf3['fee_item'] = '操作费';
                        $sdf3['unique_id'] = finance_io_bill_func::unique_id(array(
                            $row['data']['date'],
                            $row['data']['order_bn'],
                            $sdf3['fee_item']
                        ));

                        $rs = finance_io_bill_func::save($sdf3);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    //配送费
                    if($row['data']['ship'] != '' && $row['data']['ship'] != '0'){
                        $sdf4 = $sdf;
                        $sdf4['money'] = $row['data']['ship'];
                        $sdf4['fee_item'] = '物流费';
                        $sdf4['unique_id'] = finance_io_bill_func::unique_id(array(
                            $row['data']['date'],
                            $row['data']['order_bn'],
                            $sdf4['fee_item']
                        ));

                        $rs = finance_io_bill_func::save($sdf4);
                        if($rs['status'] == 'fail' ){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    // //手续费
                    // if($row['data']['procedure'] != '' && $row['data']['procedure'] != '0'){
                    //     $sdf5 = $sdf;
                    //     $sdf5['money'] = $row['data']['procedure'];
                    //     $sdf5['fee_item'] = '手续费';
                    //     $sdf5['unique_id'] = finance_io_bill_func::unique_id(array(
                    //         $row['data']['date'],
                    //         $row['data']['order_bn'],
                    //         $sdf5['fee_item']
                    //     ));

                    //     $rs = finance_io_bill_func::save($sdf5);
                    //     if($rs['status'] == 'fail'){
                    //         $result['status'] = 'fail';
                    //         $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                    //     }
                    // }


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

        #货款验证
        $rs = finance_io_bill_verify::isPrice($data['sale'],'货款格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }   

        #佣金验证
        $rs = finance_io_bill_verify::isPrice($data['fee'],'佣金格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #打包服务费验证
        $rs = finance_io_bill_verify::isPrice($data['service'],'打包服务费格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #配送费验证
        $rs = finance_io_bill_verify::isPrice($data['ship'],'配送费格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #手续费验证
        // $rs = finance_io_bill_verify::isPrice($data['procedure'],'手续费格式错误');
        // if($rs['status'] == 'fail'){
        //     $result['msg'][ $inline ] .= $rs['msg'].'|';
        //     $result['status'] = 'fail';
        // }

        #日期验证
        if($data['date']!=''){
            $rs = finance_io_bill_verify::isDate($data['date'],'关单时间格式错误');
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
            $title = finance_io_bill_title::getTitle('jingdong_tuotou');
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
            'fee_obj'           => '京东',
            'money'             => '',
            'fee_item'          => '',
            'member'            => '京东',
            'credential_number' => '',
        );

        // 货款--过滤掉金额为零的信息
        if( !empty( $tmp['sale'] )){
            $fee_item = $tmp['sale'] > 0 ? '销售收款' : '销售退款';

            $unique_id = finance_func::unique_id(array(
                $tmp['date'],
                $tmp['order_bn'],
                $fee_item,
            ));

            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['sale'],'fee_item'=>$fee_item,'unique_id'=>$unique_id));
        }

        // 佣金--过滤掉金额为零的信息
        if( !empty( $tmp['fee'] )){
            $unique_id = finance_func::unique_id(array(
                $tmp['date'],
                $tmp['order_bn'],
                '佣金',
            ));

            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['fee'],'fee_item'=>'佣金','unique_id'=>$unique_id));
        }

        // 打包服务费--过滤掉金额为零的信息
        if( !empty( $tmp['service']) ){
            $unique_id = finance_func::unique_id(array(
                $tmp['date'],
                $tmp['order_bn'],
                '操作费',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['service'],'fee_item'=>'操作费','unique_id'=>$unique_id));
        }

        // 配送费--过滤掉金额为零的信息
        if( !empty( $tmp['ship'] )){
            $unique_id = finance_func::unique_id(array(
                $tmp['date'],
                $tmp['order_bn'],
                '物流费',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['ship'],'fee_item'=>'物流费','unique_id'=>$unique_id));
        }

        return $sdf;
    }

}
?>