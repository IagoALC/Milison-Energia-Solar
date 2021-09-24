<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = LEVEL_WC_VEICULOS;

if (!APP_VEICULOS || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPPSSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Carros';
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
    $Upload = new Upload('../../uploads/');

    //SELECIONA AÇÃO
    switch ($Case):
        case 'manager':
            $CarId = $PostData['car_id'];
            unset($PostData['car_id'], $PostData['car_cover']);

            if (!empty($_FILES['car_cover'])):
                $UserThumb = $_FILES['car_cover'];
                $Read->FullRead("SELECT car_cover FROM " . DB_VEICULOS . " WHERE car_id = :id", "id={$CarId}");
                if ($Read->getResult()):
                    if (file_exists("../../uploads/{$Read->getResult()[0]['car_cover']}") && !is_dir("../../uploads/{$Read->getResult()[0]['car_cover']}")):
                        unlink("../../uploads/{$Read->getResult()[0]['car_cover']}");
                    endif;
                endif;

                $Upload->Image($UserThumb, $CarId . "-" . time(), 600);
                if ($Upload->getResult()):
                    $PostData['car_cover'] = $Upload->getResult();
                else:
                    $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR FOTO:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG ou PNG para enviar como foto!", E_USER_WARNING);
                    echo json_encode($jSON);
                    return;
                endif;
            endif;

            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. O Veículo foi atualizado com sucesso!");
            $Update->ExeUpdate(DB_VEICULOS, $PostData, "WHERE car_id = :id", "id={$CarId}");
            break;

        case 'delete':
            $CarId = $PostData['id'];
            $Read->ExeRead(DB_VEICULOS, "WHERE car_id = :id", "id={$CarId}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>VEÍCULO NÃO EXISTE:</b> Olá {$_SESSION['userLogin']['user_name']}, você tentou deletar um Veículo que não existe ou já foi removido!", E_USER_WARNING);
            else:
                extract($Read->getResult()[0]);
                $Delete->ExeDelete(DB_VEICULOS, "WHERE car_id = :id", "id={$CarId}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>VEÍCULO REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=clientes/vei_home&id={$user_id}";
            endif;
            break;

        case 'manager_marca':
            $MarcaId = $PostData['marca_id'];
            unset($PostData['marca_id'], $PostData['marca_cover']);
            $PostData['marca_status'] = (!empty($PostData['marca_status']) ? $PostData['marca_status'] : '0');
            if (!empty($_FILES['marca_cover'])):
                $UserThumb = $_FILES['marca_cover'];
                $Read->FullRead("SELECT marca_cover FROM " . DB_VEICULOS_MARCA . " WHERE marca_id = :id", "id={$MarcaId}");
                if ($Read->getResult()):
                    if (file_exists("../../uploads/{$Read->getResult()[0]['marca_cover']}") && !is_dir("../../uploads/{$Read->getResult()[0]['marca_cover']}")):
                        unlink("../../uploads/{$Read->getResult()[0]['marca_cover']}");
                    endif;
                endif;
                $Upload->Image($UserThumb, $MarcaId . "-" . Check::Name($PostData['marca_title']) . '-' . time(), 600);
                if ($Upload->getResult()):
                    $PostData['marca_cover'] = $Upload->getResult();
                else:
                    $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR FOTO:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG ou PNG para enviar como foto!", E_USER_WARNING);
                    echo json_encode($jSON);
                    return;
                endif;
            endif;
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. A Marca foi atualizada com sucesso!");
            $Update->ExeUpdate(DB_VEICULOS_MARCA, $PostData, "WHERE marca_id = :id", "id={$MarcaId}");
            $jSON['redirect'] = "dashboard.php?wc=configuracoes/mar_home";
            break;

        case 'delete_marca':
            $MarcaId = $PostData['id'];
            $Read->ExeRead(DB_VEICULOS_MARCA, "WHERE marca_id = :id", "id={$MarcaId}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>MARCA NÃO EXISTE:</b> Olá {$_SESSION['userLogin']['user_name']}, você tentou deletar uma Marca que não existe ou já foi removida!", E_USER_WARNING);
            else:
                extract($Read->getResult()[0]);
                $Delete->ExeDelete(DB_VEICULOS_MARCA, "WHERE marca_id = :id", "id={$MarcaId}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>MARCA REMOVIDA COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=configuracoes/mar_home";
            endif;
            break;

        case 'manager_modelo':
            $ModId = $PostData['mod_id'];
            unset($PostData['mod_id'], $PostData['mod_cover']);
            $PostData['mod_status'] = (!empty($PostData['mod_status']) ? $PostData['mod_status'] : '0');
            if (!empty($_FILES['mod_cover'])):
                $UserThumb = $_FILES['mod_cover'];
                $Read->FullRead("SELECT mod_cover FROM " . DB_VEICULOS_MODELO . " WHERE mod_id = :id", "id={$ModId}");
                if ($Read->getResult()):
                    if (file_exists("../../uploads/{$Read->getResult()[0]['mod_cover']}") && !is_dir("../../uploads/{$Read->getResult()[0]['mod_cover']}")):
                        unlink("../../uploads/{$Read->getResult()[0]['mod_cover']}");
                    endif;
                endif;
                $Upload->Image($UserThumb, $ModId . "-" . Check::Name($PostData['mod_title']) . '-' . time(), 600);
                if ($Upload->getResult()):
                    $PostData['mod_cover'] = $Upload->getResult();
                else:
                    $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR FOTO:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG ou PNG para enviar como foto!", E_USER_WARNING);
                    echo json_encode($jSON);
                    return;
                endif;
            endif;
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. O Modelo foi atualizado com sucesso!");
            $Update->ExeUpdate(DB_VEICULOS_MODELO, $PostData, "WHERE mod_id = :id", "id={$ModId}");
            $jSON['redirect'] = "dashboard.php?wc=configuracoes/mod_home";
            break;

        case 'delete_modelo':
            $ModId = $PostData['id'];
            $Read->ExeRead(DB_VEICULOS_MODELO, "WHERE mod_id = :id", "id={$ModId}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>MODELO NÃO EXISTE:</b> Olá {$_SESSION['userLogin']['user_name']}, você tentou deletar um Modelo que não existe ou já foi removido!", E_USER_WARNING);
            else:
                extract($Read->getResult()[0]);
                $Delete->ExeDelete(DB_VEICULOS_MODELO, "WHERE mod_id = :id", "id={$ModId}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>MODELO REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=configuracoes/mod_home";
            endif;
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
