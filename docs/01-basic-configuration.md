## Basic configuration

The minimal configuration:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['photo']);
```

The minimal configuration equal with the basic configuration:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        'filePathProcessor' => \FileUploader\FilePathProcessor\DefaultProcessor::class,
        'metadataCallback' => function ($table, $entity, $data, $field, $settings) {
            // return a custom array with additional data
        },
        'returnValue' => 'id',
        'calculateHash' => false,
    ]
]);
```

This minimal configuration uses the file system, if you want to use a cloud provider you can extend this config.

***filePathProcessor***

Default value: null

The __filePathProcessor__ it's an implementation of __\FileUploader\FilePathProcessor\FilePathProcessorInterface__.
This class decides how the directory structure should look like and also decides the filename.

The local filesystem always saves the file in the following format.

```
WWW_ROOT/files/:table_alias/:year/:month/:timestamp.:extension
```

If you want to change only the _files_ directory you can do it in _app.php_ by setting the _App.fileBaseDirectory_ value.
But ofc you can implement your own _filePathProcessor_ class.

***metadataCallback***

Default value: null

A custom callback function what must return an array. You can save additional info here about the file

***returnValue***

Default value: 'id'

This field is what we return and what we save into the 'external table'.

***calculateHash***

Default value: false

You can calculate the SHA1 hash of the uploaded file if you want to filter out duplicate files.
