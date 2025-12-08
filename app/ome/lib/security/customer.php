<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_security_customer
{

    function check_sensitive_info(&$rows, $type, $op_id){
        //检查操作员数据权限,如果有的话，直接跳出不做处理
        if($this->has_permission($op_id, 'customer_sensitive_info')){
            return true;
        }else{
            switch($type){
                case 'ome_mdl_orders':
                case 'archive_mdl_orders':
                case 'ome_mdl_delivery':
                case 'wms_mdl_delivery':
                case 'omedlyexport_mdl_ome_delivery':
                    $encrypt_mapping = array(
                        'ship_mobile' => 'ship_mobile',
                        'ship_tel'        => 'ship_tel',
                        'ship_name'   => 'ship_name',
                        'ship_addr'     => 'ship_addr',
                    );
                    break;
                case 'sales_mdl_sales':
                    $encrypt_mapping = array(
                        'column_ship_mobile' => 'ship_mobile',
                        'column_ship_tel'        => 'ship_tel',
                        'column_ship_name'   => 'ship_name',
                        'column_ship_addr'     => 'ship_addr',
                    );
                    break;
                default:
                    return false;
                    break;
            }

            //获取需要处理的字段
            $encrypt_fields = array_keys($encrypt_mapping);
            if($rows){
                foreach($rows as $k => &$row){
                    if($encrypt_fields){
                        foreach($encrypt_fields as $field){
                            if(isset($row[$field])){
                                $func = sprintf('encrypt_%s',$encrypt_mapping[$field]);
                                $this->$func($row[$field]);
                            }
                        }
                    }
                }
                return true;
            }else{
                return false;
            }
        }
    }

    #检查当前管理员是否有客户敏感信息权限
    function has_permission($op_id, $authority_id)
    {
        //没有对应的操作员即无权限
        if(!$op_id){return false;}

        $usersObj = app::get('desktop')->model('users');
        $hasroleObj = app::get('desktop')->model('hasrole');
        $rolesObj = app::get('desktop')->model('roles');

        $userInfo = $usersObj ->dump($op_id,'*',array( ':account@pam'=>array('*') ));
        if($userInfo){
            //如果是超管认定为有权限
            if($userInfo['super']){
                return true;
            }else{
                //取当前管理员对应的角色以及角色绑定的数据权限
                $sdf = $hasroleObj->getList('role_id',array('user_id'=>$userInfo['user_id']));
                $pass = array();
                foreach($sdf as $val){
                    $pass[] = $rolesObj->dump($val,'data_authority');
                }

                $group = array();
                foreach($pass as $key){
                    $work = unserialize($key['data_authority']);
                    if($work){
                        foreach($work as $val){
                            $group[] = $val;
                        }
                    }
                }

                if(in_array($authority_id,$group)){
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
    }


    //加密收货人信息
    function encrypt_ship_name(&$value){
        $value = preg_replace('/([\x{4e00}-\x{9fa5}]{1})[\x{4e00}-\x{9fa5}]{1,4}/iu', '$1**', $value);
    }

    //加密收货人地址信息
    function encrypt_ship_addr(&$value){
        $value = preg_replace('/(\d{1,})/', '*', $value);
    }

    //加密收货人手机
    function encrypt_ship_mobile(&$value){
        $value = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $value);
    }

    //加密收货人固话
    function encrypt_ship_tel(&$value){
        $value = preg_replace('/(\w{0,4}\-?\w{2})\w{4}(\w{2})/', '$1****$2', $value);
    }
}