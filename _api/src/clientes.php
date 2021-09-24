<?php

use \Firebase\JWT\JWT;
use Zend\Config\Config;
use Zend\Config\Factory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$app->group('/clientes', function () use ($app) {

    $app->post('/cadastro', function ($request, $response) {

        try {

            $input = $request->getParsedBody();
            $table = "ws_users";
            $campos = "*";
            $sql = "SELECT {$campos} FROM {$table} WHERE user_cel = :user_cel AND user_level=1 AND user_pro_id=:user_pro_id";
            $sth = $this->db->prepare($sql);
            $sth->bindParam('user_cel', $input['user_cel']);
            $sth->bindValue('user_pro_id', 10);
            $sth->execute();
            $count = $sth->rowCount();

            $erroCpf = false;
            $erroEmail = false;

            if ($count > 0) {
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    $user = $row;
                }
            } else {

                setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
                date_default_timezone_set('America/Sao_Paulo');
                $agora = new DateTime();
                $sql = "INSERT INTO ws_users (guid, user_gender, user_name, user_password, user_registration, user_lastupdate, user_lastaccess, user_level, user_pro_id, user_cel) 
                          VALUES (:guid, :user_gender, :user_name, :user_password, :user_registration, :user_lastupdate, :user_lastaccess, :user_level, :user_pro_id, :user_cel)";
                $sp = explode(" ", $input['user_name']);
                $sth = $this->db->prepare($sql);
                $sth->bindValue('user_pro_id', "a8afae43-72de-444e-b038-3d83e239bf96");
                $sth->bindParam('guid', utf8_decode($input['guid']));
                $sth->bindParam('user_name', utf8_decode($input['user_name']));
                $sth->bindParam('user_cel', utf8_decode($input['user_cel']));
                $sth->bindParam('user_gender', utf8_decode($input['user_gender']));
                $sth->bindValue('user_level', 1);
                $sth->bindParam('user_password', hash("sha512", "123456"));
                $sth->bindParam('user_registration', $agora->format('Y-m-d H:i:s'));
                $sth->bindParam('user_lastupdate', $agora->format('Y-m-d H:i:s'));
                $sth->bindParam('user_lastaccess', $agora->format('Y-m-d H:i:s'));
                $sth->execute();

                $sql = "SELECT {$campos} FROM {$table} WHERE user_id={$this->db->lastInsertId()}";
                $sth = $this->db->prepare($sql);
                $sth->execute();
                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    $row["user_id"] = intval($row["user_id"]);
                    $row["user_level"] = intval($row["user_level"]);
                    $row["user_gender"] = intval($row["user_gender"]);
                    $row["user_name"] = $row["user_name"] . ($row["user_lastname"] ? " " . $row["user_lastname"] : "");
                    $user = $row;
                }

                if ($user) {
                    $retorno["cliente"] = $user;
                    return $this->response->withStatus(200)->withJson($retorno);
                } else {
                    return $this->response->withStatus(500)->write(1);
                }
            }

            return $this->response->withStatus(200)->withJson(1);
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });

    $app->post('/listagem', function ($request, $response) {

        try {
            $input = $request->getParsedBody();

            if (!isset($input['limit'])):
                $input['limit'] = 25;
            endif;

            $search = isset($input["search"]) ? " AND (user_name LIKE '%{$input["search"]}%' OR user_cel LIKE '%{$input["search"]}%')" : "";

            $page = isset($input["page"]) ? $input["page"] : 0;
            $inicio = $page * $input['limit'];
            $limit = " LIMIT {$inicio}, {$input['limit']}";

            $campos = "*";
            $table = "ws_users";
            $where = "user_level=1 AND user_pro_id='{$input["user_pro_id"]}' {$search}";
            $order = $input["order"] ? $input["order"] : "user_name ASC";

            $retorno = array();
            $listagem = array();

            $sql = "SELECT count(*) as total FROM {$table} WHERE {$where}";
            $sth = $this->db->prepare($sql);
            $sth->execute();
            while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                $retorno["totalElements"] = floatval($row["total"]);
                $retorno["totalPages"] = ceil($retorno["totalElements"] / $input['limit']);
            }

            $sql = "SELECT {$campos} FROM {$table} WHERE {$where} ORDER BY {$order} {$limit}";
            $sth = $this->db->prepare($sql);
            $sth->execute();

            while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                $row["user_id"] = intval($row["user_id"]);
                $row["user_level"] = intval($row["user_level"]);
                $row["user_gender"] = intval($row["user_gender"]);
                $row["user_name"] = $row["user_name"] . ($row["user_lastname"] ? " " . $row["user_lastname"] : "");
                $temp = $row;
                array_push($listagem, $temp);
            }

            $retorno["listagem"] = $listagem;
            return $this->response->withStatus(200)->withJson($retorno);
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });

    $app->put('/atualizar', function ($request, $response) {

        try {
            $datebirth = "";
            $input = $request->getParsedBody();

            if ($input['user_datebirth']):
                $datebirth = ", user_datebirth = :user_datebirth";
            endif;

            $sql = "UPDATE ws_users SET user_name = :user_name, user_lastname = :user_lastname, user_cel = :user_cel, user_document = :user_document, user_telephone = :user_telephone, user_email = :user_email {$datebirth} WHERE guid = :guid";
            $nameTemp = explode(" ", utf8_decode($input['user_name']));
            $input['user_lastname'] = str_replace("{$nameTemp[0]} ", "", $input['user_name']);
            $sth = $this->db->prepare($sql);
            $sth->bindParam('user_name', $nameTemp[0]);
            $sth->bindParam('user_lastname', $input['user_lastname']);
            if ($input['user_datebirth']):
                $sth->bindParam('user_datebirth', utf8_decode($input['user_datebirth']));
            endif;
            $sth->bindParam('guid', utf8_decode($input['guid']));
            $sth->bindParam('user_cel', utf8_decode($input['user_cel']));
            $sth->bindParam('user_document', utf8_decode($input['user_document']));
            $sth->bindParam('user_telephone', utf8_decode($input['user_telephone']));
            $sth->bindParam('user_email', utf8_decode($input['user_email']));
            $sth->execute();

            return $this->response->withStatus(200)->withJson(1);
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });

    $app->post('/excluir', function ($request, $response) {

        try {
            $input = $request->getParsedBody();

            $sql = "DELETE FROM ws_users WHERE guid=:guid";
            $sth = $this->db->prepare($sql);
            $sth->bindParam('guid', utf8_decode($input['guid']));
            $sth->execute();

            return $this->response->withStatus(200)->withJson(1);
        } catch (PDOException $e) {
            return $this->response->withStatus(500)->write($e->getMessage());
        }
    });
});
