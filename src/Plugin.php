<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 15.11.2017
 */

namespace skeeks\cms\composer\update;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * Class Plugin
 * @package skeeks\cms\marketplace\composer
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    const UPDATE_LOCK_TMP_FILE = 'update.lock.tmp';

    /**
     * @var string absolute path to the package base directory
     */
    protected $baseDir;
    /**
     * @var string absolute path to vendor directory
     */
    protected $vendorDir;
    /**
     * @var Filesystem utility
     */
    protected $filesystem;

    /**
     * @var Composer instance
     */
    protected $composer;
    /**
     * @var IOInterface
     */
    public $io;

    /**
     * Initializes the plugin object with the passed $composer and $io.
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        
        if (!file_exists($this->getUpdateLockFile())) {
            $this->io->writeError('<info>Create update lock tmp file: ' . $this->getUpdateLockFile() . '</info>');

            $fp = fopen($this->getUpdateLockFile(), "w");
            fwrite($fp, time());
            fclose($fp);
        }


    }

    /**
     * Returns list of events the plugin is subscribed to.
     * @return array list of events
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => [
                ['onPostAutoloadDump', 0],
            ],
        ];
    }

    /**
     * @return string
     */
    public function getUpdateLockFile()
    {
        $dir = $this->getBaseDir();
        return $dir . "/" . self::UPDATE_LOCK_TMP_FILE;
    }

    /**
     * This is the main function.
     * @param Event $event
     */
    public function onPostAutoloadDump(Event $event)
    {
        $this->io->writeError('<info>Remove update lock tmp file: ' . $this->getUpdateLockFile() . '</info>');
        if (file_exists($this->getUpdateLockFile())) {
            if (!unlink($this->getUpdateLockFile())) {
                $this->io->writeError("<error>Not removed lock file: " . $this->getUpdateLockFile() . "</error>");
            }
        } else {
            $this->io->writeError("<warning>Not found lock file: " . $this->getUpdateLockFile() . "</warning>");
        }
    }

    protected function initAutoload()
    {
        $dir = dirname(dirname(dirname(__DIR__)));
        require_once "$dir/autoload.php";
    }


    /**
     * Get absolute path to package base dir.
     * @return string
     */
    public function getBaseDir()
    {
        if (null === $this->baseDir) {
            $this->baseDir = dirname($this->getVendorDir());
        }
        return $this->baseDir;
    }

    /**
     * Get absolute path to composer vendor dir.
     * @return string
     */
    public function getVendorDir()
    {
        if (null === $this->vendorDir) {
            $dir = $this->composer->getConfig()->get('vendor-dir');
            $this->vendorDir = $this->getFilesystem()->normalizePath($dir);
        }
        return $this->vendorDir;
    }

    /**
     * Getter for filesystem utility.
     * @return Filesystem
     */
    public function getFilesystem()
    {
        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }
        return $this->filesystem;
    }
}