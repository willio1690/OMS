<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/8/4 14:28:59
 * @describe: 解密导出
 * ============================
 */
class ome_security_export {
    private $node_type = [];
    private $encrypt_node_type = ['xhs'];

    /**
     * 解密一个字段
     * @param  array $data [
     *    'origin' => '玉**>>sed@hash',
     *    'field_type' => 'ship_name',
     *    'shop_id' => '234123',
     *    'origin_bn' => '表单号',
     *    'type' => 'sdb_ome_orders'
     * ]
     * @return string       解密后的字符
     */

    public function decryptField($data) {
        $string = $data['origin'];
        $is_encrypt = (kernel::single('ome_security_hash')->get_code() == substr($string, -5));

        if ($is_encrypt) {
            if($data['origin_bn'] && $data['shop_id']) {
                $shop = app::get('ome')->model('shop')->db_dump(['shop_id'=>$data['shop_id']], 'node_type');
                if(empty($shop['node_type']) || !in_array($shop['node_type'], $this->node_type)) {
                    if($index = strpos($string, '>>')) {
                        return substr($string, 0, $index);
                    }
                    return $string;
                }
                $decrypt_data = kernel::single('ome_security_router',$shop['node_type'])->decrypt(array (
                    $data['field_type']    => $data['origin'],
                    'shop_id'     => $data['shop_id'],
                    'order_bn'    => $data['origin_bn'],
                ), $data['type']);
                if($decrypt_data[$data['field_type']]) {
                    $string = $decrypt_data[$data['field_type']];
                }
            }
            if($index = strpos($string, '>>')) {
                return substr($string, 0, $index);
            }
        } else {
            if($data['shop_id']) {
                $shop = app::get('ome')->model('shop')->db_dump(['shop_id'=>$data['shop_id']], 'node_type');
                if(in_array($shop['node_type'], $this->encrypt_node_type)) {
                    return kernel::single('base_view_helper')->modifier_cut($string,-1,'****',false,true);
                }
            }
        }

        return $string;
    }
}