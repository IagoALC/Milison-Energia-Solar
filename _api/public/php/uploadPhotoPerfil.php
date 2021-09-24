<?php

$target_dir = "../../../uploads/perfil/";
$target_file = $target_dir . basename($_FILES["photo"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
$check = getimagesize($_FILES["photo"]["tmp_name"]);
if ($check !== false) {
    //echo "File is an image - " . $check["mime"] . ".";
    $uploadOk = 1;
    unlink('../../../uploads/perfil/'.$_FILES['file']['tmp_name']);
    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        //echo "The file " . basename($_FILES["photo"]["name"]) . " has been uploaded.";
    } else {
        //echo "Sorry, there was an error uploading your file.";
    }
} else {
    //echo "File is not an image.";
    $uploadOk = 0;
}
?>