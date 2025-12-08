<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_shop extends dbeav_model
{
    var $export_name = '店铺';
    var $has_export_cnf = true;

    static $restore = false;

    /**
     * 快速查询店铺信息
     * @access public
     * @param mixed $shop_id 店铺ID
     * @param String $cols 字段名
     * @return Array 店铺信息
     */
    public function getRow($filter, $cols = '*')
    {
        if (empty($filter)) {
            return array();
        }

        $shop = $this->dump($filter, $cols);
        if ($shop) {
            return $shop;
        } else {
            return false;
        }
    }

    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        $where = ' 1 ';
        if (isset($filter['shop_bn_in'])) {
            $where .= ' AND shop_bn IN ' . $filter['shop_bn_in'];
            unset($filter['shop_bn_in']);
        }
    
        if (isset($filter['user_org_id'])) {
            if ($filter['user_org_id'] && is_array($filter['user_org_id'])) {
                $where .= '  AND org_id IN ("' . implode('","', $filter['user_org_id']) . '")';
            }
            unset($filter['user_org_id']);
        }

        return parent::_filter($filter, $tableAlias, $baseWhere) . " AND " . $where;;
    }

    public function gen_id($shop_bn)
    {
        if (empty($shop_bn)) {
            return false;
        } else {
            $shop_id = md5($shop_bn);
            if ($this->db->selectrow("SELECT shop_id FROM sdb_ome_shop WHERE shop_id='" . $shop_id . "'")) {
                return false;
            } else {
                return $shop_id;
            }
        }
    }

    public function save(&$data, $mustUpdate = null)
    {

        if (isset($data['config']) && is_array($data['config'])) {
            $config = $data['config'];
            if ($config['password']) {
                $config['password'] = $this->aes_encode($config['password']);
            }

            unset($data['config']);
            $data['config'] = serialize($config);
        }
        $data['active'] = 'true';

        if (self::$restore) {
            return parent::save($data, $mustUpdate);
        } else {
            if (!$data['shop_id']) {
                $shop_id = $this->gen_id($data['shop_bn']);
                if ($shop_id) {
                    $data['shop_id'] = $shop_id;
                } else {
                    return false;
                }
                parent::save($data, $mustUpdate);
                return true;
            } else {
                return parent::save($data, $mustUpdate);
            }
        }
    }

    public function insert(&$data)
    {
        if (parent::insert($data)) {
            foreach (kernel::servicelist('ome_shop_ex') as $name => $object) {
                if (method_exists($object, 'insert')) {
                    $object->insert($data);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function update($data, $filter = array(), $mustUpdate = null)
    {
        if (parent::update($data, $filter, $mustUpdate)) {
            foreach (kernel::servicelist('ome_shop_ex') as $name => $object) {
                if (method_exists($object, 'update')) {
                    $object->update($data);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function delete($filter, $subSdf = 'delete')
    {
        if (parent::delete($filter)) {
            foreach (kernel::servicelist('ome_shop_ex') as $name => $object) {
                if (method_exists($object, 'delete')) {
                    $object->delete($filter);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    //店铺类型
    public function modifier_shop_type($row)
    {
        $tmp = ome_shop_type::get_shop_type();
        return $tmp[$row];
    }

    public function pre_recycle($data)
    {
        $filter = $data;
        unset($filter['_finder']);
        if ($data['isSelectedAll'] == '_ALL_') {
            $shop = $this->getList('shop_id', $filter);
            foreach ($shop as $v) {
                $shop_id[] = $v['shop_id'];
            }
        } else {
            $shop_id = $data['shop_id'];
        }
        if ($data) {
            $orderObj = app::get('ome')->model('orders');
            $relation = app::get('ome')->getConf('shop.branch.relationship');
            foreach ($data as $key => $val) {
                //判断是否已绑定，否则无法删除
                if ($val['node_id']) {
                    $this->recycle_msg = '店铺:' . $val['name'] . '已绑定，无法删除!';
                    return false;
                }
                //查看是否有订单
                $order_count = $orderObj->count(array('shop_id' => $val['shop_id']));
                if ($order_count > 0) {
                    $this->recycle_msg = '店铺:' . $val['name'] . '已有相关订单,不可以删除!';
                    return false;
                }
                unset($relation[$val['shop_bn']]);
            }

            app::get('ome')->setConf('shop.branch.relationship', $relation);
        }
        return true;
    }

    public function pre_delete($shop_id)
    {
        return true;
    }

    public function pre_restore($shop_id)
    {
        self::$restore = true;
        return true;
    }

    public function aes_encode($str)
    {
        $aes  = kernel::single('ome_aes', true); // 把加密后的字符串按十六进制进行存储
        $key  = kernel::single("base_certificate")->get('token'); // 密钥
        $keys = $aes->makeKey($key);

        $ct = $aes->encryptString($str, $keys);
        return $ct;
    }

    public function aes_decode($str)
    {
        $aes  = kernel::single('ome_aes', true); // 把加密后的字符串按十六进制进行存储
        $key  = kernel::single("base_certificate")->get('token'); // 密钥
        $keys = $aes->makeKey($key);

        $dt = $aes->decryptString($str, $keys);

        return $dt;
    }

    public function searchOptions()
    {
        return array(

        );
    }

    /**
     * 返回店铺类型
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function getShoptype($shop_id)
    {
        $shop      = $this->dump($shop_id);
        $shop_type = $shop['shop_type'];
        if ($shop_type == 'taobao') {
            if (strtoupper($shop['tbbusiness_type']) == 'B') {
                $shop_type = 'tmall';
            }
        }
        return $shop_type;

    }

    /**
     * 返回店铺信息
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function getShopInfo($shop_id)
    {
        $shop = $this->dump($shop_id);
        if ($shop['shop_type'] == 'taobao') {
            if (strtoupper($shop['tbbusiness_type']) == 'B') {
                $shop['shop_type'] = 'tmall';
            }
        }
        return $shop;

    }

    public function get_taobao_name()
    {
        $shop = $this->getList('name', array('node_type' => 'taobao'));
        if ($shop) {
            foreach ($shop as $key => $val) {
                $arrName[] = $val['name'];
            }
            return implode(',', $arrName);
        } else {
            return false;
        }
    }

    /**
     * 因店铺不常变，故静态获取店铺
     *
     * @return void
     * @author
     **/
    public function getShopById($shop_id)
    {
        static $shops;

        if ($shops[$shop_id]) {
            return $shops[$shop_id];
        }

        $shops[$shop_id] = $this->db_dump($shop_id);

        return $shops[$shop_id];
    }
}
