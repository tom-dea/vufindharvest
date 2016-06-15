<?php

/**
 * OAI-PMH record xml formatter unit test.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
namespace VuFindTest\Harvest;

use VuFindHarvest\OaiRecordXmlFormatter;

/**
 * OAI-PMH record xml formatter unit test.
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
class OaiRecordXmlFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Return protected or private property.
     *
     * Uses PHP's reflection API in order to modify property accessibility.
     *
     * @param object|string $object   Object or class name
     * @param string        $property Property name
     *
     * @throws \ReflectionException Property does not exist
     *
     * @return mixed
     */
    protected function getProperty($object, $property)
    {
        $reflectionProperty = new \ReflectionProperty($object, $property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }

    /**
     * Test configuration.
     *
     * @return void
     */
    public function testConfig()
    {
        $config = [
            'injectId' => 'idtag',
            'injectSetSpec' => 'setspectag',
            'injectDate' => 'datetag',
            'injectHeaderElements' => 'headertag',
        ];
        $oai = new OaiRecordXmlFormatter($config);

        // Special case where value is transformed:
        $this->assertEquals(
            [$config['injectHeaderElements']],
            $this->getProperty($oai, 'injectHeaderElements')
        );

        // Unset special cases in preparation for generic loop below:
        unset($config['injectHeaderElements']);

        // Generic case for remaining configs:
        foreach ($config as $key => $value) {
            $this->assertEquals($value, $this->getProperty($oai, $key));
        }
    }

    /**
     * Test ID injection.
     *
     * @return void
     */
    public function testIdInjection()
    {
        $formatter = new OaiRecordXmlFormatter(['injectId' => 'id']);
        $result = $formatter->format('foo', $this->getRecordFromFixture());
        $xml = simplexml_load_string($result);
        $this->assertEquals('foo', $xml->id);
    }

    /**
     * Test set spec injection.
     *
     * @return void
     */
    public function testSetSpecInjection()
    {
        $formatter = new OaiRecordXmlFormatter(['injectSetSpec' => 'setSpec']);
        $result = $formatter->format('foo', $this->getRecordFromFixture());
        $xml = simplexml_load_string($result);
        $this->assertEquals(2, count($xml->setSpec));
        $this->assertEquals('TESTING_DIGI_TEST', (string)$xml->setSpec[0]);
        $this->assertEquals('TESTING_DIGI', (string)$xml->setSpec[1]);
    }

    /**
     * Test set name injection.
     *
     * @return void
     */
    public function testSetNameInjection()
    {
        $formatter = new OaiRecordXmlFormatter(['injectSetName' => 'setName']);

        // Default behavior -- use set spec if no set name provided:
        $result = $formatter->format('foo', $this->getRecordFromFixture());
        $xml = simplexml_load_string($result);
        $this->assertEquals(2, count($xml->setName));
        $this->assertEquals('TESTING_DIGI_TEST', (string)$xml->setName[0]);
        $this->assertEquals('TESTING_DIGI', (string)$xml->setName[1]);

        // Check correct behavior when set names provided:
        $formatter->setSetNames(
            ['TESTING_DIGI_TEST' => 'foo', 'TESTING_DIGI' => 'bar']
        );
        $result2 = $formatter->format('foo', $this->getRecordFromFixture());
        $xml2 = simplexml_load_string($result2);
        $this->assertEquals(2, count($xml2->setName));
        $this->assertEquals('foo', (string)$xml2->setName[0]);
        $this->assertEquals('bar', (string)$xml2->setName[1]);
    }

    /**
     * Get a record from the test fixture.
     *
     * @return object
     */
    protected function getRecordFromFixture()
    {
        $xml = simplexml_load_file(__DIR__ . '/../../../fixtures/marc.xml');
        return $xml->ListRecords->record[0];
    }
}