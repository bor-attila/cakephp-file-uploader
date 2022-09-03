### Using ClamAV

If you want to use ClamAV for virus checking install [appwrite/php-clamav](https://github.com/appwrite/php-clamav).

```
composer require appwrite/php-clamav
```

You must be sure that ClamAV is installed on server and runs as __root__ or as the same user as the webserver.

In the background the PHP uploads the file from your PC to the server.
For exmpl: __C:\mydoc.pdf__ to __/tmp/qwd45rgrQWQ__
This __/tmp/qwd45rgrQWQ__ temporary file must be accessible for clamav.
If this is possible, the you can enable it.

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['image' => [
    // ... your other configs here ....
    'clamav' => [
        'enabled' => true,
        'socket' => '/var/run/clamav/clamd.ctl',
    ]
]]);
```

OR

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['image' => [
    // ... your other configs here ....
    'clamav' => [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => '3310',
    ]
]]);
```

Note: if the ClamAV check is enabled the file MUST PASS. If the file is not accessible, ClamAV deamon is down or the
file is dangerous, the upload fails.

***clamav.enabled***

Optional, default value: false

Turns on/off the virus checking with ClamAV.

***clamav.socket***

Optional, default value: null

The clamav deamon's socket.

***clamav.host***

Optional, default value: localhost

If the __socket__ is null, the __host__ and __port__ is used.

***clamav.port***

Optional, default value: 3310

If the __socket__ is null, the __host__ and __port__ is used.
