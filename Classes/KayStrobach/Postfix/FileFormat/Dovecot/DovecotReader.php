<?php

namespace KayStrobach\Postfix\FileFormat\Dovecot;


class DovecotReader
{
    /**
     * @var array of Sections
     */
    protected $sections;

    public function __construct($filename) {
        $fileContent = file_get_contents($filename);
    }
}