<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = 3;//LEVEL_WC_BANNERS;

if (!APP_BANNERS || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPPSSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Banners';
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
        //GERENCIA
        case 'manager':
            $BannerId = $PostData['banner_id'];
            $image = (!empty($_FILES['banner_image']) ? $_FILES['banner_image'] : null);

            unset($PostData['banner_id'], $PostData['banner_image']);
            $Read->FullRead("SELECT banner_image FROM " . DB_BANNERS . " WHERE banner_id = :id", "id={$BannerId}");

            if (empty($image) && (!$Read->getResult() || !$Read->getResult()[0]['banner_image'])):
                $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO CADASTRAR:</b> Favor envie uma imagem de banner nas medidas de 430x310px!', E_USER_ERROR);
            elseif (in_array('', $PostData)):
                $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO CADASTRAR:</b> Para atualizar o banner, favor preencha todos os campos!', E_USER_ERROR);
                $jSON['error'] = true;
            else:
                $PostData['banner_date'] = date('Y-m-d H:i:s');
                $PostData['banner_status'] = (!empty($PostData['banner_status']) ? $PostData['banner_status'] : '0');

                if (!empty($image)):
                    if ($Read->getResult() && !empty($Read->getResult()[0]['banner_image']) && file_exists("../../uploads/{$Read->getResult()[0]['banner_image']}") && !is_dir("../../uploads/{$Read->getResult()[0]['banner_image']}")):
                        unlink("../../uploads/{$Read->getResult()[0]['banner_image']}");
                    endif;

                    $Upload = new Upload('../../uploads/');
                    $Upload->Image($image, Check::Name($PostData['banner_title']), 430, 'banners');
                    $PostData['banner_image'] = $Upload->getResult();
                endif;

                $Update->ExeUpdate(DB_BANNERS, $PostData, "WHERE banner_id = :id", "id={$BannerId}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>Tudo certo {$_SESSION['userLogin']['user_name']}</b>: O banner foi atualizado com sucesso!");
            endif;
            break;

        //DELETA
        case 'delete':
            $BannerId = $PostData['del_id'];
            $Read->FullRead("SELECT banner_image FROM " . DB_BANNERS . " WHERE banner_id = :id", "id={$BannerId}");
            if ($Read->getResult()):
                $image = (!empty($Read->getResult()[0]['banner_image']) ? $Read->getResult()[0]['banner_image'] : null);
                if ($image && file_exists("../../uploads/{$image}") && !is_dir("../../uploads/{$image}")):
                    unlink("../../uploads/{$image}");
                endif;
            endif;

            $Delete->ExeDelete(DB_BANNERS, "WHERE banner_id = :id", "id={$BannerId}");
            $jSON['success'] = true;
            break;

        case 'get_line':
            $id = $PostData['id'];
            $page = $PostData['page'];
            $jSON['banner_line'] = null;

            $Read->FullRead("SELECT banner_line FROM " . DB_BANNERS . " WHERE banner_id = :id", "id={$id}");

            foreach (new DirectoryIterator('../../themes/' . THEME) as $file):
                if ($file->getExtension() === 'php'):
                    $filename = str_replace('.php', '', $file->getFilename());
                    $path = $file->getPathname();
                    $contentFile = file_get_contents($path);
                    $countLine = substr_count($contentFile, 'count_line_banner');

                    if ($page == $filename && $countLine >= 1):
                        $jSON['banner_line'] = "<option value=''>Selecione a Linha</option>";
                        for ($i = 1; $i <= $countLine; $i ++):
                            $jSON['banner_line'] .= "<option value='{$i}'" . ($Read->getResult()[0]['banner_line'] == $i ? ' selected="selected"' : '') . ">$i</option>";
                        endfor;
                    endif;
                endif;
            endforeach;

            if ($jSON['banner_line'] == null):
                $jSON['banner_line'] = "<option value=''>Selecione a Página</option>";
            endif;

            $jSON['success'] = true;
            break;

        case 'get_size':
            $page = $PostData['page'];
            $line = $PostData['line'];
            $jSON['banner_size'] = null;

            $Read->FullRead("SELECT banner_size FROM " . DB_BANNERS . " WHERE banner_line = :line AND banner_page = :page", "line={$line}&page={$page}");
            if ($Read->getResult() && !empty($Read->getResult()[0]['banner_size'])):
                if ($Read->getResult()[0]['banner_size'] == '1'):
                    $jSON['banner_size'] .= "<option value=''>Selecione a Dimensão</option>";
                    $jSON['banner_size'] .= "<option value='1'" . ($Read->getResult()[0]['banner_size'] == '1' ? ' selected="selected"' : '') . ">1 ÷ 1</option>";
                elseif ($Read->getResult()[0]['banner_size'] == '2'):
                    $jSON['banner_size'] .= "<option value=''>Selecione a Dimensão</option>";
                    $jSON['banner_size'] .= "<option value='2'" . ($Read->getResult()[0]['banner_size'] == '2' ? ' selected="selected"' : '') . ">1 ÷ 2</option>";
                elseif ($Read->getResult()[0]['banner_size'] == '3'):
                    $jSON['banner_size'] .= "<option value=''>Selecione a Dimensão</option>";
                    $jSON['banner_size'] .= "<option value='3'" . ($Read->getResult()[0]['banner_size'] == '3' ? ' selected="selected"' : '') . ">1 ÷ 3</option>";
                elseif ($Read->getResult()[0]['banner_size'] == '4'):
                    $jSON['banner_size'] .= "<option value=''>Selecione a Dimensão</option>";
                    $jSON['banner_size'] .= "<option value='4'" . ($Read->getResult()[0]['banner_size'] == '4' ? ' selected="selected"' : '') . ">1 ÷ 4</option>";
                endif;
            else:
                if (!empty($line)):
                    $jSON['banner_size'] .= "<option value=''>Selecione a Dimensão</option>";
                    $jSON['banner_size'] .= "<option value='1'>1 ÷ 1</option>";
                    $jSON['banner_size'] .= "<option value='2'>1 ÷ 2</option>";
                    $jSON['banner_size'] .= "<option value='3'>1 ÷ 3</option>";
                    $jSON['banner_size'] .= "<option value='4'>1 ÷ 4</option>";
                else:
                    $jSON['banner_size'] .= "<option value=''>Selecione a Linha</option>";
                endif;
            endif;

            $jSON['success'] = true;
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
