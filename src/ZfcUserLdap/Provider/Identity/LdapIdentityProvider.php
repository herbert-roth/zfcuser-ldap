<?php

/**
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 *
 * @author Will Hattingh <w.hattingh@nitecon.com>
 *
 *
 */

namespace ZfcUserLdap\Provider\Identity;

use BjyAuthorize\Exception\InvalidRoleException;
use Zend\Permissions\Acl\Role\RoleInterface;

class LdapIdentityProvider implements \BjyAuthorize\Provider\Identity\ProviderInterface
{

    /**
     * @var \Zend\Authentication\AuthenticationService
     */
    protected $authService;

    /**
     * @var string|\Zend\Permissions\Acl\Role\RoleInterface
     */
    protected $defaultRole;
    protected $config;
    protected $bjyConfig;

    /**
     * @param \ZfcUser\Service\User    $userService
     * @param array $config;
     * @param array $bjyConfig;
     */
    public function __construct($authService, array $config, array $bjyConfig)
    {

        $this->authService = $authService;
        $this->config = $config;
        $this->bjyConfig = $bjyConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentityRoles()
    {
        $definedRoles = $this->bjyConfig['role_providers']['BjyAuthorize\Provider\Role\Config']['user']['children'];
        $roleKey = $this->config['identity_providers']['ldap_role_key'];


        if (!$this->authService->hasIdentity()) {
            return array($this->getDefaultRole());
        }

        $userRoles = $this->authService->getIdentity()->getRoles();

        if (is_null($userRoles) || !is_array($userRoles)) {
            return array($this->getDefaultRole());
        }

        $roles = array('user');

        foreach ($userRoles as $role) {
            if (isset($definedRoles[$role])) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * @return string|\Zend\Permissions\Acl\Role\RoleInterface
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * @param string|\Zend\Permissions\Acl\Role\RoleInterface $defaultRole
     *
     * @throws \BjyAuthorize\Exception\InvalidRoleException
     */
    public function setDefaultRole($defaultRole)
    {
        if (!($defaultRole instanceof RoleInterface || is_string($defaultRole))) {
            throw InvalidRoleException::invalidRoleInstance($defaultRole);
        }

        $this->defaultRole = $defaultRole;
    }
}
