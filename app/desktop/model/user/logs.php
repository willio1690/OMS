<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @2016-12-23
 * @mdl_user_logs
 */
class desktop_mdl_user_logs extends dbeav_model{
    
    //1-添加用户
    /**
     * 添加User
     * @param mixed $user user
     * @return mixed 返回值
     */

    public function addUser($user){
        unset($user['re_password']);

        $op_id = kernel::single('desktop_user')->get_id();
        $op_name = kernel::single('desktop_user')->get_name();

        $data = array(
            'obj_name'         => $user['name'],                                        # 被操作人姓名
            'obj_id'           => $user['user_id'],                                     # 被操作人ID
            'op_id'            => $op_id ? $op_id : 16777215,                          # 操作人ID
            'op_name'          => $op_name ? $op_name : 'system',                       # 操作人姓名
            'operation_time'   => time(),                                               # 操作时间
            'operation_type'   => 1,                                                    # 操作类型
            'operation_detail' => serialize($user),                         # 快照
            'ip'               => kernel::single('base_request')->get_remote_addr(),    # 远程ID
        );
        $this->insert($data);
    }
    
    //2-信息编辑
    /**
     * userEdit
     * @param mixed $user user
     * @return mixed 返回值
     */
    public function userEdit($user){
        $op_id = kernel::single('desktop_user')->get_id();
        $op_name = kernel::single('desktop_user')->get_name();

        $data = array(
            'obj_name'         => $user['name'],                                        # 被操作人姓名
            'obj_id'           => $user['account_id'],                                     # 被操作人ID
            'op_id'            => $op_id ? $op_id : 16777215,                          # 操作人ID
            'op_name'          => $op_name ? $op_name : 'system',                       # 操作人姓名
            'operation_time'   => time(),                                               # 操作时间
            'operation_type'   => 2,                                                    # 操作类型
            'operation_detail' => serialize($user),
            'ip'               => kernel::single('base_request')->get_remote_addr(),    # 远程ID
        );
        $this->insert($data);
    }
    
    //3-删除用户
    /**
     * 删除User
     * @param mixed $users users
     * @return mixed 返回值
     */
    public function deleteUser($users){
        $strFields = array('obj_name','obj_id','op_id','op_name','operation_time','operation_type','ip');
        $strValues = array();
        
        $op_id = kernel::single('desktop_user')->get_id();
        $op_name = kernel::single('desktop_user')->get_name();
        
        foreach ($users as $user) {
            $v = array(
                $this->db->quote($user['name']),
                $this->db->quote($user['user_id']),
                $this->db->quote($op_id ? $op_id : 16777215),
                $this->db->quote($op_name ? $op_name : 'system'),
                time(),
                3,
                $this->db->quote(kernel::single('base_request')->get_remote_addr()),

            );

            $strValues[] = implode(',',$v);
        }

        $sql = sprintf('INSERT INTO `%s`(`%s`) VALUES (%s)',$this->table_name(true),implode('`,`',$strFields),implode('),(',$strValues));

        $this->db->exec($sql);
    }
    
    //4-修改密码
    /**
     * changePwd
     * @param mixed $user_id ID
     * @return mixed 返回值
     */
    public function changePwd($user_id){
        $user = $this->app->model('users')->dump($user_id,'name,user_id');

        $op_id = kernel::single('desktop_user')->get_id();
        $op_name = kernel::single('desktop_user')->get_name();

        $data = array(
            'obj_name'         => $user['name'],                                        # 被操作人姓名
            'obj_id'           => $user['user_id'],                                     # 被操作人ID
            'op_id'            => $op_id ? $op_id : 16777215,                          # 操作人ID
            'op_name'          => $op_name ? $op_name : 'system',                       # 操作人姓名
            'operation_time'   => time(),                                               # 操作时间
            'operation_type'   => 4,                                                    # 操作类型
            'operation_detail' => serialize(array('pam_account'=>array('login_password'=>$_POST['pam_account']['login_password']))),
            'ip'               => kernel::single('base_request')->get_remote_addr(),    # 远程ID
        );
        $this->insert($data);
    }
}
