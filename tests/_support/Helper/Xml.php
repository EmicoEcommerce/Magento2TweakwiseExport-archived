<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Helper;

use Codeception\Module;
use DOMDocument;

class Xml extends Module
{
    /**
     * @param string $xml
     * @return string
     */
    public function normalizeXml($xml)
    {
        $doc = new DOMDocument(1.0);
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);
        return $doc->saveHTML();
    }
}
