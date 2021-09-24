<?php

use \Firebase\JWT\JWT;
use Zend\Config\Config;
use Zend\Config\Factory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$app->group('/app', function () use ($app) {

    $app->post('/config', function ($request, $response) {

        $config = Factory::fromFile('../config/config.php', true);

        try {
            $input = $request->getParsedBody();
            
            $config = [];
            $config["version"] = "1.0";
            $config["mod"] = "development";//production ou development
            
            return $this->response->withStatus(200)->withJson($config);
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });
});
