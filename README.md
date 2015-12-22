# php-backblaze-b2-uploader
PHP Class that allows you to upload files to Backblaze B2

## Simple usage
   require 'b2.php';
   
   // Create new instance and pass it your credentials
   $b2 = new B2Uploader([
        'accountId'         => 'XXXXXX',
        'applicationKey'    => 'XXXXXXXXXXXX',
        'bucketId'          => 'XXXXXXXXXXXX'
   ]);
    
    // Upload a file!
    $b2->uploadFile('myBackup.zip');
