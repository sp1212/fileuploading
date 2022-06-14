<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <title>User Files</title>
</head>
<body>
    <form action="uploadpage.php" method="post" enctype="multipart/form-data">
        <h2>File Upload</h2>
        <label for="fileSelect">Filename:</label>
        <input type="file" name="document" id="fileSelect">
        <input type="submit" name="submit" value="Upload">
        <p><strong>Note:</strong> Only .pdf, .jpg, .jpeg, .gif, .png formats allowed to a max size of 5 MB.</p>
    </form>

<?php
include 'Database.php';
$db = new Database();

$folderPath = "uploads/";

if (isset($_POST["deleteFile"])) {
    $path;
    $data = $db->query("select path from uploads where id = ?", "d", $_POST["deleteFile"]);
    if ($data === false) {
        echo "Error finding file.";
    }
    else if (!empty($data)) {
        $path = $data[0]['path'];
    }

    $data = $db->query("delete from uploads where id = ?;", "d", $_POST["deleteFile"]);
    if($data === false) {
        echo "Error deleting file.";
    }
    else {
        if (isset($path)) {
            if (!file_exists($path)) {
                echo "Physical file not found but deleted database entry.";
            }
            else if (!unlink($path)) {
                echo "Error - file not deleted.";
            }
            else {
                echo "File has been deleted.";
            }
        }
    }
}
// Check if the form was submitted
else if(isset($_FILES["document"])){
    // Check if file was uploaded without errors (adapted from https://www.tutorialrepublic.com/php-tutorial/php-file-upload.php)
    if(isset($_FILES["document"]) && $_FILES["document"]["error"] == 0){
        $allowed = array("pdf" => "application/pdf", "jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["document"]["name"];
        $filetype = $_FILES["document"]["type"];
        $filesize = $_FILES["document"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) die("Error: Please select a valid file format.");
    
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) die("Error: File size is larger than the allowed limit.");
    
        // Verify MYME type of the file
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it
            if(file_exists($folderPath . $filename)){
                echo $filename . " is already exists.";
            } else{
                move_uploaded_file($_FILES["document"]["tmp_name"], $folderPath . $filename);
                $insert = $db->query("insert into uploads (name, path, type, size, owner) values (?, ?, ?, ?, ?);", "sssss",
                        $_FILES["document"]["name"], 
                        $folderPath . $_FILES["document"]["name"],
                        $_FILES["document"]["type"],
                        $_FILES["document"]["size"],
                        "-",
                        );
                if ($insert === false) {
                    echo "Error tracking file in the database.";
                }
                echo "Your file was uploaded successfully.";
            } 
        } else{
            echo "Error: There was a problem uploading your file. Please try again."; 
        }
    } else{
        echo "Error: " . $_FILES["document"]["error"];
    }
}

$data = $db->query("select * from uploads");
?>

<h4>Database View</h4>
<table class="table table-striped">
  <thead>
    <tr>
      <th scope="col">id</th>
      <th scope="col">name</th>
      <th scope="col">path</th>
      <th scope="col">type</th>
      <th scope="col">size</th>
      <th scope="col">owner</th>
      <th scope="col">upload_date</th>
      <th scope="col"></th>
      <th scope="col"></th>
      <th scope="col"></th>
    </tr>
  </thead>
  <tbody>

<?php
foreach ($data as $entry) {
?>
    <tr>
      <th scope="row"><?=$entry['id']?></th>
      <td><?=$entry['name']?></td>
      <td><?=$entry['path']?></td>
      <td><?=$entry['type']?></td>
      <td><?=$entry['size']?></td>
      <td><?=$entry['owner']?></td>
      <td><?=$entry['upload_date']?></td>
      <td>
        <a class="btn btn-outline-primary" href="<?=$entry['path']?>">View</a>
      </td>
      <td>
        <a class="btn btn-outline-primary" href="<?=$entry['path']?>" download>Download</a>
      </td>
      <td>
        <form method="post">
            <button type="submit" class="btn btn-outline-danger" name="deleteFile" value="<?=$entry['id']?>">Delete</button>
        </form>
      </td>
    </tr>
<?php
}
?>
  </tbody>
</table>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
</body>
</html>