<?php

/**
 * This file is for user verification
 */

include_once 'db_connect.php';

class Login{
    public $username,$password,$code,$mysqli,$user_id; 
    
    function __construct($mysqli, $username, $password, $code) {
        $this->username = $username;
        $this->password = $password;
        $this->code = $code;
        $this->mysqli = $mysqli;
    }
    function checkUsername($mysqli,$username){
        if($query = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1")){
            $query->bind_param('s', $username);
            $query->execute();
            $query->store_result();
            
            if($query->num_rows == 1){
                $query->bind_result($user_id);
                $query->fetch();
                
                $this->user_id = $user_id;
                //check if password is match
                //self::checkPassword($mysqli, $username, $password);
                return TRUE;
            }else{  
                //failLogin::logRecord();
                //print 'false';
                return FALSE;
            }
            
        }else{
            //error preparing query statement
            return FALSE;
        }
    }
    function checkPassword($mysqli, $user_id, $password){
        if($query = $mysqli->prepare("SELECT password,salt FROM users WHERE id = ? LIMIT 1")){
            $query->bind_param('s', $user_id);
            $query->execute();
            $query->store_result();
            $query->bind_result($db_pass,$db_salt);
            $query->fetch();
                
            $password = $password . $db_salt; // this should be hashed/encrypted with blowfish
            if($password == $db_pass){
                //print 'true';
                return TRUE;
            }else{
                //print 'false';
                return FALSE;
            }
        }else{
            //error preparing query statement
            return FALSE;
        }
    }
    function checkCode($mysqli, $code){
        if($query = $mysqli->prepare("SELECT * FROM setup WHERE value = ? LIMIT 1")){
            $query->bind_param('s', $code);
            $query->execute();
            $query->store_result();
            if($query->num_rows == 1){
                //print 'TRUE';
                return TRUE;
            }else{
                //print 'FALSE';
                return FALSE;
            }
        }else{
            //failLogin::logRecord();
            //print 'False';
            //error preparing query statement
            return FALSE;
        }
        
    }
    function get_login_info($mysqli,$username){
        if($query = $mysqli->prepare("SELECT users.usrlevel,infousers.name FROM infousers,users WHERE users.id = infousers.id AND users.username = ? LIMIT 1")){
            $query->bind_param('s', $username);
            $query->execute();
            $query->store_result();
            if($query->num_rows == 1){
                $query->bind_result($usrLevel,$name);
                $query->fetch();
                //store data in array or in session
                print $usrLevel . $name;
            }else{
                //no user data
                print 'no records yet';
            }
        }else{
            //error preparing query statement
            print 'NO';
        }
    }
    function on_execute($mysqli, $username, $password, $code, $user_id){
        if (self::checkUsername($mysqli, $username) == TRUE){
            if(failLogin::bruteforce($mysqli,$user_id) == TRUE){
                print 'account is locked for 2 hrs';
            }else{
                if(self::checkPassword($mysqli, $user_id, $password) == TRUE){
                    if(self::checkCode($mysqli, $code) == TRUE){
                        //get user info's
                        self::get_login_info($mysqli,$username);
                    }else{
                        failLogin::recordLogAttempts($mysqli,$user_id);
                        //print 'error code';
                    }
                }else{
                    failLogin::recordLogAttempts($mysqli,$user_id);
                    //print 'error pass';
                }
            }
        }else{
            //failLogin::bruteforce($mysqli);
            //No user doesn't exist
            //print 'error username';
        }
    }
    function get_values($mysqli, $username, $password, $code, $user_id){
        $this->checkUsername($mysqli, $username);
        $this->checkPassword($mysqli, $user_id, $password);
        $this->checkCode($mysqli, $code);
    }
    function __destruct() {
        $this->username;
        $this->password;
        $this->code;
        $this->mysqli;
        $this->user_id;
        self::get_values($this->mysqli, $this->username, $this->password, $this->code, $this->user_id);
        self::on_execute($this->mysqli, $this->username, $this->password, $this->code, $this->user_id);
    }
}

class failLogin{ 
    function recordLogAttempts($mysqli,$user_id){
        //get timestamp of current time
        $time_now = time();
        if($query = $mysqli->prepare("INSERT INTO loginattempts (user_id,time) VALUES (?,?)")){
            $query->bind_param('ii',$user_id,$time_now);
            $query->execute();
        }
    }
    function bruteforce($mysqli,$user_id){
        //get timestamp of current time
        $time_now = time();
        //all login attempts are counted from 2 hours past
        $valid_attempts = $time_now - (2 * 60 * 60);
        
        if($query = $mysqli->prepare("SELECT * FROM loginattempts WHERE user_id = ? AND time > '$valid_attempts'")){
            $query->bind_param('i',$user_id);
            $query->execute();
            $query->store_result();
            
            //only 5 attempts are allowed
            if($query->num_rows > 5){
                return TRUE;
            }else{
                return FALSE;
            }
        }else{
            return FALSE;
        }
    }
}

class loginSession{
    /**
     * UNDER CONSTRUCTION CLASS
     */
}

$login = new Login($mysqli,"admin","11234","qw-erty-uiop");