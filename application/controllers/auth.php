<?php
/**
 * Created on Jan 6, 2016
 * Time Created: 1:03:10 AM
 * Filename: auth.php
 *
 * Email: wisnuwidi@yahoo.com
 * Copyright (c) 2016 Indsites
 * http://www.indsites.com
 */

class auth extends MY_Controller {
    protected $auth;
    protected $authPage = 'auth/';

    public function __construct() {
        parent::__construct();
        $this->auth = new MY_Auth();
    }

    public function index() {
        return $this->auth->index($this->authPage);
    }

    public function login() {
        return $this->auth->login($this->authPage);
    }

    public function logout() {
        return $this->auth->logout($this->authPage);
    }

    function change_password() {
        return $this->auth->change_password($this->authPage);
    }

    public function forgot_password() {
        return $this->auth->forgot_password($this->authPage);
    }

    public function reset_password($code = null) {
        return $this->auth->reset_password($this->authPage, $code);
    }

    public function activate($id, $code = null) {
        return $this->auth->activate($this->authPage, $id, $code);
    }

    public function deactivate($id) {
        return $this->auth->deactivate($this->authPage, $id);
    }

    public function create_user() {
        return $this->auth->create_user($this->authPage);
    }

    public function edit_user() {
        return $this->auth->edit_user($this->authPage);
    }

    public function create_group() {
        return $this->auth->create_group($this->authPage);
    }

    public function edit_group() {
        return $this->auth->edit_group($this->authPage);
    }
}