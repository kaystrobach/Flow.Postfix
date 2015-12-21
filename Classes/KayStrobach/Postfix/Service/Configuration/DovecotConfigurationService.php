<?php
namespace KayStrobach\Postfix\Service\Configuration;

class DovecotConfigurationService
{
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