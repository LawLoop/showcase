<?php
/**
 * Created by PhpStorm.
 * User: todd
 * Date: 11/10/16
 * Time: 19:36
 */

class User extends Model
{

    public function takeValues($values)
    {
        parent::takeValues($values);
        if(isset($values['password']) && !empty($values['password']))
        {
            $this->setPassword($values['password']);
        }
    }

    public function setPassword($password)
    {
        $this->digesta1 = md5($password);
    }

    public function validatePassword($password)
    {
        return ($this->digesta1 == md5($password));
    }


    public function jsonSerialize()
    {
        $json = $this->_data;
        unset($json['digesta1']);
        return $json;
    }
}