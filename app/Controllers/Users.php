<?php
class Users extends Controller {
    public function __construct(){
        $this->userModel = $this->model('User');
    }

    public function register(){
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            // Process form

            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'username' => trim($_POST['username']),
                'fname' => trim($_POST['fname']),
                'lname' => trim($_POST['lname']),
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'username_err' => '',
                'fname_err' => '',
                'lname_err' => '',
                'email_err' => '',
                'password_err' => '',
                'confirm_password_err' => ''
            ];

            // Validate Email
            if(empty($data['email'])){
                $data['email_err'] = 'Please enter email';
            } else {
                // Check email
                if($this->userModel->findUserByEmail($data['email'])){
                    $data['email_err'] = 'Email is already taken';
                }
            }

            // Validate Username
            if(empty($data['username'])){
                $data['username_err'] = 'Please enter username';
            } else {
                // Check username
                if($this->userModel->findUserByUsername($data['username'])){
                    $data['username_err'] = 'Username is already taken';
                }
            }

            // Validate First Name
            if(empty($data['fname'])){
                $data['fname_err'] = 'Please enter first name';
            }

            // Validate Last Name
            if(empty($data['lname'])){
                $data['lname_err'] = 'Please enter last name';
            }

            // Validate Password
            if(empty($data['password'])){
                $data['password_err'] = 'Please enter password';
            } elseif(strlen($data['password']) < 6){
                $data['password_err'] = 'Password must be at least 6 characters';
            }

            // Validate Confirm Password
            if(empty($data['confirm_password'])){
                $data['confirm_password_err'] = 'Please confirm password';
            } else {
                if($data['password'] != $data['confirm_password']){
                    $data['confirm_password_err'] = 'Passwords do not match';
                }
            }

            // Make sure errors are empty
            if(empty($data['email_err']) && empty($data['username_err']) && empty($data['fname_err']) && empty($data['lname_err']) && empty($data['password_err']) && empty($data['confirm_password_err'])){
                // Validated

                // Hash Password
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

                // Register User
                if($this->userModel->register($data)){
                    // Redirect
                    flash('register_success', 'You are registered and can log in');
                    redirect('users/login');
                } else {
                    die('Something went wrong');
                }

            } else {
                // Load view with errors
                $this->view('users/register', $data);
            }

        } else {
            // Init data
            $data = [
                'username' => '',
                'fname' => '',
                'lname' => '',
                'email' => '',
                'password' => '',
                'confirm_password' => '',
                'username_err' => '',
                'fname_err' => '',
                'lname_err' => '',
                'email_err' => '',
                'password_err' => '',
                'confirm_password_err' => ''
            ];
            // Load view
            $this->view('users/register', $data);
        }
    }

    public function login(){
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            // Process form
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'username' => trim($_POST['username']),
                'password' => trim($_POST['password']),
                'remember' => isset($_POST['remember']) ? true : false,
                'username_err' => '',
                'password_err' => '',
            ];

            // Validate Username
            if(empty($data['username'])){
                $data['username_err'] = 'Please enter username';
            }

            // Validate Password
            if(empty($data['password'])){
                $data['password_err'] = 'Please enter password';
            }

            // Check for user/username
            $user = $this->userModel->findUserByUsername($data['username']);
            if(!$user){
                $data['username_err'] = 'No user found with that username';
            }

            // Make sure errors are empty
            if(empty($data['username_err']) && empty($data['password_err'])){
                // Validated
                // Check and set logged in user
                $loggedInUser = $this->userModel->login($data['username'], $data['password']);

                if($loggedInUser){
                    // Create Session
                    $this->createUserSession($loggedInUser);

                    // Set remember me cookie
                    if($data['remember']){
                        setcookie("remember_username", $loggedInUser->username, time() + (86400 * 30), "/");
                    } else {
                        setcookie("remember_username", "", time() - 3600, "/");
                    }

                    redirect('accounts'); // Redirect to accounts page

                } else {
                    $data['password_err'] = 'Password incorrect';

                    error_log("Login failed. Session: " . print_r($_SESSION, true));
                    $this->view('users/login', $data);
                }
            } else {
                // Load view with errors
                $this->view('users/login', $data);
            }


        } else {
            // Init data
            $data = [
                'username' => '',
                'password' => '',
                'remember' => false,
                'username_err' => '',
                'password_err' => '',
            ];

            // Check for remember me cookie
            if(isset($_COOKIE['remember_username'])){
                $data['username'] = $_COOKIE['remember_username'];
                $data['remember'] = true;
            }

            // Load view
            $this->view('users/login', $data);
        }
    }

    public function createUserSession($user){
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->username;

        if(isset($_SESSION['join_token'])){
            $token = $_SESSION['join_token'];
            unset($_SESSION['join_token']);
            redirect('accounts/join/' . $token);
        } else {
            redirect('accounts'); // Redirect to accounts page
        }
    }

    public function logout(){
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        session_destroy();
        redirect('users/login');
    }

    public function profile(){
        if(!isLoggedIn()){
            redirect('users/login');
        }

        $user = $this->userModel->findUserById($_SESSION['user_id']);

        $data = [
            'user' => $user
        ];

        $this->view('users/profile', $data);
    }

    public function edit(){
        if(!isLoggedIn()){
            redirect('users/login');
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            // Sanitize POST array
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'id' => $_SESSION['user_id'],
                'username' => trim($_POST['username']),
                'fname' => trim($_POST['fname']),
                'lname' => trim($_POST['lname']),
                'email' => trim($_POST['email']),
                'username_err' => '',
                'fname_err' => '',
                'lname_err' => '',
                'email_err' => ''
            ];

            // Validate data
            if(empty($data['username'])){
                $data['username_err'] = 'Please enter username';
            }
            if(empty($data['fname'])){
                $data['fname_err'] = 'Please enter first name';
            }
            if(empty($data['lname'])){
                $data['lname_err'] = 'Please enter last name';
            }
            if(empty($data['email'])){
                $data['email_err'] = 'Please enter email';
            }

            // Make sure no errors
            if(empty($data['username_err']) && empty($data['fname_err']) && empty($data['lname_err']) && empty($data['email_err'])){
                // Validated
                if($this->userModel->updateUser($data)){
                    // Update session variables
                    $_SESSION['user_name'] = $data['username'];
                    $_SESSION['user_email'] = $data['email'];
                    flash('profile_message', 'Profile Updated');
                    redirect('users/profile');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('users/edit', $data);
            }

        } else {
            // Get existing user from model
            $user = $this->userModel->findUserById($_SESSION['user_id']);

            $data = [
                'id' => $_SESSION['user_id'],
                'username' => $user->username,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'username_err' => '',
                'fname_err' => '',
                'lname_err' => '',
                'email_err' => ''
            ];

            $this->view('users/edit', $data);
        }
    }

    public function delete(){
        if(!isLoggedIn()){
            redirect('users/login');
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if($this->userModel->deleteUser($_SESSION['user_id'])){
                $this->logout();
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('users/profile');
        }
    }
}
