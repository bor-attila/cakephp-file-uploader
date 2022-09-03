<?php
declare(strict_types=1);

namespace FileUploader;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Database\TypeFactory;
use FileUploader\Database\Type\FileType;

/**
 * Plugin for CakephpImageUploader
 */
class Plugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        TypeFactory::map('upload.image', FileType::class);
    }
}
