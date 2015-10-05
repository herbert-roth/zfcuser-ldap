<?php

namespace ZfcUserLdap\Adapter;

interface AdapterInterface
{
    public function findByUsername($username);
    public function findByEmail($email);
    public function findById($id);
    public function getUsername();
    public function getDisplayName();
    public function getEmail();
    public function getRoles($role_key, array $usable_roles);
}