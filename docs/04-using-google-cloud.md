### Using with Google cloud

#### Installation

Install dependency

```
composer require league/flysystem-google-cloud-storage
```

#### Configuration

Using with Google cloud is very similar with local file system but needs some extra configuration.

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        // ... all configuration from base configuration ...//

        // required, a fully configured StorageClient instance
        'client' => new \Google\Cloud\Storage\StorageClient($clientOptions),

        // the containers's name
        'container' => 'my-bucket',
    ]
]);
```

***client***

Required, default value: null.

The preconfigured StorageClient instance. In case of null the local file system will be used

***container***

Required, default value: 'files'.

the container's name where we upload the files
