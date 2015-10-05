<?php

namespace ZfcUserLdap\Adapter;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\Ldap as AuthAdapter;
use Zend\Ldap\Exception\LdapException;
use Zend\Ldap\Ldap as ZendLdap;

class AbstractAdapter
{
    protected $config;

    /** @var Zend\Ldap\Ldap */
    protected $ldap;

    /**
     * Array of server configuration options, active server is
     * set to the first server that is able to bind successfully
     * @var array */
    protected $active_server;

    /**
     * An array of error messages.
     * @var array
     */
    protected $error = array();

    /**
     * Log writer
     * @var Zend\Log\Logger
     */
    protected $logger;

    /** @var bool */
    protected $log_enabled;

    /** @var int */
    protected $scope;

    /** @var array */
    protected $user = array();

    public function bind()
    {
        $options = $this->config;
        /* We will try to loop through the list of servers
         * if no active servers are available then we will use the error msg
         */
        foreach ($options as $server) {
            $this->log("Attempting bind with ldap");
            try {
                $this->ldap = new ZendLdap($server);
                $this->ldap->bind();
                $this->log("Bind successful setting active server.");
                $this->active_server = $server;
            } catch (LdapException $exc) {
                $this->error[] = $exc->getMessage();
                continue;
            }
        }
    }

    /**
     *
     * @param string $msg
     * @param int $priority EMERG=0, ALERT=1, CRIT=2, ERR=3, WARN=4, NOTICE=5, INFO=6, DEBUG=7
     */
    public function log($msg, $priority = 5)
    {
        $max_len = 512;
        $msg = rtrim(mb_strimwidth($msg, 0, $max_len)) . "...";

        if ($this->log_enabled) {
            if (!is_string($msg)) {
                $this->logger->log($priority, var_export($msg, true));
            } else {
                $this->logger->log($priority, $msg);
            }
        }
    }

    public function find($name, $value, $attr_name)
    {
        $this->bind();

        $filter = "$attr_name=$value";
        $base_dn = $this->active_server['baseDn'];
        $this->log("Attempting to search for $name=$value using basedn=$base_dn");

        try {
            $hm = $this->ldap->search($filter, $base_dn, $this->scope);
            $this->log("Raw Ldap Object: " . var_export($hm, true), 7);

            if ($hm->count() == 0) {
                $this->log("Could not find an account for $name=$value", 5);
                return false;
            } elseif ($hm->count() > 1) {
                $this->log("Found more than one user account for $name=$value", 1);
                return false;
            }

            $this->user = $hm->current();
            $this->log("User entry response: " . var_export($this->user, true), 7);
            return $this->user;
        } catch (LdapException $exc) {
            return $exc->getMessage();
        }
    }

    public function authenticate($username, $password)
    {
        $this->bind();
        $options = $this->config;
        $auth = new AuthenticationService();
        $this->log("Attempting to authenticate $username");
        $adapter = new AuthAdapter($options, $username, $password);
        $result = $auth->authenticate($adapter);

        if ($result->isValid()) {
            $this->log("$username logged in successfully!");
            return true;
        } else {
            $messages = $result->getMessages();
            $this->log("$username AUTHENTICATION FAILED!, error output: " . var_export($messages, true));
            return $messages;
        }
    }

    public function setUserArray(array $user)
    {
        $this->user = $user;
        return $this;
    }
}
