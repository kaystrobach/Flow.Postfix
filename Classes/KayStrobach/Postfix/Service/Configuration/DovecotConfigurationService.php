<?php
namespace KayStrobach\Postfix\Service\Configuration;

use TYPO3\Flow\Utility\Files;

class DovecotConfigurationService
{
    /**
     * @var array
     */
    protected $params = array();

    /**
     * @param string $param
     * @param string $value
     * @return $this
     */
    public function setParam($param, $value) {
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * @param string $directory
     */
    public function setDirectoryContentFromTemplates($directory) {
        $basePath = FLOW_PATH_PACKAGES . 'Application/KayStrobach.Postfix/Resources/Private/ConfigurationTemplates' . $directory;
        $dir = new \RecursiveDirectoryIterator(
            $basePath,
            \RecursiveDirectoryIterator::SKIP_DOTS|\RecursiveDirectoryIterator::FOLLOW_SYMLINKS|\RecursiveIteratorIterator::LEAVES_ONLY
        );

        echo $basePath . PHP_EOL;

        foreach(new \RecursiveIteratorIterator($dir) as $file) {
            $buffer = \file_get_contents($file);
            foreach($this->params as $key => $value) {
                $buffer = str_replace('${' . $key . '}', $value, $buffer);
            }
            \file_put_contents($directory . substr($file, strlen($basePath)), $buffer);
        }
    }
}