<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_member_address extends dbeav_model{

    /**
     * 创建_address
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function create_address($data){
        $member_id = $data['member_id'];
        if($member_id){
            $address_hash = sprintf('%u',crc32($data['ship_name'].'-'.$data['ship_area'].$data['ship_addr'].'-'.$data['ship_mobile'].'-'.$data['ship_tel'].'-'.$data['ship_zip'].'-'.$data['ship_email']));
            $data['address_hash'] = $address_hash;
            $address_detail = $this->dump(array('address_hash'=>$address_hash,'member_id'=>$member_id),'address_id');
            if(!$address_detail['address_id']){
                $result = $this->save($data);
            }
            
            if($data['is_default'] == '1' && $data['address_id']){
                $this->db->exec("UPDATE sdb_ome_member_address SET is_default='0' WHERE member_id=".$data['member_id']." AND address_id!=".$data['address_id']);
                $this->db->exec("UPDATE sdb_ome_members SET  area='".$data['ship_area']."',addr='".$data['ship_addr']."',mobile='".$data['ship_mobile']."',tel='".$data['ship_tel']."',email='".$data['ship_email']."', zip='".$data['ship_zip']."' WHERE member_id=".$data['member_id']);
            }
        }
        
    }
}
