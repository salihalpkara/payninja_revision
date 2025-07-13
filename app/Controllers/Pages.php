<?php
class Pages extends Controller {
    public function __construct(){

    }

    public function index(){
        if(isLoggedIn()){
            redirect('accounts');
        } else {
            redirect('users/login');
        }
    }

    
}
