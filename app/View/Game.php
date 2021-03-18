<?php


namespace app\View;


class Game extends AppView
{
    protected $model;
    protected $controller;

    public function __construct($model, $controller, $page, $user) {
        parent::__construct($page, $user);

        $this->model = $model;
        $this->controller = $controller;
    }

    //
}
