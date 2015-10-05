<?php

namespace ZfcUserLdap\Adapter;

use Zend\Ldap\Ldap as ZendLdap;

class ActiveDirectory extends AbstractAdapter implements AdapterInterface
{
    public function __construct($config, $logger, $log_enabled)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->log_enabled = $log_enabled;
        $this->scope = ZendLdap::SEARCH_SCOPE_SUB;
    }

    public function findByUsername($username)
    {
        return $this->find('username', $username, 'sAMAccountName');
    }

    public function findByEmail($email)
    {
        return $this->find('email', $email, 'email');
    }

    public function findById($id)
    {
        return $this->find('uid', $id, 'uidnumber');
    }

    public function getUsername()
    {
        return $this->user['samaccountname']['0'];
    }

    public function getDisplayName()
    {
        return $this->user['givenname']['0'] . ' ' . $this->user['sn']['0'];
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

        foreach ($this->user[$role_key] as $grp) {
            $role = substr($grp, 3, strpos($grp, ',') - 3);
//            var_dump($role);
            if (in_array($role, $usable_roles)) {
                $roles[] = $role;
            }
        }
        
        $this->log(sprintf('getRoles() returned %s roles for username=%s',
                           count($roles), $this->getUsername()), 7);
        return $roles;
    }
}
