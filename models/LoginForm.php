<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\helpers\JiraAuthenticationHelper;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;
    public $user_jira = false;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // email and password are both required
            [['email', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validateAuthentication()
            ['password', 'validateAuthentication'],
            ['email', 'email'],
        ];
    }

    /**
     * Validates the Jira authentication.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateAuthentication($attribute, $params)
    {
        if (!$this->hasErrors()) {


            if (!$this->getUserFromJira()) {
                $this->addError($attribute, 'Incorrect email or password.');
            } else {
                if (!$this->getUser())
                    $this->addError($attribute, 'Incorrect email or password.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Get user by [[email]]
     *
     * @return User|false
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByEmail($this->email);
        }
        return $this->_user;
    }

    public function getUserFromJira()
    {
        if ($this->user_jira === false) {
            $this->user_jira = (new JiraAuthenticationHelper())->getUser($this->email, $this->password);
        }
        return $this->user_jira;
    }
}
