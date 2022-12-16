### Validation

This plugin offers basic validation.

You can validate your file against:

* existence
* mime type
* extension
* file size

Example:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['image' => [
    // ... your other configs here ....
    'validation' => [
        'allowEmptyFile' => true,
        'allowedExtensions' => ['jpg', 'pdf'],
        'allowedMimeTypes' => ['application/pdf', 'image/jpg'],
        'fileSize' => [
            'min' => null,
            'max' => '5MB',
        ]
    ]
]]);
```

***validation.allowEmptyFile***

Default value: true

The file is required or optional. Possible values: true, false, 'created', 'updated'

***validation.allowedExtensions***

Default value: []

The list with allowed extension what can be uploaded.

***validation.allowedMimeTypes***

Default value: []

The list with allowed mime types what can be uploaded.

***validation.fileSize.min***

Default value: null

The file's size must be equal or higher than this number (in human readable string 5MB', '5M', '500B', '50kb' etc.)

***validation.fileSize.max***

Default value: null

The file's size must be equal or less than this number (in human readable string 5MB', '5M', '500B', '50kb' etc.)

#### Other validation methods

The behaviour extracts all data from the file and creates an UploadedFile entity, what is validated with
UploadedFileTable.

You can extend the UploadedFileTable and you can define custom rules, and you can override the validator method.

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['image' => [
    // ... your other configs here ....
    'table' => \App\Model\Table\MyUploadedFilesTable::class,
    'validation' => [
        'method' => 'image',
        'allowEmptyFile' => true,
        'allowedExtensions' => ['jpg', 'pdf'],
        'allowedMimeTypes' => ['application/pdf', 'image/jpg'],
        'fileSize' => [
            'min' => null,
            'max' => '5MB',
        ]
    ]
]]);
```

In the example above I override the default _\FileUploader\Model\Table\UploadedFilesTable_ with my table, where I defined
my custom validation rules.

***table***

Default value: \FileUploader\Model\Table\UploadedFilesTable::class

The default Table what saves the UploadedFile entity into the database.

***validation.method***

Default value: 'default'

The default validation method what is applied to the UploadedFile entity. If this fails, the upload will not happen.
