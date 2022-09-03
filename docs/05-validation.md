### Validation

This plugin offers basic validation.

You can validate your file against:

* mime type
* extension
* file size

Example:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['image' => [
    // ... your other configs here ....
    'validation' => [
        'allowedExtensions' => ['jpg', 'pdf'],
        'allowedMimeTypes' => ['application/pdf', 'image/jpg'],
        'fileSize' => [
            'min' => 1,
            'max' => 100,
        ]
    ]
]]);
```

***validation.allowedExtensions***

Default value: []

The list with allowed extension what can be uploaded.

***validation.allowedMimeTypes***

Default value: []

The list with allowed mime types what can be uploaded.

***validation.fileSize.min***

Default value: null

The file's size must be equal or higher than this number (in bytes)

***validation.fileSize.max***

Default value: null

The file's size must be equal or less than this number (in bytes)

#### Other validation methods

The behaviour extracts all data from the file and creates an UploadedFile entity, what is validated with
UploadedFileTable.

You can extend the UploadedFileTable and you can define custom rules, and you can override the validator method.

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['image' => [
    // ... your other configs here ....
    'validation' => [
        'table' => \App\Model\Table\MyUploadedFilesTable::class,
        'rule' => 'image',
        'allowedExtensions' => ['jpg', 'pdf'],
        'allowedMimeTypes' => ['application/pdf', 'image/jpg'],
        'fileSize' => [
            'min' => 1,
            'max' => 100,
        ]
    ]
]]);
```

In the example above I overrided the default _\FileUploader\Model\Table\UploadedFilesTable_ with my table, where I defined
my custom validation rules.

***validation.table***

Default value: \FileUploader\Model\Table\UploadedFilesTable::class

The default Table what saves the UploadedFile entity into the database.

***validation.rule***

Default value: 'default'

The default validation rule what is applied to the UploadedFile entity. If this fails, the upload will not happen.
