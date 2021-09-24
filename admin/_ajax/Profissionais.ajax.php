<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = 3; //LEVEL_WC_USERS;

if ((!APP_USERS && !APP_EAD) || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess) :
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Profissionais';
$PostData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

//VALIDA AÇÃO
if ($PostData && $PostData['callback_action'] && $PostData['callback'] == $CallBack) :
    //PREPARA OS DADOS
    $Case = $PostData['callback_action'];
    unset($PostData['callback'], $PostData['callback_action']);

    // AUTO INSTANCE OBJECT READ
    if (empty($Read)) :
        $Read = new Read;
    endif;

    // AUTO INSTANCE OBJECT CREATE
    if (empty($Create)) :
        $Create = new Create;
    endif;

    // AUTO INSTANCE OBJECT UPDATE
    if (empty($Update)) :
        $Update = new Update;
    endif;

    // AUTO INSTANCE OBJECT DELETE
    if (empty($Delete)) :
        $Delete = new Delete;
    endif;
    $Upload = new Upload('../../uploads/');

    //SELECIONA AÇÃO
    switch ($Case):

        case 'manager':
            $ProId = $PostData['pro_id'];
            $Update->ExeUpdate(DB_PROFESSIONALS, $PostData, "WHERE pro_id = :id", "id={$ProId}");
            unset($PostData['pro_id']);
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. O Profissional foi atualizado com sucesso!");
            $jSON['redirect'] = "dashboard.php?wc=profissionais/home";
            break;

        case 'delete':
            $ProId = $PostData['id'];
            $Read->ExeRead(DB_PROFESSIONALS, "WHERE pro_id = :id", "id={$ProId}");
            if (!$Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>PROFISSIONAL NÃO EXISTE:</b> Olá {$_SESSION['userLogin']['user_name']}, você tentou deletar um Profissional que não existe ou já foi removido!", E_USER_WARNING);
            else :
                extract($Read->getResult()[0]);
                $Delete->ExeDelete(DB_PROFESSIONALS, "WHERE pro_id = :id", "id={$pro_id}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>PROFISSIONAL REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=profissionais/home";
            endif;
            break;
    endswitch;

    //RETORNA O CALLBACK
    if ($jSON) :
        echo json_encode($jSON);
    else :
        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas uma ação do sistema não respondeu corretamente. Ao persistir, contate o desenvolvedor!', E_USER_ERROR);
        echo json_encode($jSON);
    endif;
else :
    //ACESSO DIRETO
    die('<br><br><br><center><h1>Acesso Restrito!</h1></center>');
endif;
