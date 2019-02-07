<?php
class UserController
{
    public function view()
    {
        return render('mypage', []);
    }

    public function loginView()
    {
        if (getSession('is_login')) {
            redirect(WEB_ROOT . '/mypage');
        } else {
            return render('login', ['menu_show' => false]);
        }
    }

    public function login()
    {
        $id   = request('id');
        $pass = request('pass');

        $status = 400;
        $user = QB::table('users')
                  ->where('login_id', '=', $id)
                  ->first();
        if ($user) {
            if (password_verify($pass, $user->password)) {
                session_regenerate_id(true);
                setSession('user_id', $user->id);
                setSession('role', $user->role);
                setSession('is_login', true);
                $status = 200;
            }
        }
        return responseJson($user, $status);
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        redirect(WEB_ROOT . '/login');
    }
}
