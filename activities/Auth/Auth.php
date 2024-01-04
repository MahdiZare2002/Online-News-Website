<?php

namespace Auth;

use database\DataBase;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Auth
{

    protected function redirect($url)
    {
        header('Location: ' . trim(CURRENT_DOMAIN, '/ ') . '/' . trim($url, '/ '));
        exit;
    }

    protected function redirectback()
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    private function hash($password)
    {
        $hashPassword = password_hash($password, PASSWORD_DEFAULT);
        return $hashPassword;
    }

    private function random()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    public function activationMessage($username, $verifyToken)
    {
        $message = '
        <h1>Active --NewsSite Account--</h1>
        <p>dear ' . $username . ' please click on the below link to activate your account</p>
        <div><a href= "' . url('activation/' . $verifyToken) . '"> Link </a></div>
        ';
        return $message;
    }

    private function sendMail($emailAddress, $subject, $body)
    {
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->CharSet = "UTF-8";
            $mail->isSMTP(); //Send using SMTP
            $mail->Host = MAIL_HOST; //Set the SMTP server to send through
            $mail->SMTPAuth = SMTP_AUTH; //Enable SMTP authentication
            $mail->Username = MAIL_USERNAME; //SMTP username
            $mail->Password = MAIL_PASSWORD; //SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = MAIL_PORT; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom(SENDER_MAIL, SENDER_NAME);
            $mail->addAddress($emailAddress); //Add a recipient

            //Content
            $mail->isHTML(true); //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

    public function register()
    {
        require_once(BASE_PATH . '/template/auth/register.php');
    }

    public function registerStore($request)
    {
        if (empty($request['username'] || empty($request['email']) || empty($request['password']))) {
            flash('register_error', 'All fields are required');
            $this->redirectback();
        } else if (strlen($request['password']) < 8) {
            flash('register_error', 'Password must be at least 8 characters long');
            $this->redirectback();
        } else if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            flash('register_error', 'The email entered is not valid');
            $this->redirectback();
        } else {
            $db = new DataBase();
            $user = $db->select('SELECT * FROM users WHERE email = ?', [$request['email']])->fetch();

            if ($user != null) {
                flash('register_error', 'Email already exists');
                $this->redirectback();
            } else {
                $randomToken = $this->random();
                $activationMessage = $this->activationMessage($request['username'], $randomToken);
                $result = $this->sendMail($request['email'], 'Active Account', $activationMessage);

                if ($result) {
                    $request['verify_token'] = $randomToken;
                    $request['password'] = $this->hash($request['password']);
                    $db->insert('users', array_keys($request), $request);
                    $this->redirect('login');
                } else {
                    flash('register_error', 'The activation email was not sent');
                    $this->redirectback();
                }
            }
        }
    }

    public function activation($verifyToken)
    {
        $db = new DataBase();
        $user = $db->select('SELECT * FROM users WHERE verify_token = ? AND is_active = 0', [$verifyToken])->fetch();
        if ($verifyToken == null) {
            $this->redirect('login');
        } else {
            $db->update('users', $user['id'], ['is_active'], [1]);
            $this->redirect('login');
        }
    }

    public function login()
    {
        require_once(BASE_PATH . '/template/auth/login.php');
    }

    public function checkLogin($request)
    {
        if (empty($request['email']) || empty($request['password'])) {
            flash('login_error', 'All fields are required');
            $this->redirectback();
        } else {
            $db = new DataBase();
            $user = $db->select('SELECT * FROM users WHERE email = ?', [$request['email']])->fetch();

            if ($user != null) {
                if (password_verify($request['password'], $user['password']) && $user['is_active'] == 1) {
                    $_SESSION['user'] = $user['id'];
                    $this->redirect('admin');
                } else {
                    flash('login_error', 'The password is wrong');
                    $this->redirectBack();
                }
            } else {
                flash('login_error', 'User not found');
                $this->redirectback();
            }
        }
    }

    public function checkAdmin()
    {
        if (isset($_SESSION['user'])) {
            $db = new DataBase();
            $user = $db->select('SELECT * FROM users WHERE id = ?', [$_SESSION['user']])->fetch();
            if ($user != null) {
                if ($user['permission'] != 'admin') {
                    $this->redirect('home');
                }
            } else {
                $this->redirect('home');
            }
        } else {
            $this->redirect('home');
        }
    }

    public function logout()
    {
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
            session_destroy();
        }
        $this->redirect('login');
    }

    public function forgotMessage($username, $forgotToken)
    {
        $message = '
        <h1>Account Recovery</h1>
        <p>dear ' . $username . ' please click on the below link to recovry your account</p>
        <div><a href= "' . url('reset-password-form/' . $forgotToken) . '"> Link </a></div>
        ';
        return $message;
    }

    public function forgot()
    {
        require_once(BASE_PATH . '/template/auth/forgot.php');
    }


    public function forgotRequest($request)
    {
        if (empty($request['email'])) {
            flash('forgot_error', 'The email field is required');
            $this->redirectback();
        } else if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            flash('forgot_error', 'The entered email is not correct');
            $this->redirectback();
        } else {
            $db = new DataBase();
            $user = $db->select('SELECT * FROM users WHERE email = ?', [$request['email']])->fetch();
            if ($user == null) {
                flash('forgot_error', 'There is no email entered');
                $this->redirectBack();
            } else {
                $randomToken = $this->random();
                $forgotMessage = $this->forgotMessage($user['username'], $randomToken);
                $result = $this->sendMail($request['email'], 'Password Recovery', $forgotMessage);

                if ($result) {
                    date_default_timezone_set('Asia/Tehran');
                    $db->update('users', $user['id'], ['forgot_token', 'forgot_token_expire'], [$randomToken, date("Y-m-d H:i:s", strtotime('+15 minutes'))]);

                    $this->redirect('login');
                } else {
                    flash('forgot_error', 'Email could not be sent.');
                    $this->redirectBack();
                }
            }
        }
    }

    public function resetPasswordView($forgot_token)
    {
        require_once(BASE_PATH . '/template/auth/reset-password.php');
    }

    public function resetPassword($request, $forgot_token)
    {
        if (!isset($request['password']) || strlen($request['password']) < 8) {
            flash('reset_error', 'password could not be store.');
            $this->redirectBack();
        } else {
            $db = new DataBase();
            $user = $db->select('SELECT * FROM users WHERE forgot_token = ?', [$forgot_token])->fetch();
            if ($user == null) {
                flash('reset_error', 'User with this profile was not found');
                $this->redirectBack();
            } else {
                date_default_timezone_set('Asia/Tehran');
                if ($user['forgot_token_expire'] < date('Y-m-d H:i:s')) {
                    flash('reset_error', 'This token has expired');
                    $this->redirectBack();
                }
                if ($user) {
                    $db->update('users', $user['id'], ['password'], [$this->hash($request['password'])]);
                    $this->redirect('login');
                } else {
                    $this->redirectBack();
                }
            }
        }
    }

}
