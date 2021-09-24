<?php

use \Firebase\JWT\JWT;
use Zend\Config\Config;
use Zend\Config\Factory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$app->group('/usuarios', function () use ($app) {

    $app->post('/logar', function ($request, $response) {

        try {
            $input = $request->getParsedBody();
            $config = Factory::fromFile('../config/config.php', true);

            $table = "ws_users u";
            $campos = "*";
            $where = "(u.user_email='". strtolower($input['user_email'])."' AND u.user_password = :user_password AND user_level=7";
            $sql = "SELECT {$campos} FROM {$table} WHERE {$where}";
            $sth = $this->db->prepare($sql);
            if (isset($input['especial_password'])) {
                $sth->bindParam('user_password', $input['user_password']);
            } else {
                $sth->bindParam('user_password', hash("sha512", $input['user_password']));
            }
            $sth->execute();

            $count = 0;
            if (empty($count)) {
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    $count++;
                    if (!$row['user_thumb']) {
                        $row['user_thumb'] = "uploads/images/no_avatar.jpg";
                    }
                    $row['user_thumb'] = str_replace("https", "http", mb_strtolower($row['user_thumb']));

                    $user = array();

                    if (empty($row['user_name'])):
                        $row['user_name'] = "Sem nome";
                    endif;

                    $user['user_id'] = $row['user_id'];
                    $user['user_level'] = $row['user_level'];
                    $user['user_genre'] = $row['user_genre'];
                    $user['user_name'] = utf8_encode($row['user_name'] . " " . $row['user_lastname']);
                    $user['user_email'] = utf8_encode($row['user_email']);
                    $user['user_thumb'] = utf8_encode("https://{$config->get('host')}/tim.php?src={$row['user_thumb']}&w=400");
                    $user['user_datebirth'] = utf8_encode($row['user_datebirth']);
                    $user['user_cell'] = utf8_encode($row['user_cell']);
                    $user['user_registration'] = utf8_encode($row['user_registration']);

                    if ($input['password'] == $row['user_password']) {
                        $user['change'] = true;
                    } else {
                        $user['change'] = false;
                    }
                }
            }

            if ($count > 0) {
//$tokenId = base64_encode(random_bytes(32)); //random_bytes(32)
                $issuedAt = time();
                $notBefore = $issuedAt;             //Adding 10 seconds (removi os 10 segundos)
//$expire     = $notBefore + 60;            // Adding 60 seconds
                $serverName = 'teste';

                $data = [
                    'iat' => $issuedAt, // Issued at: time when the token was generated
//'jti' => $tokenId, // Json Token Id: an unique identifier for the token
                    'iss' => $serverName, // Issuer
                    'nbf' => $notBefore, // Not before
//'exp'  => $expire,           // Expire
                    'data' => [// Data related to the signer user
                        'user_id' => $user['user_id'], // id do usuario na tabela pessoa
                        'user_name' => $user['user_name'], // id do usuario na tabela pessoa
                        'user_email' => $user['user_email'], //user_email do usuario              
                        'user_registration' => $user['user_registration'] //data de user_registration do usuario              
                    ]
                ];

                $secretKey = ($config->get('jwt')->get('key'));
                $jwt = JWT::encode(
                                $data, $secretKey, 'HS512'
                );

                $user['jwt'] = $jwt;

                return $this->response->withStatus(200)->withJson($user);
            } else {
                if ($input['user_email'] && $input['user_password']) {
                    return $this->response->withStatus(401)->withJson(0);
                } else {
                    return $this->response->withStatus(403)->withJson(0);
                }
            }
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });

    $app->put('/atualizar', function ($request, $response) {

        if ($request->hasHeader('Authorization')) {

            $authHeader = $request->getHeader('authorization');
            $jwt = $authHeader[0];

            $config = Factory::fromFile('../config/config.php', true);
            $secretKey = ($config->get('jwt')->get('key'));

            $separator = '.';

            if (2 !== substr_count($jwt, $separator)) {
                return $this->response->withStatus(401)->withJson('Token incorreto');
            } else {

                try {
                    $token = JWT::decode($jwt, $secretKey, array('HS512'));
                } catch (Exception $ex) {
                    return $this->response->withStatus(401)->withJson('Token incorreto');
                }

                try {
                    $senhaNew = "";
                    $datebirth = "";
                    $input = $request->getParsedBody();
                    if ($input['user_password']):
                        $senhaNew = ", user_password =:user_password";
                    endif;

                    if ($input['user_datebirth']):
                        $datebirth = ", user_datebirth = :user_datebirth";
                    endif;

                    $sql = "UPDATE ws_users SET user_name = :user_name, user_lastname = :user_lastname, user_cell = :user_cell {$senhaNew}{$datebirth} WHERE user_id = :user_id";
                    $nameTemp = explode(" ", utf8_decode($input['user_name']));
                    $input['user_lastname'] = str_replace("{$nameTemp[0]} ", "", $input['user_name']);
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam('user_name', $nameTemp[0]);
                    $sth->bindParam('user_lastname', $input['user_lastname']);
                    if ($input['user_datebirth']):
                        $sth->bindParam('user_datebirth', utf8_decode($input['user_datebirth']));
                    endif;
                    $sth->bindParam('user_cell', utf8_decode($input['user_cell']));
                    $sth->bindParam('user_id', $token->data->user_id);
                    if ($input['user_password']):
                        $sth->bindParam('user_password', hash("sha512", utf8_decode($input['user_password'])));
                    endif;
                    $sth->execute();

                    return $this->response->withStatus(200)->withJson(1);
                } catch (PDOException $e) {
                    return $this->response->withStatus(500)->write($e->getMessage());
                }
            }
        } else {
            return $this->response->withStatus(401)->withJson('Token inexistente');
        }
    });

    $app->get('/atualizarSenha/[{user_password}]', function ($request, $response, $args) {

        if ($request->hasHeader('Authorization')) {

            $authHeader = $request->getHeader('authorization');
            $jwt = $authHeader[0];

            $config = Factory::fromFile('../config/config.php', true);
            $secretKey = ($config->get('jwt')->get('key'));

            $separator = '.';

            if (2 !== substr_count($jwt, $separator)) {
                return $this->response->withStatus(401)->withJson('Token incorreto');
            } else {

                try {
                    $token = JWT::decode($jwt, $secretKey, array('HS512'));
                } catch (Exception $ex) {
                    return $this->response->withStatus(401)->withJson('Token incorreto');
                }

                try {

                    $sql = "UPDATE ws_users SET user_password = :user_password WHERE user_id = :user_id";
                    $sth = $this->db->prepare($sql);
                    $sth->bindValue('user_password', utf8_decode($args['user_password']));
                    $sth->bindParam('user_id', $token->data->user_id);
                    $sth->execute();

                    return $this->response->withStatus(200)->withJson(1);
                } catch (PDOException $e) {
                    return $this->response->withStatus(500)->write($e->getMessage());
                }
            }
        } else {
            return $this->response->withStatus(401)->withJson('Token inexistente');
        }
    });

    $app->post('/cadastro', function ($request, $response) {

        $config = Factory::fromFile('../config/config.php', true);

        try {
            $input = $request->getParsedBody();
            $table = "ws_users";
            $campos = "user_email,user_id";
            $sql = "SELECT {$campos} FROM {$table} WHERE user_email = :user_email";
            $sth = $this->db->prepare($sql);
            $sth->bindParam('user_email', $input['user_email']);
            $sth->execute();
            $count = $sth->rowCount();

            $erroCpf = false;
            $erroEmail = false;

            if ($count > 0) {
//usuario já existe
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    if (strtolower($input['user_email']) == $row['user_email']) {
                        return $this->response->withStatus(401)->withJson(1);
                    }
                }
            } else {

                setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
                date_default_timezone_set('America/Sao_Paulo');
                $agora = new DateTime();

//Cadastro de Usuário
                $sql = "INSERT INTO ws_users (user_name, user_lastname, user_email, user_password, user_registration, user_lastupdate, user_lastaccess, user_level) 
                          VALUES (:user_name, :user_lastname, :user_email, :user_password, :user_registration, :user_lastupdate, :user_lastaccess, :user_level)";
                $sp = explode(" ", $input['user_name']);
                $input['user_lastname'] = str_replace("{$sp[0]} ", "", $input['user_name']);
                $input['user_name'] = $sp[0];
                if ($input['user_name'] == $input['user_lastname']):
                    $input['user_lastname'] = "";
                endif;
                $sth = $this->db->prepare($sql);
                $sth->bindParam('user_level', "7");
                $sth->bindParam('user_name', utf8_decode($input['user_name']));
                $sth->bindParam('user_lastname', utf8_decode($input['user_lastname']));
                $sth->bindParam('user_email', $input['user_email']);
                $sth->bindParam('user_password', hash("sha512", $input['user_password']));
                $sth->bindParam('user_registration', $agora->format('Y-m-d H:i:s'));
                $sth->bindParam('user_lastupdate', $agora->format('Y-m-d H:i:s'));
                $sth->bindParam('user_lastaccess', $agora->format('Y-m-d H:i:s'));
                $sth->execute();
                $user["user_id"] = $this->db->lastInsertId();

                if ($user) {
                    return $this->response->withStatus(200)->withJson($user);
                } else {
                    return $this->response->withStatus(500)->write(1);
                }
            }
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });

    $app->post('/sendMailPassword', function ($request, $response) {

        try {

            $config = Factory::fromFile('../config/config.php', true);
            $input = $request->getParsedBody();
            $sql = "SELECT * FROM ws_users WHERE user_email = :user_email";
            $sth = $this->db->prepare($sql);
            $sth->bindParam('user_email', $input['user_email']);
            $sth->execute();
            $count = $sth->rowCount();

            $user_name = '';
            $id = 0;

            if ($count > 0) {
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    $user_name = $row['user_name'];
                    $user_id = floatval($row['user_id']);
                }

                $user_password = getToken(6);
                $user_password = 123456;
                $sql = "UPDATE ws_users SET user_password = :user_password WHERE user_id = :user_id";
                $sth = $this->db->prepare($sql);
                $sth->bindParam('user_id', $user_id);
                $sth->bindParam('user_password', hash("sha512", $user_password));
                $sth->execute();

                $mail = new PHPMailer(true);
                try {
                    /*
                    //Server settings
                    $mail->SMTPDebug = 0;
                    $mail->isSMTP();
                    $mail->Host = $config->get('hostEmail');
                    $mail->SMTPAuth = true;
                    $mail->Username = $config->get('emailRemetente');
                    $mail->Sender = $config->get('emailRemetente');
                    $mail->Password = $config->get('senhaEmail');
                    $mail->SMTPSecure = "ssl";
                    $mail->SMTPAutoTLS = false;
                    $mail->Port = 465;
                    $mail->CharSet = 'utf-8';
                    $mail->setFrom($config->get('emailRemetente'), $config->get('nomeRemetente'));
                    $mail->ConfirmReadingTo = $config->get('emailResposta');
                    $mail->addReplyTo($config->get('emailResposta'), $config->get('nomeResposta'));

                    $mail->addAddress($input['user_email'], $user_name);

                    $mensagem = 'Prezado(a) <b>' . $user_name . '</b>,<br><br>';
                    $mensagem .= 'Acabamos de receber uma solicitação para alterar sua senha de acesso ao aplicativo.<br>';
                    $mensagem .= 'Se foi você que solicitou esta ação, faça seu próximo login usando seu E-mail e a seguinte senha abaixo:<br><br>';
                    $mensagem .= '<b>' . $user_password . '</b><br><br>';
                    $mensagem .= 'Caso não tenha conhecimento sobre essa solicitação, por favor desconsiderar este e-mail.<br><br>';
                    $mensagem .= 'Obrigado.<br>';

                    $mail->isHTML(true);
                    $mail->Subject = "Alteração de senha | App ".$config->get('nomeResposta');;
                    $mail->Body = $mensagem;

                    $mail->send();*/
                    return $this->response->withStatus(200)->withJson(1);
                } catch (Exception $e) {
                    return $this->response->withStatus(500)->write($mail->ErrorInfo);
                }
            } else {
                return $this->response->withStatus(401)->withJson('count ' . $count);
            }
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });

    $app->post('/updateArquivo', function ($request, $response) {

        try {

            $authHeader = $request->getHeader('authorization');
            $jwt = $authHeader[0];

            $config = Factory::fromFile('../config/config.php', true);
            $secretKey = ($config->get('jwt')->get('key'));

            $separator = '.';

            if (2 !== substr_count($jwt, $separator)) {
                return $this->response->withStatus(401)->withJson('Token incorreto');
            } else {

                try {
                    $token = JWT::decode($jwt, $secretKey, array('HS512'));
                } catch (Exception $ex) {
                    return $this->response->withStatus(401)->withJson('Token incorreto 2');
                }
                $input = $request->getParsedBody();

                $query = "UPDATE ws_users SET user_thumb = :user_thumb WHERE user_id = :user_id";
                $sth = $this->db->prepare($query);
                $sth->bindParam('user_thumb', $input['user_thumb']);
                $sth->bindParam('user_id', $token->data->user_id);
                $sth->execute();
                return $this->response->withStatus(200)->withJson(1);
            }
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });
});
