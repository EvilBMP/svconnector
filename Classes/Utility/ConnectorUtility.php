<?php
namespace Cobweb\Svconnector\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Utility class for the Connector family of services
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnector
 */
class ConnectorUtility
{
    /**
     * This method takes a XML structure and transforms it into a PHP array
     * This array is rather complicated as the method tries not to loose information from the XML structure
     *
     * Considering the following XML:
     *
     * <test>
     *        <foo>
     *            bar
     *            <child rank="#1?>Junior</child>
     *            <child rank="#2?>Baby</child>
     *        </foo>
     * </test>
     *
     * the resulting array will look like:
     *
     * array(
     *        'foo' => array(
     *            0 => array(
     *                'value' => 'bar',
     *                'children' => array(
     *                    'child' => (
     *                        0 => array(
     *                            'value' => 'Junior',
     *                            'children' => array(),
     *                            'attributes' => array(
     *                                'rank' => '#1'
     *                            )
     *                        ),
     *                        1 => array(
     *                            'value' => 'Baby',
     *                            'children' => array(),
     *                            'attributes' => array(
     *                                'rank' => '#2'
     *                            )
     *                        )
     *                    )
     *                ),
     *                'attributes' => array()
     *            )
     *        )
     * );
     *
     * NOTE: this method was written because t3lib_div::xml2array() is much too keyed
     * to TYPO3's specifics and produces weird or even outright wrong array structures.
     * On the other hand the reverse conversion is fine with t3lib_div::array2xml_cs().
     *
     * @param string $string XML to parse
     * @throws \Exception
     * @return array PHP array
     */
    static public function convertXmlToArray($string)
    {
        $phpArray = array();
        // If input string is empty, exit with exception
        if (empty($string)) {
            throw new \Exception('XML string is empty!', 1294325109);
        }

        // Try loading the string into the Simple XML library
        $xmlObject = simplexml_load_string($string);
        // If the value returned is false, the XML could not be parsed
        if ($xmlObject === false) {
            throw new \Exception('XML string is invalid!', 1294325195);
        }

        // Transform XML into a PHP array
        foreach ($xmlObject as $key => $value) {
            if (!isset($phpArray[$key])) {
                $phpArray[$key] = array();
            }
            $phpArray[$key][] = self::handleXmlNode($value);
        }

        return $phpArray;
    }

    /**
     * This method converts a given XML node into a PHP array, preserving all
     * the attribute and children information. Calls itself recursively on child nodes.
     *
     * @param \SimpleXMLElement $node XML node to transform
     * @return array Transformed XML node and children
     */
    static public function handleXmlNode(\SimpleXMLElement $node)
    {
        // Initializations
        $nodeArray = array();
        $nodeArray['value'] = trim((string)$node);
        $nodeArray['children'] = array();
        $nodeArray['attributes'] = array();
        // Loop on all children, if any
        $children = $node->children();
        if ($children->count() > 0) {
            // If there are child nodes, recursively transform them into arrays too
            foreach ($children as $key => $subNode) {
                if (!isset($nodeArray[$key])) {
                    $nodeArray[$key] = array();
                }
                $nodeArray['children'][$key][] = self::handleXmlNode($subNode);
            }
        }
        // Handle attributes, if any
        $attributes = $node->attributes();
        if ($attributes->count() > 0) {
            foreach ($attributes as $key => $value) {
                $nodeArray['attributes'][$key] = (string)$value;
            }
        }
        return $nodeArray;
    }
}
