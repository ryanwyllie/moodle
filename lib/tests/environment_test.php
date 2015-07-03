<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle environment test.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Do standard environment.xml tests.
 */
class core_environment_testcase extends advanced_testcase {

    /**
     * Test the environment.
     */
    public function test_environment() {
        global $CFG;

        require_once($CFG->libdir.'/environmentlib.php');
        list($envstatus, $environment_results) = check_moodle_environment(normalize_version($CFG->release), ENV_SELECT_RELEASE);

        $this->assertNotEmpty($envstatus);
        foreach ($environment_results as $environment_result) {
            if ($environment_result->part === 'php_setting'
                and $environment_result->info === 'opcache.enable'
                and $environment_result->getLevel() === 'optional'
                and $environment_result->getStatus() === false
            ) {
                $this->markTestSkipped('OPCache extension is not necessary for unit testing.');
                continue;
            }
            $this->assertTrue($environment_result->getStatus(), "Problem detected in environment ($environment_result->part:$environment_result->info), fix all warnings and errors!");
        }
    }

    /**
     * Test the get_list_of_environment_versions() function.
     */
    public function test_get_list_of_environment_versions() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');
        // Build a sample xmlised environment.xml.
        $xml = <<<END
<COMPATIBILITY_MATRIX>
    <MOODLE version="1.9">
        <PHP_EXTENSIONS>
            <PHP_EXTENSION name="xsl" level="required" />
        </PHP_EXTENSIONS>
    </MOODLE>
    <MOODLE version="2.5">
        <PHP_EXTENSIONS>
            <PHP_EXTENSION name="xsl" level="required" />
        </PHP_EXTENSIONS>
    </MOODLE>
    <MOODLE version="2.6">
        <PHP_EXTENSIONS>
            <PHP_EXTENSION name="xsl" level="required" />
        </PHP_EXTENSIONS>
    </MOODLE>
    <MOODLE version="2.7">
        <PHP_EXTENSIONS>
            <PHP_EXTENSION name="xsl" level="required" />
        </PHP_EXTENSIONS>
    </MOODLE>
    <PLUGIN name="block_test">
        <PHP_EXTENSIONS>
            <PHP_EXTENSION name="xsl" level="required" />
        </PHP_EXTENSIONS>
    </PLUGIN>
</COMPATIBILITY_MATRIX>
END;
        $environemt = xmlize($xml);
        $versions = get_list_of_environment_versions($environemt);
        $this->assertCount(5, $versions);
        $this->assertContains('1.9', $versions);
        $this->assertContains('2.5', $versions);
        $this->assertContains('2.6', $versions);
        $this->assertContains('2.7', $versions);
        $this->assertContains('all', $versions);
    }

    /**
     * Test the environment_verify_plugin() function.
     */
    public function test_verify_plugin() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');
        // Build sample xmlised environment file fragments.
        $plugin1xml = <<<END
<PLUGIN name="block_testcase">
    <PHP_EXTENSIONS>
        <PHP_EXTENSION name="xsl" level="required" />
    </PHP_EXTENSIONS>
</PLUGIN>
END;
        $plugin1 = xmlize($plugin1xml);
        $plugin2xml = <<<END
<PLUGIN>
    <PHP_EXTENSIONS>
        <PHP_EXTENSION name="xsl" level="required" />
    </PHP_EXTENSIONS>
