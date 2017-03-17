<?php

namespace Lexik\Bundle\MonologBrowserBundle\Twig;



/**
 * Definition of TwigExtension
 *
 * @author Diego de Biagi <diegobiagiviana@gmail.com>
 */
class TwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface {

    private $dateFormat;

    function __construct($dateFormat) {
        $this->dateFormat = $dateFormat;
    }

    public function getGlobals() {
        return [
            'monolog_browser_bundle' => [
                'date_format' => $this->dateFormat
            ]
        ];
    }
}