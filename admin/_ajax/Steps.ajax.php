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
$CallBack = 'Steps';
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
            $StepId = $PostData['step_id'];

            $Read->ExeRead(DB_STEP, "WHERE step_id= :id", "id={$StepId}");
            $ThisPage = $Read->getResult()[0];
            if (!empty($_FILES['step_image'])) :
                $File = $_FILES['step_image'];

                if ($ThisPage['step_image'] && file_exists("../../uploads/{$ThisPage['step_image']}") && !is_dir("../../uploads/{$ThisPage['step_image']}")) :
                    unlink("../../uploads/{$ThisPage['step_image']}");
                endif;

                $Upload = new Upload('../../uploads/');
                $Upload->Image($File, time(), IMAGE_W, 'images');
                if ($Upload->getResult()) :
                    $PostData['step_image'] = $Upload->getResult();
                else :
                    $jSON['alert'] = ["yellow", "image", "ERRO AO ENVIAR IMAGEM", "Desculpe {$_SESSION['userLogin']['user_name']}, Selecione Uma Imagem JPG Ou PNG Para Enviar Como Imagem!"];
                    echo json_encode($jSON);
                    return;
                endif;
            else :
                unset($PostData['step_image']);
            endif;
            $Update->ExeUpdate(DB_STEP, $PostData, "WHERE step_id = :id", "id={$StepId}");
            unset($PostData['step_id']);
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. A Step foi atualizado com sucesso!");
            $jSON['redirect'] = "dashboard.php?wc=site/step/home";
            break;
        case 'delete':
            $ImageId = $PostData['id'];
            $Read->ExeRead(DB_STEP, "WHERE step_id = :id", "id={$ImageId}");
            if ($Read->getResult()) :
                foreach ($Read->getResult() as $Image) :
                    $ImageRemove = "../../uploads/{$Image['step_image']}";
                    if (file_exists($ImageRemove) && !is_dir($ImageRemove)) :
                        unlink($ImageRemove);
                    endif;
                endforeach;
            endif;
            extract($Read->getResult()[0]);
            $Delete->ExeDelete(DB_STEP, "WHERE step_id = :id", "id={$step_id}");
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>STEP REMOVIDO COM SUCESSO!</b>");
            $jSON['redirect'] = "dashboard.php?wc=site/step/home";
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
