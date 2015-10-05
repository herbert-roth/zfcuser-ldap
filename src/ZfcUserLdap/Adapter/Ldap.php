<?php

namespace ZfcUserLdap\Adapter;

use Zend\Ldap\Ldap as ZendLdap;

class Ldap extends AbstractAdapter implements AdapterInterface
{
    public function __construct($config, $logger, $log_enabled)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->log_enabled = $log_enabled;
        $this->scope = ZendLdap::SEARCH_SCOPE_ONE;
}

    public function findByUsername($username)
    {
        return $this->find('username', $username, 'uid');
    }

    public function findByEmail($email)
    {
        return $this->find('email', $email, 'mail');
    }

    public function findById($id)
    {
        return $this->find('uid', $id, 'uidnumber');
    }

    public function getUsername()
    {
        return $this->user['uid']['0'];
    }

    public function getDisplayName()
    {
        return $this->user['cn']['0'];
    }

    public function getEmail()
    {
        return $this->user['mail']['0'];
    }

    public function getRoles($role_key, array $usable_roles)
    {
        $roles = array();

        if (empty($this->user[$role_key])) {
            return $roles;
        }

        foreach ($this->user[$role_key] as $role) {
            if (in_array($role, $usable_roles)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }
}
