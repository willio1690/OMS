<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-22
 * @describe 打印数据获取，只处理正常打印的数据，补打更改字段另行处理
 */
class logisticsmanager_print_data  {

    /**
     * @param $delivery array 发货单信息（多条，以发货单ID为键值）
     * @param $corp array 物流公司（单条）
     * @param $printField array 需要获取数据的字段
     * @param $type string 类型 brush 或者 ome
     * @return array 打印模板所需要的数据
     */

    public function dealPrintData($delivery, $corp, $printField, $type) {
        $oriData = $delivery; //使用引用方式调用变量$oriData
        $arrFieldMethod = kernel::single('logisticsmanager_print_mapping')->getFieldMethod($printField);
        foreach($arrFieldMethod as $method => $field) {
            try {
                $class = $type . '_print_' . $method;
                if (class_exists($class)) {
                    kernel::single($class)->$method($oriData, $corp, $field);
                }
            } catch (Exception $e) {
                try {
                    if (class_exists('logisticsmanager_print_data_' . $method)) {
                        kernel::single('logisticsmanager_print_data_' . $method)->$method($oriData, $corp, $field, $type);
                    }
                } catch (Exception $e) {
                    if (method_exists($this, $method)) {
                        $this->$method($oriData, $corp, $field, $type);
                    }
                }
            }

        }

        $hashCode = kernel::single('ome_security_hash')->get_code();

        //处理自定义大头笔
        $this->getPrintTag($oriData, $printField);
        $printData = array();
        foreach($oriData as $row) {
            $tmpRowData = kernel::single('logisticsmanager_print_mapping')->getFieldData($printField, $row);

            $tmpRowData = $this->formatrow($tmpRowData,$row);

            kernel::single('logisticsmanager_print_format')->formatField($tmpRowData);
            if($tmpRowData['member_uname']) $row['member_uname'] = $tmpRowData['member_uname'];
            // 判断是否加密
            $encryptField = ['ship_detailaddr','ship_addr','ship_name','ship_addr_mark','ship_detailaddr_mark','ship_mobile','dly_mobile','dly_tel','ship_tel'];
            foreach ($encryptField as $cf) {
                //虚拟物流打印不需要解密
                if ($corp['type'] == 'virtual_delivery' && isset($tmpRowData[$cf])) {
                    $tmpRowData[$cf] = ($index = strpos($tmpRowData[$cf], '>>')) ? substr($tmpRowData[$cf], 0, $index) : $tmpRowData[$cf];
                }

                if (false !== strpos($tmpRowData[$cf], $hashCode)) {
                    $tmpRowData['delivery_id'] = $row['delivery_id'];
                    $tmpRowData['is_encrypt']  = true;
                    $tmpRowData['app']  = $type;

                    break;
                }
            }

            $printData[] = $tmpRowData;
        }
        return $printData;
    }

    private function formatrow($tmpRowData,$row)
    {
        $json_packet = @json_decode($tmpRowData['json_packet'],true);
        if (kernel::single('ome_delivery_bool_type')->isTry($row['bool_type']) && isset($json_packet['printData']['should_pay'])) {
            $tmpRowData['delivery_receivable'] = $json_packet['printData']['should_pay'];
        }

        return $tmpRowData;
    }

    private function date(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        $date = array();
        if(isset($GLOBALS['user_timezone'])){
            $t = time()+($GLOBALS['user_timezone']-SERVER_TIMEZONE)*3600;
        }else{
            $t = time();
        }
        foreach($field as $f) {
            $date[$f] = date($f, $t);
        }
        foreach($oriData as $k => &$val) {
            foreach($field as $f) {
                if(isset($date[$f])) {
                    $val[$pre . $f] = $date[$f];
                } else {
                    $val[$pre . $f] = '';
                }
            }
        }
    }

    private function corp(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        foreach($oriData as $k => &$val) {
            foreach($field as $f) {
                if(isset($corp[$f])) {
                    $val[$pre . $f] = $corp[$f];
                } else {
                    $val[$pre . $f] = '';
                }
            }
        }
    }

    private function getPrintTag(&$oriData, $printField) {
        $tagIds = array();
        foreach($printField as $k => $val) {
            if(strpos($val, 'print_tag_') !== false) {
                $tag = explode('_', $val);
                $tagIds[] = $tag[2];
            }
        }
        kernel::single('logisticsmanager_print_data_tag')->getPrintTag($oriData, $tagIds);
    }

    /**
     * 获取SelectField
     * @param mixed $mField mField
     * @param mixed $field field
     * @param mixed $model model
     * @param mixed $tableAlias tableAlias
     * @return mixed 返回结果
     */
    public function getSelectField($mField, $field, $model, $tableAlias = '') {
        $arrField = array_merge($mField, $field);
        $col = array_keys($model->_columns());
        $ret = $tableAlias ? $tableAlias . '.' . implode(',' . $tableAlias . '.', array_intersect($arrField, $col)) : implode(',', array_intersect($arrField, $col));
        return $ret;
    }

}