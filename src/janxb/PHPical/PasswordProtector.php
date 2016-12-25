<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 24.12.16
 * Time: 17:50
 */

namespace janxb\PHPical;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class PasswordProtector
{
    public function __construct($password)
    {
        $request = Request::createFromGlobals();
        $session = new Session();
        $session->start();
        $lifetime = 60 * 60 * 24 * 365;
        setcookie(session_name(), session_id(), time() + $lifetime);

        if ($request->get('logout') != null) {
            $session->invalidate();
            header('Location: .');
        }

        if ($request->get('password') == $password) {
            $session->set('password', $password);
            header('Location: .');
        }

        if ($session->get('password') == $password) {
            $html = <<<'EOT'
            <form method="post" class="loginform">
                <input type="hidden" name="logout" value="true">
                <input type="submit" value="Logout">
            </form>
EOT;
            echo $html;
        } else {
            $html = <<<'EOT'
            <form method="post" class="loginform">
                <input type="password" name="password">
                <input type="submit" value="Login">
            </form>
EOT;
            die($html);
        }
    }
}