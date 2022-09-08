### Deleting files

By default, the plugin does not delete any file.
If you want to remove the file when the entity is deleted, then you can provide a __deleteCallback__ function, what 
MUST return a __\FileUploader\Model\Entity\UploadedFile__ entity.

Example:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        // ... all configuration from base configuration ...//
        'returnValue' => 'id',
        'deleteCallback' => function ($entity, $UploadedFilesTable) {
            return $UploadedFilesTable->get($entity->get('photo'));
        }
    ]
]);
```

The __deleteCallback__ gets the deleted entity and the __\FileUploader\Model\Table\UploadedFilesTable__ as parameter.
In the example above, we are lucky because under the 'Model.photo' we saved (_returnValue_) the id.

But if you change the __returnValue__ to something else, is your job to write query to return the correct entity. 

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        // ... all configuration from base configuration ...//
        'returnValue' => 'full_path',
        'deleteCallback' => function ($entity, $UploadedFilesTable) {
            // ... search by full path
        }
    ]
]);
```