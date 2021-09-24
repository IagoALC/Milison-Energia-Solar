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
$CallBack = 'Imagens';
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
            $ImageId = $PostData['ima_id'];
            unset($PostData['ima_id'], $PostData['ima_cover']);

            if (!empty($_FILES['ima_cover'])) :
                $ImageCover = $_FILES['ima_cover'];
                $Read->FullRead("SELECT ima_cover FROM " . DB_ANALYSIS_IMAGE . " WHERE ima_id = :id", "id={$ImageId}");
                if ($Read->getResult()) :
                    if (file_exists("../../uploads/{$Read->getResult()[0]['ima_cover']}") && !is_dir("../../uploads/{$Read->getResult()[0]['ima_cover']}")) :
                        unlink("../../uploads/{$Read->getResult()[0]['ima_cover']}");
                    endif;
                endif;

                $Upload->Image($ImageCover, $ImageId . '-' . time(), 600);
                if ($Upload->getResult()) :
                    $PostData['ima_cover'] = $Upload->getResult();
                else :
                    $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR FOTO:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG ou PNG para enviar como foto!", E_USER_WARNING);
                    echo json_encode($jSON);
                    return;
                endif;
            endif;

            $Update->ExeUpdate(DB_ANALYSIS_IMAGE, $PostData, "WHERE ima_id = :id", "id={$ImageId}");
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. A Imagem foi atualizado com sucesso!");
            $jSON['redirect'] = "dashboard.php?wc=imagens/home";
            break;

        case 'delete':
            $ImageId = $PostData['id'];
            $Read->ExeRead(DB_ANALYSIS_IMAGE, "WHERE ima_id = :id", "id={$ImageId}");
            if (!$Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>IMAGEM NÃO EXISTE:</b> Olá {$_SESSION['userLogin']['user_name']}, você tentou deletar uma Imagem que não existe ou já foi removido!", E_USER_WARNING);
            else :
                extract($Read->getResult()[0]);
                if (file_exists("../../uploads/{$demo_thumb}") && !is_dir("../../uploads/{$demo_thumb}")) :
                    unlink("../../uploads/{$demo_thumb}");
                endif;
                $Delete->ExeDelete(DB_ANALYSIS_IMAGE, "WHERE ima_id = :id", "id={$ima_id}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>IMAGEM REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=imagens/home";

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
