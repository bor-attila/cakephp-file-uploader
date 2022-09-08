### Using with Microsoft Azure

#### Installation

Install dependency

```
composer require league/flysystem-azure-blob-storage
```

#### Configuration

Using with Microsoft Azure is very similar with local file system but needs some extra configuration.

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        // ... all configuration from base configuration ...//

        // required, a fully configured Blob Service instance
        'client' => BlobRestProxy::createBlobService('[INSERT-DSN-STRING-HERE]'),

        // the containers's name
        'container' => 'my-bucket',
    ]
]);
```

***client***

Required, default value: null.

The preconfigured BlobProxy instance. In case of null the local file system will be used

***container***

Required, default value: 'files'.

the container's name where we upload the files
