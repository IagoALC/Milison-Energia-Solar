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
$CallBack = 'Prints';
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
            $PrintId = $PostData['print_id'];
            $i = 1;
            //VERIFICAÇÂO DE IMAGENS
            for ($i; $i <= 5; $i++) :
                $print = 'print_image' . $i;
                unset($PostData[$print]);
                if (!empty($_FILES[$print])) :
                    $ImageCover = $_FILES[$print];
                    $Read->FullRead("SELECT $print FROM " . DB_PRINT . " WHERE print_id = :id", "id={$PrintId}");
                    if ($Read->getResult()) :
                        if (file_exists("../../uploads/{$Read->getResult()[0][$print]}") && !is_dir("../../uploads/{$Read->getResult()[0][$print]}")) :
                            unlink("../../uploads/{$Read->getResult()[0][$print]}");
                        endif;
                    endif;

                    $Upload->Image($ImageCover, $PrintId . $i . '-' . time(), 600);
                    if ($Upload->getResult()) :
                        $PostData[$print] = $Upload->getResult();
                    else :
                        $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR FOTO:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG ou PNG para enviar como foto!", E_USER_WARNING);
                        echo json_encode($jSON);
                        return;
                    endif;
                endif;
            endfor;

            $Update->ExeUpdate(DB_PRINT, $PostData, "WHERE print_id = :id", "id={$PrintId}");
            unset($PostData['print_id']);
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. A Análise foi atualizado com sucesso!");
            $jSON['redirect'] = "dashboard.php?wc=site/print/create&id={$PrintId}";
            break;

        case 'delete':
            $PrintId = $PostData['id'];
            $Read->ExeRead(DB_PRINT, "WHERE print_id = :id", "id={$PrintId}");
            if (!$Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>PRINT NÃO EXISTE:</b> Olá {$_SESSION['userLogin']['user_name']}, você tentou deletar um Print que não existe ou já foi removido!", E_USER_WARNING);
            else :
                extract($Read->getResult()[0]);
                $i = 1;
                for ($i; $i <= 5; $i++) :
                    $print = 'print_image' . $i;
                    if (file_exists("../../uploads/{$print}") && !is_dir("../../uploads/{$print}")) :
                        unlink("../../uploads/{$print}");
                    endif;
                endfor;
                $Delete->ExeDelete(DB_PRINT, "WHERE print_id = :id", "id={$print_id}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>PRINT REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=site/print/create&id={$PrintId}";
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
