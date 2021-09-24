<?php
define("END", "projetos.mxse.com.br/harmoniser");
//define("END", "localhost/harmoniser");
define("BASE", "https://".END);
date_default_timezone_set('America/Sao_Paulo');

$n = 0;
$host[0] = 'localhost';
$dbname[0] ='mxjunior_harmoniser';
$user[0] = 'mxjunior_user'; 
$pass[0] = 'j{O%!~}eYTPP2';

$host[1] = 'localhost';
$dbname[1] ='bd_wc_harmoniser';
$user[1] = 'root'; 
$pass[1] = '';

return array(
    'jwt' => array(
        'key' => '8D969EEF6ECAD3C29A3A629280E686CF0C3F5D5A86AFF3CA12020C923ADC6C92',
        'algorithm' => 'HS512'
    ),
    'database' => array(
        'host' => $host[$n],
        'dbname' => $dbname[$n],
        'user' => $user[$n],
        'pass' => $pass[$n],
    ),
    'host' => END,
    'hostEmail' => 'mail.dominio.com.br',
    'emailRemetente' => 'app@dominio.com.br',
    'nomeRemetente' => 'NomeAPP',
    'emailResposta' => 'app@dominio.com.br',
    'emailRespostaContato' => 'app@dominio.com.br',
    'nomeRespostaContato' => 'NomeAPP',
    'nomeResposta' => 'NomeAPP',
    'senhaEmail' => ''
);