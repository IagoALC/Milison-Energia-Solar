<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = 3; //LEVEL_WC_PRODUCTS;

if (!APP_PRODUCTS || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess) :
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Products';
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
            $PdtId = $PostData['pdt_id'];
            $PostData['pdt_status'] = 1;//(!empty($PostData['pdt_status']) ? $PostData['pdt_status'] : '0');
            $Read->ExeRead(DB_PDT, "WHERE pdt_id = :id", "id={$PdtId}");
            if (!$Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>Erro ao atualizar:</b> Desculpe {$_SESSION['userLogin']['user_name']}, mas não foi possível consultar o equipamento. Experimente atualizar a página!", E_USER_WARNING);
            else :
                $Product = $Read->getResult()[0];
                unset($PostData['pdt_id'], $PostData['pdt_cover'], $PostData['image']);
        
                if (!empty($_FILES['pdt_cover'])) :
                    $File = $_FILES['pdt_cover'];

                    if ($Product['pdt_cover'] && file_exists("../../uploads/{$Product['pdt_cover']}") && !is_dir("../../uploads/{$Product['pdt_cover']}")) :
                        unlink("../../uploads/{$Product['pdt_cover']}");
                    endif;

                    $Upload->Image($File, "{$PdtId}-{$PostData['pdt_name']}-" . time(), 1000);
                    if ($Upload->getResult()) :
                        $PostData['pdt_cover'] = $Upload->getResult();
                    else :
                        $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR CAPA:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG de 1000x1000px para a capa!", E_USER_WARNING);
                        echo json_encode($jSON);
                        return;
                    endif;
                endif;

                if (!empty($_FILES['image'])) :
                    $File = $_FILES['image'];
                    $gbFile = array();
                    $gbCount = count($File['type']);
                    $gbKeys = array_keys($File);
                    $gbLoop = 0;

                    for ($gb = 0; $gb < $gbCount; $gb++) :
                        foreach ($gbKeys as $Keys) :
                            $gbFiles[$gb][$Keys] = $File[$Keys][$gb];
                        endforeach;
                    endfor;

                    $jSON['gallery'] = null;
                    foreach ($gbFiles as $UploadFile) :
                        $gbLoop++;
                        $Upload->Image($UploadFile, "{$PdtId}-{$gbLoop}-" . time() . base64_encode(time()), 1000);
                        if ($Upload->getResult()) :
                            $gbCreate = ['product_id' => $PdtId, "image" => $Upload->getResult()];
                            $Create->ExeCreate(DB_PDT_GALLERY, $gbCreate);
                            $jSON['gallery'] .= "<img rel='Products' id='{$Create->getResult()}' alt='Imagem em {$PostData['pdt_title']}' title='Imagem em {$PostData['pdt_title']}' src='../uploads/{$Upload->getResult()}'/>";
                        endif;
                    endforeach;
                endif;
                
                 $Update->ExeUpdate(DB_PDT, $PostData, "WHERE pdt_id = :id", "id={$PdtId}");
                 $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO:</b> Olá {$_SESSION['userLogin']['user_name']}. O Equipamento foi atualizado com sucesso!");
                 $jSON['redirect'] = "dashboard.php?wc=equipamentos/home";
            endif;
            break;

        case 'sendimage':
            $NewImage = $_FILES['image'];
            $Read->FullRead("SELECT pdt_title, pdt_name FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$PostData['pdt_id']}");
            if (!$Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR IMAGEM:</b> Desculpe {$_SESSION['userLogin']['user_name']}, mas não foi possível identificar o equipamento vinculado!", E_USER_WARNING);
            else :
                $Upload = new Upload('../../uploads/');
                $Upload->Image($NewImage, $PostData['pdt_id'] . '-' . time(), IMAGE_W);
                if ($Upload->getResult()) :
                    $PostData['product_id'] = $PostData['pdt_id'];
                    $PostData['image'] = $Upload->getResult();
                    unset($PostData['pdt_id']);

                    $Create->ExeCreate(DB_PDT_IMAGE, $PostData);
                    $jSON['tinyMCE'] = "<img title='{$Read->getResult()[0]['pdt_title']}' alt='{$Read->getResult()[0]['pdt_title']}' src='../uploads/{$PostData['image']}'/>";
                else :
                    $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR IMAGEM:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG ou PNG para inserir no equipamento!", E_USER_WARNING);
                endif;
            endif;
            break;

        case 'delete':
            $PdtId = $PostData['id'];
            $Read->FullRead("SELECT pdt_id FROM " . DB_ORDERS_ITEMS . " WHERE pdt_id = :id", "id={$PdtId}");
            $PdtOrder = $Read->getResult();

            $Read->ExeRead(DB_PDT, "WHERE pdt_id = :id", "id={$PdtId}");
            if ($PdtOrder) :
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPSS:</b> Desculpe {$_SESSION['userLogin']['user_name']}. Não foi possível deletar pois existem pedidos para esse equipamento!", E_USER_WARNING);
            elseif (!$Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPSS:</b> Desculpe {$_SESSION['userLogin']['user_name']}. Não foi possível deletar pois o equipamento não existe ou foi removido recentemente!", E_USER_WARNING);
            else :
                $Product = $Read->getResult()[0];
                $PdtCover = "../../uploads/{$Product['pdt_cover']}";

                if (file_exists($PdtCover) && !is_dir($PdtCover)) :
                    unlink($PdtCover);
                endif;

                $Read->ExeRead(DB_PDT_IMAGE, "WHERE product_id = :id", "id={$Product['pdt_id']}");
                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $PdtImage) :
                        $PdtImageIs = "../../uploads/{$PdtImage['image']}";
                        if (file_exists($PdtImageIs) && !is_dir($PdtImageIs)) :
                            unlink($PdtImageIs);
                        endif;
                    endforeach;
                    $Delete->ExeDelete(DB_PDT_IMAGE, "WHERE product_id = :id", "id={$Product['pdt_id']}");
                endif;

                $Read->ExeRead(DB_PDT_GALLERY, "WHERE product_id = :id", "id={$Product['pdt_id']}");
                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $PdtGB) :
                        $PdtGBImage = "../../uploads/{$PdtGB['image']}";
                        if (file_exists($PdtGBImage) && !is_dir($PdtGBImage)) :
                            unlink($PdtGBImage);
                        endif;
                    endforeach;
                    $Delete->ExeDelete(DB_PDT_GALLERY, "WHERE product_id = :id", "id={$Product['pdt_id']}");
                endif;

                $Delete->ExeDelete(DB_PDT, "WHERE pdt_id = :id", "id={$Product['pdt_id']}");
                $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :id", "id={$Product['pdt_id']}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>EQUIPAMENTO REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=equipamentos/home";
            endif;
            break;

        case 'gbremove':
            $Read->FullRead("SELECT image FROM " . DB_PDT_GALLERY . " WHERE id = :id", "id={$PostData['img']}");
            if ($Read->getResult()) :
                $ImageRemove = "../../uploads/{$Read->getResult()[0]['image']}";
                if (file_exists($ImageRemove) && !is_dir($ImageRemove)) :
                    unlink($ImageRemove);
                endif;
                $Delete->ExeDelete(DB_PDT_GALLERY, "WHERE id = :id", "id={$PostData['img']}");
                $jSON['success'] = true;
            endif;
            break;

        case 'cat_manager':
            $PostData = array_map('strip_tags', $PostData);
            $CatId = $PostData['cat_id'];
            unset($PostData['cat_id']);

            $PostData['cat_name'] = Check::Name($PostData['cat_title']);
            //$PostData['cat_parent'] = ($PostData['cat_parent'] ? $PostData['cat_parent'] : null);

            $Read->FullRead("SELECT cat_id FROM " . DB_PDT_CATS . " WHERE cat_name = :cn AND cat_id != :ci", "cn={$PostData['cat_name']}&ci={$CatId}");

            if ($Read->getResult()) :
                $PostData['cat_name'] = $PostData['cat_name'] . '-' . $CatId;
            endif;

            $Update->ExeUpdate(DB_PDT_CATS, $PostData, "WHERE cat_id = :id", "id={$CatId}");
            $Read->FullRead("SELECT cat_id, cat_parent FROM " . DB_PDT_CATS . " WHERE cat_parent = :parent", "parent={$CatId}");

            function loopCat()
            {
                global $Read, $Update;

                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $CAT) :
                        $Read->LinkResult(DB_PDT_CATS, 'cat_id', $CAT['cat_parent'], 'cat_tree');
                        $arrUpdate = ['cat_tree' => ($Read->getResult()[0]['cat_tree'] ? $Read->getResult()[0]['cat_tree'] . ',' . $CAT['cat_parent'] : $CAT['cat_parent'])];
                        $Update->ExeUpdate(DB_PDT_CATS, $arrUpdate, "WHERE cat_id = :id", "id={$CAT['cat_id']}");

                        $Read->FullRead("SELECT cat_id, cat_parent FROM " . DB_PDT_CATS . " WHERE cat_parent = :parent", "parent={$CAT['cat_id']}");
                        if ($Read->getResult()) :
                            loopCat();
                        endif;
                    endforeach;
                endif;
            }

            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO: </b> A categoria <b>{$PostData['cat_title']}</b> foi atualizada com sucesso!");
            $jSON['redirect'] = "dashboard.php?wc=equipamentos/cat_home";
            break;

        case 'cat_delete':
            $CatId = $PostData['id'];
            $Read->FullRead("SELECT pdt_id FROM " . DB_PDT . " WHERE pdt_category = :cat OR pdt_subcategory = :cat", "cat={$CatId}");
            if ($Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS: </b>Desculpe {$_SESSION['userLogin']['user_name']}, mas não é possível remover categorias com equipamentos cadastrados nela!", E_USER_WARNING);
                $jSON['redirect'] = "dashboard.php?wc=equipamentos/cat_home";
            else :
                $Read->FullRead("SELECT cat_id FROM " . DB_PDT_CATS . " WHERE cat_parent = :cat", "cat={$CatId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS: </b>Desculpe {$_SESSION['userLogin']['user_name']}, mas não é possível remover categorias com subcategorias ligadas a ela!", E_USER_WARNING);
                    $jSON['redirect'] = "dashboard.php?wc=equipamentos/cat_home";
                else :
                    $Delete->ExeDelete(DB_PDT_CATS, "WHERE cat_id = :cat", "cat={$CatId}");
                    $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>CATEGORIA REMOVIDA COM SUCESSO!</b>");
                    $jSON['redirect'] = "dashboard.php?wc=equipamentos/cat_home";
                endif;
            endif;
            break;

        case 'brand_manager':
            $BrandId = $PostData['brand_id'];
            $PostData['brand_name'] = Check::Name($PostData['brand_title']);

            $Read->FullRead("SELECT brand_id FROM " . DB_PDT_BRANDS . " WHERE brand_name = :nm AND brand_id != :id", "nm={$PostData['brand_name']}&id={$BrandId}");
            if ($Read->getResult()) :
                $PostData['brand_name'] = "{$PostData['brand_name']}-{$BrandId}";
            endif;

            unset($PostData['brand_id']);
            $Update->ExeUpdate(DB_PDT_BRANDS, $PostData, "WHERE brand_id = :id", "id={$BrandId}");
            $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO: </b> A marca ou modelo <b>{$PostData['brand_title']}</b> foi atualizada com sucesso!");
            $jSON['redirect'] = "dashboard.php?wc=equipamentos/mod_home";
            break;

        case 'brand_remove':
            $BrandId = $PostData['id'];
            $Read->FullRead("SELECT pdt_id FROM " . DB_PDT . " WHERE pdt_brand = :brand", "brand={$BrandId}");
            if ($Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS: </b>Desculpe {$_SESSION['userLogin']['user_name']}, mas não é possível remover uma marca quando existem equipamentos cadastrados com ela!", E_USER_WARNING);
                $jSON['redirect'] = "dashboard.php?wc=equipamentos/mod_home";
            else :
                $Delete->ExeDelete(DB_PDT_BRANDS, "WHERE brand_id = :brand", "brand={$BrandId}");
                $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>MODELO REMOVIDO COM SUCESSO!</b>");
                $jSON['redirect'] = "dashboard.php?wc=equipamentos/mod_home";
            endif;
            break;

        case 'cupom_manage':
            if (in_array('', $PostData)) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Favor, preencha todos os campos para atualizar o cupom de desconto!", E_USER_WARNING);
            elseif (!Check::Data($PostData['cp_start']) || !Check::Data($PostData['cp_end'])) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> A data de início ou de término parecem não estar válidas!", E_USER_WARNING);
            else :
                $CouponId = $PostData['cp_id'];
                unset($PostData['cp_id']);

                $PostData['cp_start'] = (!empty($PostData['cp_start']) ? Check::Data($PostData['cp_start']) : date('Y-m-d H:i:s'));
                $PostData['cp_end'] = (!empty($PostData['cp_end']) ? Check::Data($PostData['cp_end']) : date('Y-m-d H:i:s', strtotime("+30days")));
                $Update->ExeUpdate(DB_PDT_COUPONS, $PostData, "WHERE cp_id = :id", "id={$CouponId}");
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>Tudo pronto:</b> Seu cupom de {$PostData['cp_discount']}% de desconto foi cadastrado com sucesso!");
            endif;
            break;

        case 'coupon_remove':
            $Delete->ExeDelete(DB_PDT_COUPONS, "WHERE cp_id = :del_id", "del_id={$PostData['del_id']}");
            $jSON['success'] = true;
            break;

        case 'pdt_stock':
            $PdtId = $PostData['pdt_id'];
            unset($PostData['pdt_id']);

            $SockTotal = 0;
            $jSON['res'] = null;
            foreach ($PostData as $SizeKey => $SizeValue) :
                $SockTotal += $SizeValue;
                $SizeKey = str_replace("_", " ", $SizeKey);

                $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pd AND stock_code = :cd", "pd={$PdtId}&cd={$SizeKey}");
                if (!$Read->getResult() && $SizeValue >= 1) :
                    $CreateStock = ['pdt_id' => $PdtId, 'stock_code' => "{$SizeKey}", 'stock_inventory' => $SizeValue, 'stock_sold' => 0];
                    $Create->ExeCreate(DB_PDT_STOCK, $CreateStock);
                else :
                    $UpdateStock = ['stock_inventory' => $SizeValue];
                    $Update->ExeUpdate(DB_PDT_STOCK, $UpdateStock, "WHERE pdt_id = :pd AND stock_code = :cd", "pd={$PdtId}&cd={$SizeKey}");
                endif;
            endforeach;

            //REMOVE NOT RELATED STOCK
            $StockRelated = str_replace("_", " ", "'" . implode("', '", array_keys($PostData)) . "'");
            $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :id AND stock_code NOT IN({$StockRelated})", "id={$PdtId}");

            //CLEAR ZERO STOCK
            $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :id AND stock_inventory = '0' AND stock_sold = '0'", "id={$PdtId}");

            //UPDATE GENERAL STOCK
            $UpdateGeneralStock = ['pdt_inventory' => $SockTotal];
            $Update->ExeUpdate(DB_PDT, $UpdateGeneralStock, "WHERE pdt_id = :id", "id={$PdtId}");

            $jSON['content'] = $SockTotal;
            $jSON['trigger'] = "<div class='trigger trigger_success trigger_ajax'><b class='icon icon-checkmark'>Estoque atualizado com sucesso!</b></div>";
            break;

            /* ATTR GROUP CREATE */
            /* CUSTOM BY ALISSON */
        case 'group_create':
            $PostData['group_name'] = Check::Name($PostData['group_title']);
            $PostData['group_created'] = date('Y-m-d H:i:s');

            if (in_array('', $PostData)) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Favor, para cadastrar este grupo preencha todos os campos!", E_USER_WARNING);
            else :
                $Read->FullRead("SELECT group_name FROM " . DB_PDT_GROUPS_ATTR . " WHERE group_name = :name", "name={$PostData['group_name']}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Desculpe, este grupo de atributo já existe!", E_USER_WARNING);
                else :
                    $Create->ExeCreate(DB_PDT_GROUPS_ATTR, $PostData);

                    $Read->FullRead("SELECT group_id, group_type, group_title FROM " . DB_PDT_GROUPS_ATTR . " WHERE group_id = :id", "id={$Create->getResult()}");
                    if ($Read->getResult()) :
                        $jSON['add_content'] = ['.j_group_target' => "<div class='attr_group box box25 wc_draganddrop' callback='Products' callback_action='group_order' id='{$Create->getResult()}'><div class='panel_header default al_center'><h2>{$Read->getResult()[0]['group_title']}</h2></div><div class='attr_group_actions'><span class='j_open_modal btn btn_green icon-plus icon-notext' title='Novo Atributo' data-type-modal='modal_attr_{$Read->getResult()[0]['group_type']}' data-group-id='{$Create->getResult()}'></span> <span rel='attr_group' class='j_delete_action btn btn_red icon-bin icon-notext' title='Remover Grupo' id='{$Create->getResult()}'></span> <span rel='attr_group' callback='Products' callback_action='group_remove' class='j_delete_action_confirm btn btn_yellow icon-bin icon-notext' title='Remover Grupo' style='display: none;' id='{$Create->getResult()}'></span></div><div class='attr_group_body'><div class='attr_group_scroll j_attr_item_target_{$Read->getResult()[0]['group_id']}'></div></div></div>"];
                        $jSON['trigger'] = AjaxErro("<b class='icon-info'>Tudo pronto:</b> Um novo grupo de atributos foi cadastrado com sucesso!");
                        $jSON['clear'] = true;
                    endif;
                endif;
            endif;
            break;

            /* ATTR GROUP REMOVE */
            /* CUSTOM BY ALISSON */
        case 'group_remove':
            $AttrGroupId = $PostData['del_id'];

            $Read->FullRead("SELECT attr_color_id FROM " . DB_PDT_ATTR_COLORS . " WHERE group_id = :group", "group={$AttrGroupId}");
            if ($Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Desculpe, para poder remover este grupo antes remova todos os atributos pertencentes a ele!", E_USER_WARNING);
                break;
            endif;

            $Read->FullRead("SELECT attr_size_id FROM " . DB_PDT_ATTR_SIZES . " WHERE group_id = :group", "group={$AttrGroupId}");
            if ($Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Desculpe, para poder remover este grupo antes remova todos os atributos pertencentes a ele!", E_USER_WARNING);
                break;
            endif;

            $Read->FullRead("SELECT attr_print_id FROM " . DB_PDT_ATTR_PRINTS . " WHERE group_id = :group", "group={$AttrGroupId}");
            if ($Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Desculpe, para poder remover este grupo antes remova todos os atributos pertencentes a ele!", E_USER_WARNING);
                break;
            endif;

            $Delete->ExeDelete(DB_PDT_GROUPS_ATTR, "WHERE group_id = :group", "group={$AttrGroupId}");
            $jSON['success'] = true;
            break;

            /* ATTR GROUP ORDER */
            /* CUSTOM BY ALISSON */
        case 'group_order':
            if (is_array($PostData['Data'])) :
                foreach ($PostData['Data'] as $RE) :
                    $UpdateGroup = ['group_order' => $RE[1]];
                    $Update->ExeUpdate(DB_PDT_GROUPS_ATTR, $UpdateGroup, "WHERE group_id = :group", "group={$RE[0]}");
                endforeach;

                $jSON['sucess'] = true;
            endif;
            break;

            /* ATTR SIZE CREATE */
            /* CUSTOM BY ALISSON */
        case 'attr_size_create':
            if (in_array('', $PostData)) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Favor, para cadastrar este atributo preencha todos os campos!", E_USER_WARNING);
            else :
                $PostData['attr_size_code'] = trim($PostData['attr_size_code']);

                $Read->FullRead("SELECT attr_size_id FROM " . DB_PDT_ATTR_SIZES . " WHERE group_id = :group AND attr_size_code = :code", "group={$PostData['group_id']}&code={$PostData['attr_size_code']}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Desculpe, este atributo já existe para este grupo!</span>", E_USER_WARNING);
                else :
                    $PostData['attr_size_created'] = date('Y-m-d H:i:s');
                    $Create->ExeCreate(DB_PDT_ATTR_SIZES, $PostData);

                    $jSON['add_content'] = [".j_attr_item_target_{$PostData['group_id']}" => "<div class='attr_group_item attr_size_{$Create->getResult()}' id='{$Create->getResult()}'><div class='attr_group_item_code' title='{$PostData['attr_size_title']}'><span class='attr_group_item_code_size'>{$PostData['attr_size_code']}</span><span rel='attr_size_{$Create->getResult()}' class='j_delete_action attr_group_item_remove icon-cancel-circle icon-notext' id='{$Create->getResult()}'></span><span rel='attr_size_{$Create->getResult()}' callback='Products' callback_action='attr_size_remove' class='j_delete_action_confirm attr_group_item_remove icon-cancel-circle icon-notext' style='display: none;' id='{$Create->getResult()}'></span></div></div>"];
                    $jSON['trigger'] = AjaxErro("<b class='icon-info'>Tudo pronto:</b> Um novo atributo foi cadastrado com sucesso!");
                    $jSON['clear'] = true;
                endif;
            endif;
            break;

            /* ATTR SIZE REMOVE */
            /* CUSTOM BY ALISSON */
        case 'attr_size_remove':
            $AttrId = $PostData['del_id'];

            $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE size_id = :id GROUP BY pdt_id", "id={$AttrId}");
            if ($Read->getRowCount()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> <b>{$Read->getRowCount()}</b> " . ($Read->getRowCount() == 1 ? "equipamento está" : "equipamentos estão") . " vinculado a este atributo, portanto <b>não</b> é possível removê-lo agora!", E_USER_WARNING);
            else :
                $Read->FullRead('SELECT attr_size_id FROM ' . DB_PDT_ATTR_SIZES . ' WHERE attr_size_id = :id', "id={$AttrId}");
                if (!$Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPSS:</b> Não foi possível remover este atributo pois ele não existe ou já foi removido recentemente!", E_USER_WARNING);
                else :
                    $Delete->ExeDelete(DB_PDT_ATTR_SIZES, "WHERE attr_size_id = :attr", "attr={$AttrId}");
                    $jSON['success'] = true;
                endif;
            endif;
            break;

            /* ATTR COLOR CREATE */
            /* CUSTOM BY ALISSON */
        case 'attr_color_create':
            $PostData['attr_color_code'] = array_filter($PostData['attr_color_code']);
            $PostData['attr_color_title'] = array_filter($PostData['attr_color_title']);

            if (!$PostData['attr_color_code'] || !$PostData['attr_color_title'] || (count($PostData['attr_color_code']) != count($PostData['attr_color_title']))) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Informe as cores e seus nomes respectivamente abaixo!", E_USER_WARNING);
                break;
            endif;

            foreach ($PostData['attr_color_code'] as $COLOR) :
                if (!preg_match("((#)[0-9a-fA-F]{6})", $COLOR)) :
                    $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Alguma cor não está correta! Certifique-se que todas as cores estão no formato hexadecimal, se contém 6 dígitos e se o sinal <b>#</b> precedo o código da cor.", E_USER_WARNING);
                    break;
                endif;
            endforeach;

            $PostData['attr_color_code'] = implode(',', array_slice(array_map('trim', $PostData['attr_color_code']), 0, 4));
            $PostData['attr_color_title'] = implode(', ', array_slice(array_map('trim', $PostData['attr_color_title']), 0, 4));

            if (strpos($PostData['attr_color_code'], ',')) :
                $arrColors = explode(',', $PostData['attr_color_code']);
                $bgColor = (count($arrColors) == 2 ? "style='background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%);'" : (count($arrColors) == 3 ? "style='background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]});'" : "style='background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]});'"));
            else :
                $PostData['attr_color_code'] = trim($PostData['attr_color_code']);
                $bgColor = "style='background-color: {$PostData['attr_color_code']};'";
            endif;

            $Read->FullRead("SELECT attr_color_id FROM " . DB_PDT_ATTR_COLORS . " WHERE group_id = :group AND attr_color_code = :code", "group={$PostData['group_id']}&code={$PostData['attr_color_code']}");
            if ($Read->getResult()) :
                $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Desculpe, este atributo já existe para este grupo!</span>", E_USER_WARNING);
            else :
                $PostData['attr_color_created'] = date('Y-m-d H:i:s');
                $Create->ExeCreate(DB_PDT_ATTR_COLORS, $PostData);

                $jSON['add_content'] = [".j_attr_item_target_{$PostData['group_id']}" => "<div class='attr_group_item attr_color_{$Create->getResult()}' id='{$Create->getResult()}'><div {$bgColor} class='attr_group_item_code' title='{$PostData['attr_color_title']}'><span rel='attr_color_{$Create->getResult()}' class='j_delete_action attr_group_item_remove icon-cancel-circle icon-notext' id='{$Create->getResult()}'></span><span rel='attr_color_{$Create->getResult()}' callback='Products' callback_action='attr_color_remove' class='j_delete_action_confirm attr_group_item_remove icon-cancel-circle icon-notext' style='display: none;' id='{$Create->getResult()}'></span></div></div>"];
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>Tudo pronto:</b> Um novo atributo foi cadastrado com sucesso!");
                $jSON['reset'] = ['.j_attr_color_one', '.j_attr_color_two', '.j_attr_color_three', '.j_attr_color_four'];
                $jSON['pickr'] = true;
                $jSON['clear'] = true;
            endif;
            break;

            /* ATTR COLOR REMOVE */
            /* CUSTOM BY ALISSON */
        case 'attr_color_remove':
            $AttrId = $PostData['del_id'];

            $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE color_id = :id GROUP BY pdt_id", "id={$AttrId}");
            if ($Read->getRowCount()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> <b>{$Read->getRowCount()}</b> " . ($Read->getRowCount() == 1 ? "equipamento está" : "equipamentos estão") . " vinculado a este atributo, portanto <b>não</b> é possível removê-lo agora!", E_USER_WARNING);
            else :
                $Read->FullRead('SELECT attr_color_id FROM ' . DB_PDT_ATTR_COLORS . ' WHERE attr_color_id = :id', "id={$AttrId}");
                if (!$Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPSS:</b> Não foi possível remover este atributo pois ele não existe ou já foi removido recentemente!", E_USER_WARNING);
                else :
                    $Delete->ExeDelete(DB_PDT_ATTR_COLORS, "WHERE attr_color_id = :attr", "attr={$AttrId}");
                    $jSON['success'] = true;
                endif;
            endif;
            break;

            /* ATTR PRINT CREATE */
            /* CUSTOM BY ALISSON */
        case 'attr_print_create':
            if (in_array('', $PostData)) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> Favor, para cadastrar este atributo preencha todos os campos!", E_USER_WARNING);
            else :
                $PostData['attr_print_created'] = date('Y-m-d H:i:s');
                $Create->ExeCreate(DB_PDT_ATTR_PRINTS, $PostData);

                if (!empty($_FILES['attr_print_code'])) :
                    $File = $_FILES['attr_print_code'];

                    $Upload->Image($File, $Create->getResult(), null, 'prints');
                    if ($Upload->getResult()) :
                        $Update->ExeUpdate(DB_PDT_ATTR_PRINTS, ['attr_print_code' => $Upload->getResult()], 'WHERE attr_print_id = :id', "id={$Create->getResult()}");
                        $bg_print = "style='background: url(" . BASE . "/uploads/{$Upload->getResult()}) center / cover no-repeat;'";
                    else :
                        $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR CAPA:</b> Olá {$_SESSION['userLogin']['user_name']}, selecione uma imagem JPG, JPEG OU PNG para a capa!", E_USER_WARNING);
                        echo json_encode($jSON);
                        return;
                    endif;
                endif;

                $jSON['add_content'] = [".j_attr_item_target_{$PostData['group_id']}" => "<div class='attr_group_item attr_print_{$Create->getResult()}' id='{$Create->getResult()}'><div {$bg_print} class='attr_group_item_code' title='{$PostData['attr_print_title']}'><span rel='attr_print_{$Create->getResult()}' class='j_delete_action attr_group_item_remove icon-cancel-circle icon-notext' id='{$Create->getResult()}'></span><span rel='attr_print_{$Create->getResult()}' callback='Products' callback_action='attr_print_remove' class='j_delete_action_confirm attr_group_item_remove icon-cancel-circle icon-notext' style='display: none;' id='{$Create->getResult()}'></span></div></div>"];
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>Tudo pronto:</b> Um novo atributo foi cadastrado com sucesso!");
                $jSON['clear'] = true;
            endif;
            break;

            /* ATTR PRINT REMOVE */
            /* CUSTOM BY ALISSON */
        case 'attr_print_remove':
            $AttrId = $PostData['del_id'];

            $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE print_id = :id GROUP BY pdt_id", "id={$AttrId}");
            if ($Read->getRowCount()) :
                $jSON['trigger'] = AjaxErro("<b class='icon-info'>OPSS:</b> <b>{$Read->getRowCount()}</b> " . ($Read->getRowCount() == 1 ? "equipamento está" : "equipamentos estão") . " vinculado a este atributo, portanto <b>não</b> é possível removê-lo agora!", E_USER_WARNING);
            else :
                $Read->FullRead('SELECT attr_print_id, attr_print_code FROM ' . DB_PDT_ATTR_PRINTS . ' WHERE attr_print_id = :id', "id={$AttrId}");
                if (!$Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPSS:</b> Não foi possível remover este atributo pois ele não existe ou já foi removido recentemente!", E_USER_WARNING);
                else :
                    if (file_exists("../../uploads/{$Read->getResult()[0]['attr_print_code']}") && !is_dir("../../uploads/{$Read->getResult()[0]['attr_print_code']}")) :
                        unlink("../../uploads/{$Read->getResult()[0]['attr_print_code']}");
                    endif;

                    $Delete->ExeDelete(DB_PDT_ATTR_PRINTS, "WHERE attr_print_id = :id", "id={$AttrId}");
                    $jSON['success'] = true;
                endif;
            endif;
            break;

            /* PDT SELECT TYPE GROUP */
            /* CUSTOM BY ALISSON */
        case 'form_type_group':
            $PdtId = $PostData['pdt_id'];
            $form = $PostData['callback_form'];
            unset($PostData['callback_form']);

            //Armazena conteúdo para povoar o formulário
            $form_content = null;

            $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NOT NULL", "pdt={$PdtId}");
            $readPdtColor = (!empty($Read->getResult()) ? true : false);

            if ($form == 'form_all_attr') :
                //Ler atributos de cores da tabela ws_products_attributes_color
                if (!empty($PostData['group_type_color'])) :
                    $Read->FullRead("SELECT attr_color_id, attr_color_code, attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE group_id = :group", "group={$PostData['group_type_color']}");
                else :
                    $Read->FullRead("SELECT attr_color_id, attr_color_code, attr_color_title FROM " . DB_PDT_ATTR_COLORS);
                endif;

                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $COLOR) :
                        $form_content .= "<div style='flex-basis: calc(100% / 3 - 10px);'>";

                        if (strpos($COLOR['attr_color_code'], ',')) :
                            $arrColors = explode(',', $COLOR['attr_color_code']);
                            $bgColor = (count($arrColors) == 2 ? "background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%);" : (count($arrColors) == 3 ? "background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]});" : "background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]});"));
                        else :
                            $bgColor = "background-color: {$COLOR['attr_color_code']};";
                        endif;

                        //Ler atributos de tamanhos da tabela ws_products_attributes_sizes
                        if (!empty($PostData['group_type_size'])) :
                            $Read->FullRead("SELECT attr_size_id, attr_size_code, attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE group_id = :group", "group={$PostData['group_type_size']}");
                        else :
                            $Read->FullRead("SELECT attr_size_id, attr_size_code, attr_size_title FROM " . DB_PDT_ATTR_SIZES);
                        endif;

                        foreach ($Read->getResult() as $SIZE) :
                            //Ler estoque do equipamento $PdtId na variação $SIZE['attr_size_id'] $COLOR['attr_color_id'] da tabela ws_products_stock
                            $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id = :size AND color_id = :color", "pdt={$PdtId}&size={$SIZE['attr_size_id']}&color={$COLOR['attr_color_id']}");
                            if ($Read->getResult()) :
                                $form_content .= "<label><span style='{$bgColor}' title='{$COLOR['attr_color_title']}'><input type='hidden' name='color_id[]' value='{$COLOR['attr_color_id']}'/></span><span title='{$SIZE['attr_size_title']}'>{$SIZE['attr_size_code']}<input type='hidden' name='size_id[]' value='{$SIZE['attr_size_id']}'/></span><input name='stock_inventory[]' type='number' min='0' value='{$Read->getResult()[0]['stock_inventory']}'></label>";
                            else :
                                $form_content .= "<label><span style='{$bgColor}' title='{$COLOR['attr_color_title']}'><input type='hidden' name='color_id[]' value='{$COLOR['attr_color_id']}'/></span><span title='{$SIZE['attr_size_title']}'>{$SIZE['attr_size_code']}<input type='hidden' name='size_id[]' value='{$SIZE['attr_size_id']}'/></span><input name='stock_inventory[]' type='number' min='0' value='0'></label>";
                            endif;
                        endforeach;

                        $form_content .= "</div>";
                    endforeach;
                endif;
            endif;

            if ($form == 'form_attr_print') :
                //Ler atributos de estampa da tabela ws_products_attributes_prints
                if (!empty($PostData['group_type_print'])) :
                    $Read->FullRead("SELECT attr_print_id, attr_print_code, attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE group_id = :group", "group={$PostData['group_type_print']}");
                else :
                    $Read->FullRead("SELECT attr_print_id, attr_print_code, attr_print_title FROM " . DB_PDT_ATTR_PRINTS);
                endif;

                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $PRINT) :
                        $form_content .= "<div style='flex-basis: calc(100% / 3 - 10px);'>";

                        $bg_print = "background: url(" . BASE . "/uploads/{$PRINT['attr_print_code']}) center / cover no-repeat;";

                        //Ler atributos de tamanhos da tabela ws_products_attributes_sizes
                        if (!empty($PostData['group_type_size'])) :
                            $Read->FullRead("SELECT attr_size_id, attr_size_code, attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE group_id = :group", "group={$PostData['group_type_size']}");
                        else :
                            $Read->FullRead("SELECT attr_size_id, attr_size_code, attr_size_title FROM " . DB_PDT_ATTR_SIZES);
                        endif;

                        foreach ($Read->getResult() as $SIZE) :
                            //Ler estoque do equipamento $PdtId na variação $SIZE['attr_size_id'] $PRINT['attr_print_id'] da tabela ws_products_stock
                            $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id = :size AND print_id = :print", "pdt={$PdtId}&size={$SIZE['attr_size_id']}&print={$PRINT['attr_print_id']}");
                            if ($Read->getResult()) :
                                $form_content .= "<label><span style='{$bg_print}' title='{$PRINT['attr_print_title']}'><input type='hidden' name='print_id[]' value='{$PRINT['attr_print_id']}'/></span><span title='{$SIZE['attr_size_title']}'>{$SIZE['attr_size_code']}<input type='hidden' name='size_id[]' value='{$SIZE['attr_size_id']}'/></span><input name='stock_inventory[]' type='number' min='0' value='{$Read->getResult()[0]['stock_inventory']}'></label>";
                            else :
                                $form_content .= "<label><span style='{$bg_print}' title='{$PRINT['attr_print_title']}'><input type='hidden' name='print_id[]' value='{$PRINT['attr_print_id']}'/></span><span title='{$SIZE['attr_size_title']}'>{$SIZE['attr_size_code']}<input type='hidden' name='size_id[]' value='{$SIZE['attr_size_id']}'/></span><input name='stock_inventory[]' type='number' min='0' value='0'></label>";
                            endif;
                        endforeach;

                        $form_content .= "</div>";
                    endforeach;
                endif;
            endif;

            if ($form == 'form_attr_color') :
                //Ler atributos de cores da tabela ws_products_attributes_colors
                if (!empty($PostData['group_type_color'])) :
                    $Read->FullRead("SELECT attr_color_id, attr_color_code, attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE group_id = :group", "group={$PostData['group_type_color']}");
                else :
                    $Read->FullRead("SELECT attr_color_id, attr_color_code, attr_color_title FROM " . DB_PDT_ATTR_COLORS);
                endif;

                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $COLOR) :
                        $form_content .= "<div style='flex-basis: calc(100% / 3 - 10px);'>";

                        if (strpos($COLOR['attr_color_code'], ',')) :
                            $arrColors = explode(',', $COLOR['attr_color_code']);
                            $bgColor = (count($arrColors) == 2 ? "background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%); background-image: linear-gradient(45deg, {$arrColors[0]}  50%, {$arrColors[1]} 50%);" : (count($arrColors) == 3 ? "background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]}); background-image: linear-gradient(45deg, {$arrColors[0]}  33%, {$arrColors[1]} 33%, {$arrColors[1]} 67%, {$arrColors[2]} 67%, {$arrColors[2]});" : "background-image: -webkit-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -moz-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -ms-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: -o-linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]}); background-image: linear-gradient(45deg, {$arrColors[0]}  25%, {$arrColors[1]} 25%, {$arrColors[1]} 50%, {$arrColors[2]} 50%, {$arrColors[2]} 75%, {$arrColors[3]} 75%, {$arrColors[3]});"));
                        else :
                            $bgColor = "background-color: {$COLOR['attr_color_code']};";
                        endif;

                        //Ler estoque do equipamento $PdtId quando a coluna size_id da tabela ws_products_stock for null
                        $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id = :color", "pdt={$PdtId}&color={$COLOR['attr_color_id']}");
                        if ($Read->getResult()) :
                            $form_content .= "<label><span style='{$bgColor}' title='{$COLOR['attr_color_title']}'><input type='hidden' name='color_id[]' value='{$COLOR['attr_color_id']}'/></span><span title='default'>default</span><input name='stock_inventory[]' type='number' min='0' value='{$Read->getResult()[0]['stock_inventory']}'></label>";
                        else :
                            $form_content .= "<label><span style='{$bgColor}' title='{$COLOR['attr_color_title']}'><input type='hidden' name='color_id[]' value='{$COLOR['attr_color_id']}'/></span><span title='default'>default</span><input name='stock_inventory[]' type='number' min='0' value='0'></label>";
                        endif;

                        $form_content .= "</div>";
                    endforeach;
                endif;
            endif;

            if ($form == 'form_attr_only_print') :
                //Ler atributos de estampa da tabela ws_products_attributes_prints
                if (!empty($PostData['group_type_print'])) :
                    $Read->FullRead("SELECT attr_print_id, attr_print_code, attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE group_id = :group", "group={$PostData['group_type_print']}");
                else :
                    $Read->FullRead("SELECT attr_print_id, attr_print_code, attr_print_title FROM " . DB_PDT_ATTR_PRINTS);
                endif;

                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $PRINT) :
                        $form_content .= "<div style='flex-basis: calc(100% / 3 - 10px);'>";

                        $bgPrint = "background: url(" . BASE . "/uploads/{$PRINT['attr_print_code']}) center / cover no-repeat;";

                        //Ler estoque do equipamento $PdtId quando a coluna size_id da tabela ws_products_stock for null
                        $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id = :print", "pdt={$PdtId}&print={$PRINT['attr_print_id']}");
                        if ($Read->getResult()) :
                            $form_content .= "<label><span style='{$bgPrint}' title='{$PRINT['attr_print_title']}'><input type='hidden' name='print_id[]' value='{$PRINT['attr_print_id']}'/></span><span title='default'>default</span><input name='stock_inventory[]' type='number' min='0' value='{$Read->getResult()[0]['stock_inventory']}'></label>";
                        else :
                            $form_content .= "<label><span style='{$bgPrint}' title='{$PRINT['attr_print_title']}'><input type='hidden' name='print_id[]' value='{$PRINT['attr_print_id']}'/></span><span title='default'>default</span><input name='stock_inventory[]' type='number' min='0' value='0'></label>";
                        endif;

                        $form_content .= "</div>";
                    endforeach;
                endif;
            endif;

            if ($form == 'form_attr_size') :
                //Ler atributos de tamanhos da tabela ws_products_attributes_sizes
                if (!empty($PostData['group_type_size'])) :
                    $Read->FullRead("SELECT attr_size_id, attr_size_code, attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE group_id = :group", "group={$PostData['group_type_size']}");
                else :
                    $Read->FullRead("SELECT attr_size_id, attr_size_code, attr_size_title FROM " . DB_PDT_ATTR_SIZES);
                endif;

                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $SIZE) :
                        $form_content .= "<div style='flex-basis: calc(100% / 3 - 10px);'>";

                        //Ler estoque do equipamento $PdtId na variação $SIZE['attr_size_id'] da tabela ws_products_stock
                        $Read->FullRead("SELECT stock_inventory, stock_sold FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id = :size", "pdt={$PdtId}&size={$SIZE['attr_size_id']}");
                        if ($Read->getResult() && !$readPdtColor) :
                            $form_content .= "<label title='{$SIZE['attr_size_title']}'><span>{$SIZE['attr_size_code']}<input type='hidden' name='size_id[]' value='{$SIZE['attr_size_id']}'/></span><input name='stock_inventory[]' type='number' min='0' value='{$Read->getResult()[0]['stock_inventory']}'><span class='cart'><b class='icon-cart'>" . str_pad($Read->getResult()[0]['stock_sold'], 2, 0, 0) . "</b></span></label>";
                        else :
                            $form_content .= "<label title='{$SIZE['attr_size_title']}'><span>{$SIZE['attr_size_code']}<input type='hidden' name='size_id[]' value='{$SIZE['attr_size_id']}'/></span><input name='stock_inventory[]' type='number' min='0' value='0'><span class='cart'><b class='icon-cart'>00</b></span></label>";
                        endif;

                        $form_content .= "</div>";
                    endforeach;
                endif;
            endif;

            $jSON['content'] = [".{$form}" => $form_content];
            break;

            /* STOCK MANAGER */
            /* CUSTOM BY ALISSON */
        case 'stock_manager':
            //EXCLUI ID DO EQUIPAMENTO DO ARRAY E ARMAZENA NA VARIÁVEL $PdtId
            $PdtId = $PostData['pdt_id'];
            unset($PostData['pdt_id']);

            //CONTA CORES
            $countColor = array();
            if (!empty($PostData['color_id'])) :
                for ($cc = 0; $cc < count($PostData['stock_inventory']); $cc++) :
                    if ($PostData['stock_inventory'][$cc] >= 1 && !in_array($PostData['color_id'][$cc], $countColor)) :
                        $countColor[$cc] = $PostData['color_id'][$cc];
                    endif;
                endfor;
            endif;

            //IMPEDE O CADASTRO DE DUAS CORES CASO O EQUIPAMENTO SEJA UMA VARIÇÃO
            $Read->FullRead("SELECT pdt_parent FROM " . DB_PDT . " WHERE (pdt_id = :id OR pdt_parent = :id) AND pdt_parent IS NOT NULL", "id={$PdtId}");
            if ($Read->getResult() && count($countColor) > 1) :
                $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Variações de equipamentos não podem conter mais de uma cor!</span>", E_USER_WARNING);
                break;
            endif;

            //REALIZA VERIFICAÇÕES PARA PODER VINCULAR O EQUIPAMENTO $PdtId COM CORES E TAMANHOS
            if (!empty($PostData['color_id']) && !empty($PostData['size_id']) && empty($PostData['print_id'])) :
                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores e tamanhos zere todos os valores configurados na aba <b>Estampas e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NOT NULL AND size_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores e tamanhos zere todos os valores configurados na aba <b>Cores</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores e tamanhos zere todos os valores configurados na aba <b>Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores e tamanhos zere todos os valores configurados na aba <b>Estampas</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NULL AND print_id IS NULL AND stock_inventory >= :inventory", "pdt={$PdtId}&inventory=1");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores e tamanhos zere todos os valores configurados na aba <b>Estoque</b>!</span>", E_USER_WARNING);
                    break;
                endif;
            endif;

            //REALIZA VERIFICAÇÕES PARA PODER VINCULAR O EQUIPAMENTO $PdtId COM ESTAMPAS E TAMANHOS
            if (!empty($PostData['print_id']) && !empty($PostData['size_id']) && empty($PostData['color_id'])) :
                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas e tamanhos zere todos os valores configurados na aba <b>Cores e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NOT NULL AND size_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas e tamanhos zere todos os valores configurados na aba <b>Cores</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas e tamanhos zere todos os valores configurados na aba <b>Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas e tamanhos zere todos os valores configurados na aba <b>Estampas</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NULL AND print_id IS NULL AND stock_inventory >= :inventory", "pdt={$PdtId}&inventory=1");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas e tamanhos zere todos os valores configurados na aba <b>Estoque</b>!</span>", E_USER_WARNING);
                    break;
                endif;
            endif;

            //REALIZA VERIFICAÇÕES PARA PODER VINCULAR O EQUIPAMENTO $PdtId COM CORES
            if (!empty($PostData['color_id']) && empty($PostData['size_id']) && empty($PostData['print_id'])) :
                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores zere todos os valores configurados na aba <b>Cores e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores zere todos os valores configurados na aba <b>Estampas e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores zere todos os valores configurados na aba <b>Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores zere todos os valores configurados na aba <b>Estampas</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NULL AND print_id IS NULL AND stock_inventory >= :inventory", "pdt={$PdtId}&inventory=1");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de cores zere todos os valores configurados na aba <b>Estoque</b>!</span>", E_USER_WARNING);
                    break;
                endif;
            endif;

            //REALIZA VERIFICAÇÕES PARA PODER VINCULAR O EQUIPAMENTO $PdtId COM TAMANHOS
            if (empty($PostData['color_id']) && !empty($PostData['size_id']) && empty($PostData['print_id'])) :
                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de tamanhos zere todos os valores configurados na aba <b>Cores e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de tamanhos zere todos os valores configurados na aba <b>Estampas e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NOT NULL AND size_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de tamanhos zere todos os valores configurados na aba <b>Cores</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de tamanhos zere todos os valores configurados na aba <b>Estampas</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NULL AND print_id IS NULL AND stock_inventory >= :inventory", "pdt={$PdtId}&inventory=1");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de tamanhos zere todos os valores configurados na aba <b>Estoque</b>!</span>", E_USER_WARNING);
                    break;
                endif;
            endif;

            //REALIZA VERIFICAÇÕES PARA PODER VINCULAR O EQUIPAMENTO $PdtId COM ESTAMPAS
            if (!empty($PostData['print_id']) && empty($PostData['size_id']) && empty($PostData['color_id'])) :
                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas zere todos os valores configurados na aba <b>Cores e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas zere todos os valores configurados na aba <b>Estampas e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NOT NULL AND size_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas zere todos os valores configurados na aba <b>Cores</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas zere todos os valores configurados na aba <b>Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NULL AND print_id IS NULL AND stock_inventory >= :inventory", "pdt={$PdtId}&inventory=1");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento contenha vinculação de estampas zere todos os valores configurados na aba <b>Estoque</b>!</span>", E_USER_WARNING);
                    break;
                endif;
            endif;

            //REALIZA VERIFICAÇÕES PARA PODER VINCULAR O EQUIPAMENTO $PdtId COM A QUANTIDADE EM ESTOQUE
            if (empty($PostData['color_id']) && empty($PostData['size_id']) && empty($PostData['print_id'])) :
                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento não contenha vinculação de cores, tamanhos ou estampas zere todos os valores configurados na aba <b>Cores e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NOT NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento não contenha vinculação de cores, tamanhos ou estampas zere todos os valores configurados na aba <b>Estampas e Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NOT NULL AND size_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento não contenha vinculação de cores, tamanhos ou estampas zere todos os valores configurados na aba <b>Cores</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND color_id IS NULL AND size_id IS NOT NULL AND print_id IS NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento não contenha vinculação de cores, tamanhos ou estampas zere todos os valores configurados na aba <b>Tamanhos</b>!</span>", E_USER_WARNING);
                    break;
                endif;

                $Read->FullRead("SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NOT NULL", "pdt={$PdtId}");
                if ($Read->getResult()) :
                    $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Para que este equipamento não contenha vinculação de cores, tamanhos ou estampas zere todos os valores configurados na aba <b>Estampas</b>!</span>", E_USER_WARNING);
                    break;
                endif;
            endif;

            //ATUALIZA INVENTÁRIO DO EQUIPAMENTO $PdtId NA TABELA ws_products
            $SockTotal = array_sum($PostData['stock_inventory']);
            $UpdateGeneralStock = ['pdt_inventory' => $SockTotal];
            $Update->ExeUpdate(DB_PDT, $UpdateGeneralStock, "WHERE pdt_id = :id", "id={$PdtId}");

            //CADASTRA, ATUALIZA E DELETA ESTOQUE DO EQUIPAMENTO $PdtId NA TABELA ws_products_stock
            for ($cc = 0; $cc < count($PostData['stock_inventory']); $cc++) :
                if (!empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                    //Tamanho e Cor
                    $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id = :size AND color_id = :color AND print_id IS NULL", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}&color={$PostData['color_id'][$cc]}");
                elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                    //Estampa e Tamanho
                    $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id = :size AND color_id IS NULL AND print_id = :print", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}&print={$PostData['print_id'][$cc]}");
                elseif (empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                    //Cor
                    $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id = :color AND print_id IS NULL", "pdt={$PdtId}&color={$PostData['color_id'][$cc]}");
                elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                    //Tamanho
                    $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id = :size AND color_id IS NULL AND print_id IS NULL", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}");
                elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                    //Estampa
                    $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id = :print", "pdt={$PdtId}&print={$PostData['print_id'][$cc]}");
                elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                    //Estoque
                    $Read->FullRead("SELECT stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                endif;

                if (!$Read->getResult() && $PostData['stock_inventory'][$cc] >= 1) :
                    if (!empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Tamanho e Cor
                        $dataCreate = ['pdt_id' => $PdtId, 'size_id' => $PostData['size_id'][$cc], 'color_id' => $PostData['color_id'][$cc], 'stock_inventory' => $PostData['stock_inventory'][$cc]];
                    elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                        //Estampa e Tamanho
                        $dataCreate = ['pdt_id' => $PdtId, 'size_id' => $PostData['size_id'][$cc], 'print_id' => $PostData['print_id'][$cc], 'stock_inventory' => $PostData['stock_inventory'][$cc]];
                    elseif (empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Cor
                        $dataCreate = ['pdt_id' => $PdtId, 'color_id' => $PostData['color_id'][$cc], 'stock_inventory' => $PostData['stock_inventory'][$cc]];
                    elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Tamanho
                        $dataCreate = ['pdt_id' => $PdtId, 'size_id' => $PostData['size_id'][$cc], 'stock_inventory' => $PostData['stock_inventory'][$cc]];
                    elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                        //Estampa
                        $dataCreate = ['pdt_id' => $PdtId, 'print_id' => $PostData['print_id'][$cc], 'stock_inventory' => $PostData['stock_inventory'][$cc]];
                    elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Estoque
                        $dataCreate = ['pdt_id' => $PdtId, 'stock_inventory' => $PostData['stock_inventory'][$cc]];
                    endif;

                    $Create->ExeCreate(DB_PDT_STOCK, $dataCreate);
                elseif ($Read->getResult() && $PostData['stock_inventory'][$cc] >= 1) :
                    $dataUpdate = ['stock_inventory' => $PostData['stock_inventory'][$cc]];

                    if (!empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Tamanho e Cor
                        $Update->ExeUpdate(DB_PDT_STOCK, $dataUpdate, "WHERE pdt_id = :pdt AND size_id = :size AND color_id = :color AND print_id IS NULL", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}&color={$PostData['color_id'][$cc]}");
                    elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                        //Estampa e Tamanho
                        $Update->ExeUpdate(DB_PDT_STOCK, $dataUpdate, "WHERE pdt_id = :pdt AND size_id = :size AND color_id IS NULL AND print_id = :print", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}&print={$PostData['print_id'][$cc]}");
                    elseif (empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Cor
                        $Update->ExeUpdate(DB_PDT_STOCK, $dataUpdate, "WHERE pdt_id = :pdt AND size_id IS NULL AND color_id = :color AND print_id IS NULL", "pdt={$PdtId}&color={$PostData['color_id'][$cc]}");
                    elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Tamanho
                        $Update->ExeUpdate(DB_PDT_STOCK, $dataUpdate, "WHERE pdt_id = :pdt AND size_id = :size AND color_id IS NULL AND print_id IS NULL", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}");
                    elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                        //Estampa
                        $Update->ExeUpdate(DB_PDT_STOCK, $dataUpdate, "WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id = :print", "pdt={$PdtId}&print={$PostData['print_id'][$cc]}");
                    elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Estoque
                        $Update->ExeUpdate(DB_PDT_STOCK, $dataUpdate, "WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                    endif;
                elseif ($Read->getResult() && $PostData['stock_inventory'][$cc] == 0) :
                    if (!empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Tamanho e Cor
                        $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :pdt AND size_id = :size AND color_id = :color AND print_id IS NULL", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}&color={$PostData['color_id'][$cc]}");
                    elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                        //Estampa e Tamanho
                        $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :pdt AND size_id = :size AND color_id IS NULL AND print_id = :print", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}&print={$PostData['print_id'][$cc]}");
                    elseif (empty($PostData['size_id']) && !empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Cor
                        $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :pdt AND size_id IS NULL AND color_id = :color AND print_id IS NULL", "pdt={$PdtId}&color={$PostData['color_id'][$cc]}");
                    elseif (!empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Tamanho
                        $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :pdt AND size_id = :size AND color_id IS NULL AND print_id IS NULL", "pdt={$PdtId}&size={$PostData['size_id'][$cc]}");
                    elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && !empty($PostData['print_id'])) :
                        //Estampa
                        $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id = :print", "pdt={$PdtId}&print={$PostData['print_id'][$cc]}");
                    elseif (empty($PostData['size_id']) && empty($PostData['color_id']) && empty($PostData['print_id'])) :
                        //Estoque
                        $Delete->ExeDelete(DB_PDT_STOCK, "WHERE pdt_id = :pdt AND size_id IS NULL AND color_id IS NULL AND print_id IS NULL", "pdt={$PdtId}");
                    endif;
                endif;
            endfor;

            $jSON['content'] = [".j_pdt_inventory" => $SockTotal];
            $jSON['trigger'] = AjaxErro("<div class='trigger trigger_success trigger_ajax'><b class='icon icon-checkmark'>Estoque atualizado com sucesso!</b></div>");
            break;

            /* REPLY */
            /* CUSTOM BY ALISSON */
        case 'reply':
            $PdtId = $PostData['pdt_id'];
            $jSON['trigger'] = null;

            $Read->FullRead("SELECT COUNT(DISTINCT (color_id)) AS total FROM " . DB_PDT_STOCK . " WHERE pdt_id = :id", "id={$PdtId}");
            if (!empty($Read->getResult()[0]['total']) && $Read->getResult()[0]['total'] > 1) :
                $jSON['trigger'] = AjaxErro("<span class='icon-warning'><b>OPPSSS:</b> Variações de equipamentos não podem conter mais de uma cor!</span>", E_USER_WARNING);
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
