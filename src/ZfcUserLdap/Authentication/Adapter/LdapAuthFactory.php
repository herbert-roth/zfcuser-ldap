<?php

namespace ZfcUserLdap\Authentication\Adapter;


use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LdapAuthFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     *
     * @return LdapAuth
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $adapter = new LdapAuth();
        $adapter->setServiceManager($serviceLocator);
        return $adapter;
    }
}