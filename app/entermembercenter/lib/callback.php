<?php
/**
 * Shopex OMS
 * 
 * @copyright Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * @license   Apache-2.0 with additional terms (See LICENSE file)
 */


class entermembercenter_callback
{
    /**
     * AES 解密方法
     * 
     * @param string $encryptedData 加密的数据（base64编码）
     * @param string $key 密钥
     * @return string|false 解密后的数据，失败返回false
     */
    private function aesDecrypt($encryptedData, $key)
    {
        // 解码 base64
        $encrypted = base64_decode($encryptedData);
        if ($encrypted === false) {
            return false;
        }
        
        // 提取 IV（前16字节）和加密数据
        $ivLength = 16;
        if (strlen($encrypted) < $ivLength) {
            return false;
        }
        
        $iv = substr($encrypted, 0, $ivLength);
        $ciphertext = substr($encrypted, $ivLength);
        
        // 准备密钥（AES-256需要32字节密钥）
        $keyLength = 32;
        if (strlen($key) > $keyLength) {
            $key = substr($key, 0, $keyLength);
        } elseif (strlen($key) < $keyLength) {
            $key = str_pad($key, $keyLength, "\0");
        }
        
        // AES-256-CBC 解密
        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    public function auth()
    {
        // 获取系统保存的密钥
        $saved_key = app::get('entermembercenter')->getConf('auth.key');
        if (!$saved_key) {
            echo json_encode(array(
                'rsp' => 'fail',
                'msg' => 'Auth key not configured'
            ));
            exit;
        }
        
        // 读取请求body中的加密数据
        $encryptedData = file_get_contents('php://input');
        if (empty($encryptedData)) {
            echo json_encode(array(
                'rsp' => 'fail',
                'msg' => 'Encrypted data is required'
            ));
            exit;
        }
        
        // AES解密（使用系统保存的密钥）
        $decryptedData = $this->aesDecrypt($encryptedData, $saved_key);
        if ($decryptedData === false) {
            echo json_encode(array(
                'rsp' => 'fail',
                'msg' => 'Decryption failed'
            ));
            exit;
        }
        
        // 解析JSON数据
        $data = json_decode($decryptedData, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            echo json_encode(array(
                'rsp' => 'fail',
                'msg' => 'Invalid JSON data: ' . json_last_error_msg()
            ));
            exit;
        }
        
        // 验证必需参数是否存在
        $requiredFields = array('ent_id', 'node_id', 'certificate_id', 'token');
        $missingFields = array();
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }
        if (!empty($missingFields)) {
            echo json_encode(array(
                'rsp' => 'fail',
                'msg' => 'Missing required parameters: ' . implode(', ', $missingFields)
            ));
            exit;
        }
        
        // 检查企业信息，只更新不存在的字段
        $currentEntId = base_enterprise::ent_id();
        $currentEntAc = base_enterprise::ent_ac();
        $currentEntEmail = base_enterprise::ent_email();
        
        $arr_enterprise = array();
        $needUpdate = false;
        
        // ent_id 必填，如果当前为空则更新
        if (empty($currentEntId)) {
            $arr_enterprise['ent_id'] = $data['ent_id'];
            $needUpdate = true;
        } else {
            $arr_enterprise['ent_id'] = $currentEntId;
        }
        
        // ent_ac 可选，只有当传入的数据不为空且当前为空时才更新
        if (empty($currentEntAc) && !empty($data['ent_ac'])) {
            $arr_enterprise['ent_ac'] = $data['ent_ac'];
            $needUpdate = true;
        } else {
            $arr_enterprise['ent_ac'] = $currentEntAc;
        }
        
        // ent_email 可选，只有当传入的数据不为空且当前为空时才更新
        if (empty($currentEntEmail) && !empty($data['ent_email'])) {
            $arr_enterprise['ent_email'] = $data['ent_email'];
            $needUpdate = true;
        } else {
            $arr_enterprise['ent_email'] = $currentEntEmail;
        }
        
        // 如果有字段需要更新，才执行更新
        if ($needUpdate) {
            base_enterprise::set_enterprise_info($arr_enterprise);
        }
        
        // 检查证书信息，只更新不存在的字段
        $currentCertId = base_certificate::certi_id();
        $currentToken = base_certificate::token();
        
        $certificate = array();
        if (empty($currentCertId)) {
            $certificate['certificate_id'] = $data['certificate_id'];
        } else {
            $certificate['certificate_id'] = $currentCertId;
        }
        
        if (empty($currentToken)) {
            $certificate['token'] = $data['token'];
        } else {
            $certificate['token'] = $currentToken;
        }
        
        // 如果有字段需要更新，才执行更新
        if (empty($currentCertId) || empty($currentToken)) {
            if (!base_certificate::set_certificate($certificate)) {
                echo json_encode(array(
                    'rsp' => 'fail',
                    'msg' => 'Failed to set certificate'
                ));
                exit;
            }
        }
        
        // 检查节点ID，如果不存在才更新
        // 获取需要设置 node_id 的应用ID
        $app_exclusion = app::get('base')->getConf('system.main_app');
        $app_id = $app_exclusion['app_id'];
        $currentNodeId = base_shopnode::node_id($app_id);
        
        if (empty($currentNodeId)) {
            $nodeData = array(
                'node_id' => $data['node_id'],
            );
            if (!base_shopnode::set_node_id($nodeData, $app_id)) {
                echo json_encode(array(
                    'rsp' => 'fail',
                    'msg' => 'Failed to set node_id'
                ));
                exit;
            }
        }

        echo json_encode(array(
            'rsp' => 'succ',
            'msg' => 'Enterprise account activated successfully'
        ));
        exit;
    }
}