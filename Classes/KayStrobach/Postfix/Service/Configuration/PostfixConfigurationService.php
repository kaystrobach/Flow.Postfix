<?php

namespace KayStrobach\Postfix\Service\Configuration;


use Neos\Utility\Files;

class PostfixConfigurationService
{
    /**
     * @var array
     */
    protected $params = array();

    public function setParam($param, $value) {
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * @param string $file
     * @param string $content
     * @return $this
     */
    public function setFileContent($file, $content) {
        Files::createDirectoryRecursively(dirname($file));
        file_put_contents($file, $content);
        return $this;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function setFileContentFromTemplate($file) {
        $basePath = 'resource://KayStrobach.Postfix/Private/ConfigurationTemplates';
        $filePath = $basePath . $file;
        $buffer = file_get_contents($filePath);
        foreach($this->params as $key => $value) {
            $buffer = str_replace('${' . $key . '}', $value, $buffer);
        }
        return $this->setFileContent($file, $buffer);
    }
}