<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*

*/

class omevirtualwms_mdl_dataStatus extends dbeav_model {

    /**
     * table_name
     * @param mixed $full full
     * @return mixed 返回值
     */
    public function table_name($full=null)
    {
        if ($full)

            return kernel::database()->prefix.'omevirtualwms_data_status';
        else

            return 'data_status';
    }

    /**
     * 保存
     * @param mixed $data 数据
     * @param mixed $mustUpdate mustUpdate
     * @return mixed 返回操作结果
     */
    public function save(&$data, $mustUpdate=null)
    {
        if (current($this->dump(array('bn'=>$data['bn'], 'type'=>$data['type']),'count(*) as cnt')) == 0)

            return $this->insert($data);
        else

            return $this->update($data,array('bn'=>$data['bn'], 'type'=>$data['type']));
    }

    /**
     * scanANDclean
     * @return mixed 返回值
     */
    public function scanANDclean()
    {
        /* $sql = 'delete from sdb_omevirtualwms_data_status where type=\'stockin\' and bn in ( select appropriation_bn from sdb_omestorage_appropriation where in_status not in (0,1) and otype=\'0\' )';
        $this->db->query($sql);

        $sql = 'delete from sdb_omevirtualwms_data_status where type=\'stockout\' and bn in ( select appropriation_bn from sdb_omestorage_appropriation where in_status not in (3,4) and otype=\'1\' )';
        $this->db->query($sql); */

        $sql = 'delete from sdb_omevirtualwms_data_status where type=\'reship\' and bn in ( select reship_bn from sdb_ome_reship where status!=\'ready\' and status!=\'progress\' )';
        $this->db->query($sql);

        $sql = 'delete from sdb_omevirtualwms_data_status where type=\'delivery\' and bn in ( select delivery_bn from sdb_ome_delivery where status!=\'ready\' and status!=\'accept\' and status!=\'progress\' )';
        $this->db->query($sql);
    }

}
