<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = LEVEL_APP_MAP;

if (empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPPSSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Map';
$PostData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

//VALIDA AÇÃO
if ($PostData && $PostData['callback_action'] && $PostData['callback'] == $CallBack):
    //PREPARA OS DADOS
    $Case = $PostData['callback_action'];
    unset($PostData['callback'], $PostData['callback_action']);

    // AUTO INSTANCE OBJECT READ
    if (empty($Read)):
        $Read = new Read;
    endif;

    // AUTO INSTANCE OBJECT CREATE
    if (empty($Create)):
        $Create = new Create;
    endif;

    // AUTO INSTANCE OBJECT UPDATE
    if (empty($Update)):
        $Update = new Update;
    endif;

    // AUTO INSTANCE OBJECT DELETE
    if (empty($Delete)):
        $Delete = new Delete;
    endif;

    //SELECIONA AÇÃO
    switch ($Case):

        case 'getVehicles':
            $CarId = 56;
            $Read->ExeRead(DB_VEICULOS_MAP, " WHERE car_id = :id ORDER BY map_id DESC", "id={$CarId}");
            if ($Read->getResult()):
                $jSON['results'][] = [
                    'id' => 1,
                    'lat' => $Read->getResult()[0]["map_lat"],
                    'lng' => $Read->getResult()[0]["map_lng"],
                    'vel' => $Read->getResult()[0]["map_vel"],
                    'car_placa' => "LRJ6G09",
                ];
            endif;

            //$jSON['center'] = [
            //['lat' => 37.977000, 'lng' => 23.7141811],
            //];
            break;

        case 'getRoute':
            // CRIA DOIS PONTOS NO MAPA SENDO O PONTO A COMO PARTIDA E B COMO DESTINO
            $jSON['results'] = [
                ['point' => "A", 'title' => 'Oficina', 'lat' => 37.996547, 'lng' => 23.732001],
                ['point' => "B", 'title' => 'Casa', 'lat' => 37.959408, 'lng' => 23.713982],
            ];
            break;

        case 'getWaypoints':
            $jSON['results'] = [
                ['lat' => 50.262950, 'lng' => -5.050700], // Partida
                ['lat' => 51.507351, 'lng' => -0.127758],
                ['lat' => 52.205338, 'lng' => 0.121817],
                ['lat' => 52.486244, 'lng' => -1.890401],
                ['lat' => 52.954784, 'lng' => -1.158109],
                ['lat' => 53.383060, 'lng' => -1.464800],
                ['lat' => 53.480759, 'lng' => -2.242631],
                ['lat' => 53.799690, 'lng' => -1.549100] // Destino
            ];
            break;

    endswitch;

    //RETORNA O CALLBACK
    if ($jSON):
        echo json_encode($jSON);
    else:
        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas uma ação do sistema não respondeu corretamente. Ao persistir, contate o desenvolvedor!', E_USER_ERROR);
        echo json_encode($jSON);
    endif;
else:
    //ACESSO DIRETO
    die('<br><br><br><center><h1>Acesso Restrito!</h1></center>');
endif;
