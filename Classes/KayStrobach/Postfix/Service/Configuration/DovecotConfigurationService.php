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

    /**
     * @param string $file
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setValue($file, $key, $value) {
        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @return $this
     */
    public function commentLine($file, $line) {
        return $this;
    }

    /**
     * @param $file
     * @return $this
     */
    public function clearFile($file) {
        return $this->setFileContent($file, '');
    }

    public function setFileContent($file, $content) {
        file_put_contents($file, $content);
        return $this;
    }

    public function setFileContentFromTemplate($file) {
        $basePath = 'resource://KayStrobach.Postfix/Private/ConfigurationTemplates';
        $filePath = $basePath . $file;
        if(is_file($filePath)) {
            return $this->setFileContent($file, file_get_contents($filePath));
        }
        return $this;
    }

    public function setSectionContentFromTemplate($file, $section) {
        return $this;
    }
}