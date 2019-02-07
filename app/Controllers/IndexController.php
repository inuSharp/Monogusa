<?php

class IndexController
{
    public function view()
    {
        Log::info('test');
        $user = User::select(['id', 'password'])->where('id', '=', 1)->get();
        var_dump($user);
        //echo toPlural('my_table');
        //return render('index', ['test' => 'aaa']);
    }

    public function get()
    {
        $id   = request('id');
        $pass = request('pass');

        $data = QB::table('formats')->get();
        return responseJson($data, $status);
    }

    public function post()
    {
        $data = [
            [
                'note' => 'aaa',
                'created_at' => '2018-01-01',
                'updated_at' => '2018-01-01',
            ],
        ];

        QB::table('my_table')->insert($data);
        return responseJson(['message'=>'success']);
    }

    public function put()
    {
        return responseJson(['message'=>'success']);
    }

    public function delete()
    {
        return responseJson(['message'=>'success']);
    }
}
