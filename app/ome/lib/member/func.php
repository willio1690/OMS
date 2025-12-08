<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_member_func {

	/**
     * 更新订单会员信息
     * @access public
     * @param Array $member_info 会员信息
     * @param String $shop_id 店铺ID
     * @param Number $old_member_id 订单会员ID
     * @param Array $old_member 更新前的会员信息
     * @return int 会员ID
     */
    public function save($member_info,$shop_id='',$old_member_id='',&$old_member=array()){

        if (empty($member_info)) return null;
        $membersObj = app::get('ome')->model('members');
        
        $addressObj = app::get('ome')->model('member_address');
        $oFunc = kernel::single('eccommon_regions');

        if (!isset($member_info['area'])){
            if($member_info['area_state']){
                $area = $member_info['area_state'].'/'.$member_info['area_city'].'/'.$member_info['area_district'];
                $oFunc->region_validate($area);
                $member_info['area'] = $area;
            }
            
        }
        $shopex_shop_type = ome_shop_type::shopex_shop_type();
        if ($old_member_id){
            $md5_field = array('uname','name','area','addr','phone','mobile','telephone','email','zipcode');
            $old_member_info = $membersObj->getRow($old_member_id);
            $old_member = $old_member_info;
            $update_flag = false;
            foreach($md5_field as $sdf=>$field){
                $compre_value = trim($member_info[$field]);
                if (empty($compre_value)) continue;
                if ($member_info[$field] != $old_member_info[$field]){
                    $update_flag = true;
                }
            }
            if ($update_flag == false){
                return $old_member_id;
            }
        }

        if (empty($member_info['name'])) $member_info['name'] = $member_info['uname'];
        $member_detail = array();
        $member_id = null;
        

        if($member_info['uname']){
            $member_info['buyer_open_uid'] = $member_info['buyer_open_uid'] ? : '';
            //判断是否存在该会员
            if(!$member_info['member_id']){
                if($member_info['shop_type']){
                    if(in_array($member_info['shop_type'],$shopex_shop_type)){
                        $member_detail = $membersObj->dump(array('uname'=>$member_info['uname'],'shop_id'=>$shop_id,'shop_type'=>$member_info['shop_type']), 'member_id');
                    }else{
                        $member_detail = $membersObj->dump(array('uname'=>$member_info['uname'],'shop_type'=>$member_info['shop_type'],'buyer_open_uid'=>$member_info['buyer_open_uid']), 'member_id');
                    } 
                }else{
                    $member_detail = $membersObj->dump(array('uname'=>$member_info['uname']), 'member_id');
                }
                
                
            }else{
                $member_detail['member_id'] = $member_info['member_id'];
            }
            
            $area = $member_info['area'];
            $area = str_replace('::','',$area);
            $shop_area = '';
            if($member_info['consignee']['area_state']){
                $shop_area = $member_info['consignee']['area_state'].'/'.$member_info['consignee']['area_city'].'/'.$member_info['consignee']['area_district'];
                $oFunc->region_validate($shop_area);
                $shop_area = str_replace('::','',$shop_area);
            }
            
            
            $members_data = array(
                'account' => array(
                    'uname' => $member_info['uname'],
                ),
                'contact' => array(
                    'name' => $member_info['name'],
                    'area' => $area,
                    'addr' => $member_info['addr'],
                    'phone' => array(
                        'mobile' => $member_info['mobile'],
                        'telephone' => $member_info['tel'],
                        ),
                    'email' => $member_info['email'],
                    'zipcode' => $member_info['zip'],
                ),
                'buyer_open_uid' =>  $member_info['buyer_open_uid'],
                'shop_type' =>  $member_info['shop_type'],
                'uname_md5' => md5($member_info['uname']), //md5用户名
            );
            
            if(in_array($member_info['shop_type'],$shopex_shop_type)){
                $members_data['shop_id'] = $shop_id;
            }
            $shop_members_data = array(
                'ship_name'     => $member_info['consignee']['name'] ? $member_info['consignee']['name'] : $member_info['name'],
                'ship_area'     => $shop_area ? $shop_area : $area,
                'ship_addr'     => $member_info['consignee']['addr'] ? $member_info['consignee']['addr'] : $member_info['addr'] ,
                'ship_mobile'   => $member_info['consignee']['mobile'] ? $member_info['consignee']['mobile'] : $member_info['mobile'],
                'ship_tel'      => $member_info['consignee']['telephone'] ? $member_info['consignee']['telephone'] : $member_info['tel'],
                'ship_zip'      => $member_info['consignee']['zip'] ? $member_info['consignee']['zip'] : $member_info['zip'],
                'ship_email'    => $member_info['consignee']['email'] ? $member_info['consignee']['email'] : $member_info['email'],
                
            );
            if (empty($member_detail['member_id'])){
                
                $membersObj->save($members_data);
                $member_detail['member_id'] = $members_data['member_id'];
            }else{
                unset($members_data['shop_id'],$members_data['shop_type'],$members_data['account']['uname']);

                $members_data = array_merge($members_data, array('member_id'=>$member_detail['member_id']));
                $membersObj->save($members_data);
                
            }
            $member_id = $member_detail['member_id'];
            if($member_id){
                $shop_members_data['member_id'] = $member_id;
                $addressObj->create_address($shop_members_data);
            }
            
            
        }

        return $member_id;
    }

}