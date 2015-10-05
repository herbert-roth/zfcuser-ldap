<?php

namespace ZfcUserLdap\Mapper;

use ZfcUser\Mapper\User as AbstractUserMapper;
use ZfcUser\Mapper\UserInterface;
use ZfcUserLdap\Mapper\UserHydrator as HydratorInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

class User extends AbstractUserMapper implements UserInterface, ServiceManagerAwareInterface
{

    protected $tableName = 'user';

    public function findByEmail($email)
    {
        $select = $this->getSelect()->where(array('email' => $email));
        $entity = $this->select($select, $this->getEntity(), new HydratorInterface())->current();

        if (is_object($entity) && strlen($entity->getUsername()) > 0) {
            $this->getEventManager()->trigger('find', $this, array('entity' => $entity));
        }

        /* Now we select again so that it provides us with the ID as well
         * as assurance that the user made it into the database
         */
        $selectVerfify = $this->getSelect()->where(array('email' => $email));
        $verifiedEntity = $this->select($selectVerfify, $this->getEntity(), new HydratorInterface())->current();
        $this->getEventManager()->trigger('find', $this, array('entity' => $verifiedEntity));

        return $entity;
    }

    public function findByUsername($username)
    {
        $select = $this->getSelect()->where(array('username' => $username));
        $entity = $this->select($select, $this->getEntity(), new HydratorInterface())->current();

        if (is_object($entity) && strlen($entity->getUsername()) > 0) {
            $this->getEventManager()->trigger('find', $this, array('entity' => $entity));
        }

        /* Now we select again so that it provides us with the ID as well
         * as assurance that the user made it into the database
         */
        $selectVerfify = $this->getSelect()->where(array('username' => $username));
        $verifiedEntity = $this->select($selectVerfify, $this->getEntity(), new HydratorInterface())->current();
        $this->getEventManager()->trigger('find', $this, array('entity' => $verifiedEntity));

        return $entity;
    }

    public function findById($id)
    {
        $select = $this->getSelect()->where(array('user_id' => $id));
        $entity = $this->select($select)->current();
        $this->getEventManager()->trigger('find', $this, array('entity' => $entity));

        return $entity;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function insert($entity, $tableName = null, HydratorInterface $hydrator = null)
    {
        $result = parent::insert($entity, $tableName, $hydrator);
        $entity->setId($result->getGeneratedValue());
        return $result;
    }

    public function update($entity, $where = null, $tableName = null, HydratorInterface $hydrator = null)
    {
        if (!$where) {
            $where = 'user_id = ' . $entity->getId();
        }

        return parent::update($entity, $where, $tableName, $hydrator);
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ServiceManager $locator
     * @return void
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getEntity()
    {
        $options = $this->getServiceManager()->get('zfcuser_module_options');
        $entityClass = $options->getUserEntityClass();
        return new $entityClass;
    }

    /*
     * Creates a new User Entity
     *
     * @return User Entity
     */

    public function newEntity($ldapObject)
    {
        $adapter = $this->serviceManager->get('ZfcUserLdap\LdapAdapter');
        $adapter->setUserArray($ldapObject);

        $zulConfig = $this->serviceManager->get('ZfcUserLdap\Config');
        $role_key = $zulConfig['identity_providers']['ldap_role_key'];
        $usable_roles = $zulConfig['identity_providers']['usable_roles'];

        $entity = $this->getEntity();
        $username = $adapter->getUsername();

        if (isset($username)) {
            $entity->setUsername($username);
            $entity->setDisplayName($adapter->getDisplayName());
            $entity->setEmail($adapter->getEmail());
            $entity->setPassword(md5('HandledByLdap'));
            $entity->setRoles($adapter->getRoles($role_key, $usable_roles));
        }

        return $entity;
    }

    /**
     * Insert or Update DB entry depending if a User Object is set.
     *
     * @return User Entity
     */
    public function updateDb($ldapObject, $userObject)
    {
        $adapter = $this->serviceManager->get('ZfcUserLdap\LdapAdapter');
        $adapter->setUserArray($ldapObject);

        $zulConfig = $this->serviceManager->get('ZfcUserLdap\Config');
        $role_key = $zulConfig['identity_providers']['ldap_role_key'];
        $usable_roles = $zulConfig['identity_providers']['usable_roles'];

        if ($userObject == null) {
            $entity = $this->getEntity();
        } else {
            $entity = $userObject;
        }

        $username = $adapter->getUsername();

        if (isset($username)) {
            $entity->setUsername($username);
            $entity->setDisplayName($adapter->getDisplayName());
            $entity->setEmail($adapter->getEmail());
            $entity->setPassword(md5('HandledByLdap'));
            $entity->setRoles($adapter->getRoles($role_key, $usable_roles));

            if ($userObject == null) {
//                $entity->setState(1);
                $this->insert($entity, $this->tableName, new HydratorInterface());
            } else {
                $this->update($entity, null, $this->tableName, new HydratorInterface());
            }
        }
        return $entity;
    }
}
