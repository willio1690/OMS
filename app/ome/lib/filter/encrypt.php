<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/2/4 11:19:46
 * @describe: 类
 * ============================
 */
class ome_filter_encrypt
{

    /**
     * encrypt
     * @param mixed $filter filter
     * @param mixed $encryptCols encryptCols
     * @param mixed $tPre tPre
     * @param mixed $source source
     * @return mixed 返回值
     */

    public function encrypt(&$filter, $encryptCols, $tPre, $source)
    {
        ///////////////////////////
        // 加密处理逻辑 2017/5/5 by cp //
        ///////////////////////////
        // if ($filter['no_encrypt']) {
        //     return array();
        // }
        $baseWhere = array();
        foreach ($filter as $key => $value) {
            $pos   = strpos($key, '|');
            $field = false !== $pos ? substr($key, 0, $pos) : $key;

            $encrypt_type = $encryptCols[$field];
            if ($encrypt_type) {
                $searchtype = false !== $pos ? substr($key, $pos + 1) : 'nequal';
                $encrypt_list = [];

                if ($searchtype != 'nequal' && in_array($encrypt_type, array('search', 'nick', 'receiver_name'))) {
                    $encryptVal = kernel::single('ome_security_factory')->search($value, $encrypt_type);
                } else {
                    $encryptVal = kernel::single('ome_security_factory')->encryptPublic($value, $encrypt_type, true);
                }

                $encrypt_list[] = $originalVal = utils::addslashes_array($value);
                $encrypt_list[] = $encryptVal  = utils::addslashes_array($encryptVal);

                switch ($searchtype) {
                    case 'has':
                        $baseWhere[] = "({$tPre}{$field} LIKE '%" . $originalVal . "%' || {$tPre}{$field} LIKE '%" . $encryptVal . "%')";
                        break;
                    case 'head':
                        $baseWhere[] = "({$tPre}{$field} LIKE '" . $originalVal . "%' || {$tPre}{$field} LIKE '%" . $encryptVal . "%')";
                        break;
                    case 'foot':
                        $baseWhere[] = "({$tPre}{$field} LIKE '%" . $originalVal . "' || {$tPre}{$field} LIKE '%" . $encryptVal . "%')";
                        break;
                    default:
                        $baseWhere[] = "{$tPre}{$field} IN('" . implode("','", $encrypt_list) . "')";
                        break;
                }

                unset($filter[$key]);
            }
        }
        return $baseWhere;
    }

    /**
     * 获取BuyerOpenUid
     * @param mixed $uname uname
     * @return mixed 返回结果
     */
    public function getBuyerOpenUid($uname) {
        $return = [];
        $shopList = app::get('ome')->model('shop')->getList('shop_id,node_id', array(
            'filter_sql' => '{table}node_id is not null and {table}node_id !=""',
            'node_type'  => 'taobao',
        ));
        foreach ($shopList as $shop) {
            $result = kernel::single('erpapi_router_request')->set('shop',$shop['shop_id'])->member_getOuid($uname);
            $return = array_merge($return, $result);
            break;
        }
        return $return;
    }
}