</PLUGIN>
END;
        $plugin2 = xmlize($plugin2xml);
        $this->assertTrue(environment_verify_plugin('block_testcase', $plugin1['PLUGIN']));
        $this->assertFalse(environment_verify_plugin('block_testcase', $plugin2['PLUGIN']));
        $this->assertFalse(environment_verify_plugin('mod_someother', $plugin1['PLUGIN']));
        $this->assertFalse(environment_verify_plugin('mod_someother', $plugin2['PLUGIN']));
    }

    /**
     * Test the environment_check_php_min_version() function sets NO_PHP_VERSION_FOUND
     * error code on $result if $data doesn't contain a version
     */
    public function test_check_php_min_version_sets_error_code_on_missing_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $data = array();
        $currentversion = '5.5';
        $result = new environment_results('php');
        $minversion = environment_check_php_min_version($data, $currentversion, $result);

        $this->assertNull($minversion,
            'environment_check_php_min_version returns null if no version is found');
        $this->assertFalse($result->getStatus(),
            'environment_check_php_min_version sets result status to false if no version is found');
        $this->assertEquals(NO_PHP_VERSION_FOUND, $result->getErrorCode(),
            'environment_check_php_min_version sets result error code to NO_PHP_VERSION_FOUND if no version is found');
    }

    /**
     * Test the environment_check_php_min_version() function sets result status to false
     * if the $currentversion is less than the min version
     */
    public function test_check_php_min_version_set_status_false_on_missing_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $expectedversion = '5.6';
        $data = array();
        $currentversion = '5.5';
        $result = new environment_results('php');

        // Set the min version in the correct data location.
        $data['#']['PHP']['0']['@']['version'] = $expectedversion;
        $actualversion = environment_check_php_min_version($data, $currentversion, $result);

        $this->assertEquals($expectedversion, $actualversion,
            'environment_check_php_min_version returns the min version defined in the data');
        $this->assertFalse($result->getStatus(),
            'environment_check_php_min_version sets result status to false if given version is less than minimum required');
    }

    /**
     * Test the environment_check_php_min_version() function sets result status to true
     * if the $currentversion is equal to the min version
     */
    public function test_check_php_min_version_set_status_true_on_matching_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $expectedversion = '5.5';
        $data = array();
        $currentversion = '5.5';
        $result = new environment_results('php');

        // Set the min version in the correct data location.
        $data['#']['PHP']['0']['@']['version'] = $expectedversion;
        $actualversion = environment_check_php_min_version($data, $currentversion, $result);

        $this->assertEquals($expectedversion, $actualversion,
            'environment_check_php_min_version returns the min version defined in the data');
        $this->assertTrue($result->getStatus(),
            'environment_check_php_min_version sets result status to true if given version is equal to the minimum required');
    }

    /**
     * Test the environment_check_php_min_version() function sets result status to true
     * if the $currentversion is greater than to the min version
     */
    public function test_check_php_min_version_set_status_true_on_newer_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $expectedversion = '5.5';
        $data = array();
        $currentversion = '5.6';
        $result = new environment_results('php');

        // Set the min version in the correct data location.
        $data['#']['PHP']['0']['@']['version'] = $expectedversion;
        $actualversion = environment_check_php_min_version($data, $currentversion, $result);

        $this->assertEquals($expectedversion, $actualversion,
            'environment_check_php_min_version returns the min version defined in the data');
        $this->assertTrue($result->getStatus(),
            'environment_check_php_min_version sets result status to true if given version is greater than the minimum required');
    }

    /**
     * Test the environment_check_php_unsupported_version() function returns null if no
     * unsupported version is set in the data
     */
    public function test_check_php_unsupported_version_returns_null_on_missing_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $data = array();
        $currentversion = '5.5';
        $result = new environment_results('php');
        $unsupportedversion = environment_check_php_unsupported_version($data, $currentversion, $result);

        $result->setStatus(true);

        $this->assertNull($unsupportedversion,
            'environment_check_php_unsupported_version returns null if no unsupported version is found');
        $this->assertTrue($result->getStatus(),
            "environment_check_php_unsupported_version doesn't change result status if no unsupported version is found");
    }

    /**
     * Test the environment_check_php_unsupported_version() function returns the unsupported version if
     * unsupported version is set in the data and doesn't change the result status if the current
     * version is less than the unsupported version
     */
    public function test_check_php_unsupported_version_less_than_unsupported_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $expectedversion = '5.6';
        $data = array();
        $currentversion = '5.5';
        $result = new environment_results('php');

        // Set up test data.
        $data['#']['PHP']['0']['@']['unsupported-version'] = $expectedversion;
        $result->setStatus(true);

        $actualversion = environment_check_php_unsupported_version($data, $currentversion, $result);

        $this->assertEquals($expectedversion, $actualversion,
            'environment_check_php_unsupported_version returns the unsupported version if defined in the data');
        $this->assertTrue($result->getStatus(),
            "environment_check_php_unsupported_version doesn't change result status if the current version".
            " is less than the unsupported version");
    }

    /**
     * Test the environment_check_php_unsupported_version() function returns the unsupported version if
     * unsupported version is set in the data and changes the result status to false if the current
     * version is equal to the unsupported version
     */
    public function test_check_php_unsupported_version_return_equal_to_unsupported_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $expectedversion = '5.5';
        $data = array();
        $currentversion = '5.5';
        $result = new environment_results('php');

        // Set up test data.
        $data['#']['PHP']['0']['@']['unsupported-version'] = $expectedversion;
        $result->setStatus(true);

        $actualversion = environment_check_php_unsupported_version($data, $currentversion, $result);

        $this->assertEquals($expectedversion, $actualversion,
            'environment_check_php_unsupported_version returns the unsupported version if defined in the data');
        $this->assertFalse($result->getStatus(),
            "environment_check_php_unsupported_version doesn't change result status if the current version".
            " is equal to the unsupported version");
    }


    /**
     * Test the environment_check_php_unsupported_version() function returns the unsupported version if
     * unsupported version is set in the data and sets the result status to false if the current
     * version is greater than the unsupported allowed version
     */
    public function test_check_php_unsupported_version_greater_than_unsupported_version() {
        global $CFG;
        require_once($CFG->libdir.'/environmentlib.php');

        $expectedversion = '5.6';
        $data = array();
        $currentversion = '5.7';
        $result = new environment_results('php');

        // Set the unsupported version in the data.
        $data['#']['PHP']['0']['@']['unsupported-version'] = $expectedversion;
        // Make sure the result wasn't already false.
        $result->setStatus(true);

        $actualversion = environment_check_php_unsupported_version($data, $currentversion, $result);

        $this->assertEquals($expectedversion, $actualversion,
            'environment_check_php_unsupported_version returns the unsupported version if defined in the data');
        $this->assertFalse($result->getStatus(),
            "environment_check_php_unsupported_version sets the result status to false if the current version".
            " is greater than the unsupported version");
    }
}
