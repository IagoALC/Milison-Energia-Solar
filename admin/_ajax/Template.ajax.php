<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = 3;//LEVEL_WC_TEMPLATE;

if (!APP_TEMPLATE || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPPSSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!',
        E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Template';
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
        case 'duplicate':
            $Create->ExeCreate(DB_TEMPLATE_COLORS, $PostData);
            $jSON['color_id'] = $Create->getResult();
            break;

        case 'save':
            $colorId = $PostData['color_id'];
            unset($PostData['color_id']);

            $PostData['color_status'] = (!empty($PostData['color_status']) ? '1' : '0');
            $Update->ExeUpdate(DB_TEMPLATE_COLORS, $PostData, 'WHERE color_id = :id', "id={$colorId}");
            $jSON['success'] = true;
            break;

        case 'remove':
            $colorId = $PostData['color_id'];
            $Delete->ExeDelete(DB_TEMPLATE_COLORS, 'WHERE color_id = :id', "id={$colorId}");
            $jSON['success'] = true;
            break;
    endswitch;

    //RETORNA O CALLBACK
    if ($jSON):
        echo json_encode($jSON);
    else:
        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas uma ação do sistema não respondeu corretamente. Ao persistir, contate o desenvolvedor!',
            E_USER_ERROR);
        echo json_encode($jSON);
    endif;
else:
    //ACESSO DIRETO
    die('<br><br><br><center><h1>Acesso Restrito!</h1></center>');
endif;
