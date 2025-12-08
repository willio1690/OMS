<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 不可逆加密字段处理
 */
class ome_security_hash
{
    protected $_order_encrypt          = array ();
    protected $_member_encrypt         = array ();
    protected $_delivery_encrypt       = array ();
    protected $_sales_delivery_encrypt = array ();
    protected $_invoice_encrypt        = array ();
    protected $_customs_encrypt        = array ();
    protected $_reship_encrypt         = array ();
    protected $_aftersale_encrypt      = array ();

    protected $_export_title = array (
        'orders' => array (
            'ship_mobile' => '收货人手机',
            'ship_tel'    => '收货人电话',
            'ship_addr'   => '收货地址',
            'order_bn'    => '订单号',
            'uname'       => '会员用户名',
            'ship_name'   => '收货人',
        ),
        'order_fail' => array (
            'ship_mobile' => '收货人手机',
            'ship_tel'    => '收货人电话',
            'ship_addr'   => '收货地址',
            'order_bn'    => '订单号',
            'uname'       => '会员用户名',
            'ship_name'   => '收货人',
        ),
        'ome_delivery' => array (
            'ship_mobile' => '*:收货人手机',
            'ship_tel'    => '*:收货人电话',
            'ship_addr'   => '*:收货地址',
            'order_bn'    => '*:订单号',
            'uname'       => '*:会员用户名',
            'ship_name'   => '*:收货人',
        ),
        'delivery' => array (
            'ship_mobile' => '收货人手机',
            'ship_tel'    => '收货人电话',
            'ship_addr'   => '收货地址',
            'order_bn'    => '订单号',
            'uname'       => '会员用户名',
            'ship_name'   => '收货人',
        ),
        'ome_goodsale' => array (
            'ship_mobile' => '*:收货人手机号',
            'ship_addr'   => '*:收货地址',
            'order_bn'    => '*:订单号',
            'ship_name'   => '*:收货人',
        ),
        'delivery_order_item' => array (
            'ship_mobile' => '收货人手机',
            'order_bn'    => '订单',
        ),
        'reship' => array (
            'ship_mobile' => '收货人手机',
            'ship_tel'    => '收货人电话',
            'ship_addr'   => '收货地址',
            'order_bn'    => '订单号',
            'uname'       => '会员名',
            'ship_name'   => '收货人',
        ),
    );

    protected function get_encrypt_fields($type)
    {
        switch ($type) {
            case 'order':
                return $this->_order_encrypt;break;
            case 'member':
                return $this->_member_encrypt;break;
            case 'delivery':
                return $this->_delivery_encrypt;break;
            case 'sales_delivery':
                return $this->_sales_delivery_encrypt;break;
            case 'invoice':
                return $this->_invoice_encrypt;break;
            case 'customs':
                return $this->_customs_encrypt;break;
            case 'reship':
                return $this->_reship_encrypt;break;
            case 'aftersale':
                return $this->_aftersale_encrypt;break;
        }

        return array ();
    }

    /**
     * 检查_encrypt
     * @param mixed $value value
     * @return mixed 返回验证结果
     */

    public function check_encrypt($value)
    {
        if ('false' != app::get('ome')->getConf('ome.sensitive.data.encrypt')) {
            return true;
        }

        if ($this->get_code() == substr($value, -5)) {
            return true;
        }

        return false;
    }

    /**
     * 数据展示
     * 
     * @return void
     * @author 
     * */
    public function show_encrypt($data,$type)
    {
        if ('false' != app::get('ome')->getConf('ome.sensitive.data.encrypt')) {
            return true;
        }

        return $this->is_encrypt($data,$type);
    }

    public function is_encrypt($data, $type)
    {
        foreach ((array)$this->get_encrypt_fields($type) as $c => $r) {

            $v = $data[$c]; if (!$v && $r['sdf']) $v = utils::array_path($data, $r['sdf']);

            if ($this->get_code() == substr($v, -5)) {
                return true;
            }
        }

        return false;
    }

