<?php
class User extends Model {
    public function __construct(){
        parent::__construct();
    }

    // Register user
    public function register($data){
        $this->db->query('INSERT INTO app_user (username, fname, lname, email, password_hash) VALUES(:username, :fname, :lname, :email, :password_hash)');
        // Bind values
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':fname', $data['fname']);
        $this->db->bind(':lname', $data['lname']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password_hash', $data['password']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    // Find user by email
    public function findUserByEmail($email){
        $this->db->query('SELECT * FROM app_user WHERE email = :email');
        $this->db->bind(':email', $email);

        $row = $this->db->single();

        // Check row
        if($this->db->rowCount() > 0){
            return true;
        } else {
            return false;
        }
    }

    // Find user by username
    public function findUserByUsername($username){
        $this->db->query('SELECT * FROM app_user WHERE username = :username');
        $this->db->bind(':username', $username);

        $row = $this->db->single();

        // Check row
        if($this->db->rowCount() > 0){
            return $row;
        } else {
            return false;
        }
    }

    // Login User
    public function login($username, $password){
        $this->db->query('SELECT * FROM app_user WHERE username = :username');
        $this->db->bind(':username', $username);

        $row = $this->db->single();

        if($row){
            $hashed_password = $row->password_hash;
            if(password_verify($password, $hashed_password)){
                return $row;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function searchUsersNotInAccount($searchTerm, $accountId){
        $this->db->query('
            SELECT u.id, u.username, u.fname, u.lname
            FROM app_user u
            WHERE u.username LIKE :searchTerm
            AND u.id NOT IN (
                SELECT user_id FROM account_user WHERE account_id = :accountId
            )
            LIMIT 10
        ');
        $this->db->bind(':searchTerm', '%' . $searchTerm . '%');
        $this->db->bind(':accountId', $accountId);

        $results = $this->db->resultSet();
        return $results;
    }

    public function findUserById($id){
        $this->db->query('SELECT * FROM app_user WHERE id = :id');
        $this->db->bind(':id', $id);

        $row = $this->db->single();

        // Check row
        if($this->db->rowCount() > 0){
            return $row;
        } else {
            return false;
        }
    }

    // Update user
    public function updateUser($data){
        $this->db->query('UPDATE app_user SET username = :username, fname = :fname, lname = :lname, email = :email WHERE id = :id');
        // Bind values
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':fname', $data['fname']);
        $this->db->bind(':lname', $data['lname']);
        $this->db->bind(':email', $data['email']);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function deleteUser($id){
        $this->db->query('DELETE FROM app_user WHERE id = :id');
        // Bind values
        $this->db->bind(':id', $id);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }
}