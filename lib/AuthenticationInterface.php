<?php
interface AuthentificationInterface {
    /**
     * Check if the member is valid.
     * @param $member
     * @return int|bool Returns true if the user is valid, 0 if the user is
     * 			inactive and false if the user does not exist. 
     */
    function validMember($member);
    
    /**
     * Authenticate the user based on provided password
     * @param $member
     * @param $password
     * @return bool|int Returns true if the user was correctly authenticated,
     * 					0 if the user was authenticated but inactive, and
     * 					false if the authentication failed.
     */
    function authenticate($member, $password);
}