    public function get_encrypt_origin($data, $type){
        $order_bn = $data['order_bn'];
        $order = kernel::database()->selectrow('select order_id from sdb_ome_orders where order_bn="'.$order_bn.'" and shop_id="'.$data['shop_id'].'"');
        if(empty($order)) {
            return array();
        }
        $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$order['order_id']], 'encrypt_source_data');
        if(empty($original)) {
            return array();
        }
        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);
        $tmpEncryptData = [];
        foreach ((array) $this->get_encrypt_fields($type) as $c => $r) {
            if ($this->get_code() == substr($data[$c], -5)) {
                $index = $r['jdkey'] ? $r['jdkey'] : $c;
                if($this->_original_fields[$index] && $encrypt_source_data[$this->_original_fields[$index]]) {
                    $tmpEncryptData[$c] = $encrypt_source_data[$this->_original_fields[$index]];
                }
            }
        }
        return $tmpEncryptData;
    }

        /**
     * decrypt
     * @param mixed $data 数据
     * @param mixed $type type
     * @return mixed 返回值
     */
    public function decrypt($data, $type)
    {
        if ($body = $this->get_encrypt_body($data, $type)) {
            $res = kernel::single('base_httpclient')->set_timeout(10)->post($body['url'], $body);
            $res = @json_decode($res,true);
            if ($res && $res['rsp'] == 'succ' && $res['data']) {
                foreach ((array)$res['data'][$data['order_bn']] as $k => $v) {
                    if (false !== $kk=array_search($k, (array)@json_decode($body['fields'], true))) {
                        $data[$kk] = $v;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 获取_encrypt_body
     * @param mixed $data 数据
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function get_encrypt_body($data, $type = 'order')
    {
        return array();
    }

    /**
     * 获取_code
     * @return mixed 返回结果
     */
    public function get_code()
    {
        return '@hash';
    }

    /**
     * export
     * @param mixed $buffer buffer
     * @param mixed $datatype 数据
     * @param mixed $shop_id ID
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function export($buffer, $datatype, $shop_id, $line = 1)
    {
        static $title,$is_detail;

        $encode = self::convert2utf8($buffer);

        if ($line == 1) {
            $title = str_getcsv($buffer);

            self::convert2gb2312($buffer);

            return $buffer;
        }

        if ('*:' == substr($buffer, 0, 2) || $is_detail == true) {
            $is_detail = true;

            self::convert2gb2312($buffer);

            return $buffer;
        }

        $buffer = str_getcsv($buffer);
        $c      = array_combine($title, $buffer);

        $ship_mobile = $c[$this->_export_title[$datatype]['ship_mobile']]; 
        $ship_tel    = $c[$this->_export_title[$datatype]['ship_tel']]; 
        $ship_addr   = $c[$this->_export_title[$datatype]['ship_addr']]; 
        $order_bn    = $c[$this->_export_title[$datatype]['order_bn']];
        $uname       = $c[$this->_export_title[$datatype]['uname']];
        $ship_name   = $c[$this->_export_title[$datatype]['ship_name']];

        if ($order_bn && ($ship_name || $ship_mobile || $ship_tel || $ship_addr || $uname) ) {
            $consignee = array (
                'ship_mobile' => $ship_mobile,
                'ship_tel'    => $ship_tel,
                'ship_addr'   => $ship_addr,
                'uname'       => $uname,
                'ship_name'   => $ship_name,
                'order_bn'    => $order_bn,
                'shop_id'     => $shop_id
            );

            $search = array('&nbsp;','"','&quot;',"\r\n","\r","\n",',');
            // 解密重载
            $consignee = $this->decrypt($consignee, 'order');
            if ($consignee['ship_mobile']) {
                $c[$this->_export_title[$datatype]['ship_mobile']] = $consignee['ship_mobile'];
            }
            if ($consignee['ship_tel']) {
                $c[$this->_export_title[$datatype]['ship_tel']] = $consignee['ship_tel'];
            }
            if ($consignee['ship_addr']) {
                $c[$this->_export_title[$datatype]['ship_addr']] = str_replace($search,'',$consignee['ship_addr']);
            }
            if ($consignee['uname']) {
                $c[$this->_export_title[$datatype]['uname']] = str_replace($search,'',$consignee['uname']);
            }
            if ($consignee['ship_name']) {
                $c[$this->_export_title[$datatype]['ship_name']] = str_replace($search,'',$consignee['ship_name']);
            }
        }

        $buffer = implode(',',$c)."\n";

        self::convert2gb2312($buffer);

        return $buffer;
    }

    public static function convert2utf8(&$content)
    {
        $encode = mb_detect_encoding($content, array("ASCII", "GB2312", "GBK", 'UTF-8'));
        if ('UTF-8' != $encode) {
            $content = mb_convert_encoding($content, 'UTF-8', $encode);
        }

        return $encode;
    }

    public static function convert2gb2312(&$content)
    {
        $encode = mb_detect_encoding($content, array("ASCII", "GB2312", "GBK", 'UTF-8'));

        if ('CP936' == $encode) {
            $content = mb_convert_encoding($content, 'GBK', 'UTF-8');
        } elseif ('GBK' != $encode) {
            $content = mb_convert_encoding($content, 'GBK', $encode);
        }

        return $encode;
    }

    /**
     * 检查_virtual_number
     * @param mixed $order_bn order_bn
     * @param mixed $mobile mobile
     * @param mixed $shop_id ID
     * @return mixed 返回验证结果
     */
    public function check_virtual_number($order_bn,$mobile,$shop_id)
    {
        return array (false,'非虚拟号');
    }

    /**
     * _log
     * @param mixed $logsdf logsdf
     * @param mixed $step step
     * @return mixed 返回值
     */
    public function _log($logsdf,$step='request')
    {
        $kafkaData = array(
            'title'       => '隐私解密',
            'method'      => $logsdf['method'],
            'original_bn' => $logsdf['tids'],
            'msg_id'      => '',
            'status'      => 'success',
            'createtime'  => time(),
            'spendtime'   => '',
            'data'        => $logsdf,
        );
        
        kernel::single('erpapi_log_elk')->write_log($kafkaData,$step);
    }

    /**
     * 允许解密额度限制
     *
     * @return void
     * @author 
     **/
    public function allowDecrypt($shop_id, $shop_type, $status = 'active')
    {
        $platform = [
            'luban' => [
                'limit'     => 50,
                'status'    => 'active',
            ]
        ];

        if (isset($platform[$shop_type])){
            $count = (int)cachecore::fetch("decrypt-count-{$shop_id}");

            if ($platform[$shop_type]['limit'] < $count || $platform[$shop_type]['status'] != $status){
                return false;
            }

            $count++;

            cachecore::store("decrypt-count-{$shop_id}", $count, 86400);
        }

        return true;
    }

    /**
     * 返回原始数据
     *
     * @return void
     * @author 
     **/
    public function getOriginText($text)
    {
        if ($this->get_code() == substr($text, -5)) {
            $text = substr($text, 0, -5);
        }

        if($index = strpos($text, '>>')) {
            return substr($text, 0, $index);
        }

        return $text;
    }
}