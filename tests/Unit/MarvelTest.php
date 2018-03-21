<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use \Artisan;
use \Excel;

class MarvelTest extends TestCase
{
	/**
	 *
	 */
	const COMMAND = 'marvel';
	/**
	 * Maximal number of saved entries.
	 */
	const MAX_ROWS = 40;
	/**
	 * Path to file used to output results in last test case.
	 */
	private $testFilePath = 'testOutput.csv';

	/**
	 * Cleans up evironment after executing test case.
	 * Deletes output file if it exists.
	 *
	 * @return void
	 */
	public function tearDown(){
		// delete file if it exists.
		if (file_exists($this->testFilePath)){
			unlink($this->testFilePath);
		}
	}

	/**
	 * Verifies that exception is thrown when character name parameter is not provided.
	 *
	 * @return void
	 */
	public function testMissingCharacterParameter()
	{
		// Command parameters for test
		$params=[
    		'type' => 'Event',
            'path' => $this->testFilePath,
        ];

		$exceptionThrown = false;
		try
		{
        	// execute command
			Artisan::call(self::COMMAND, $params);
	    }
	    catch(\Symfony\Component\Console\Exception\RuntimeException $e){
	    	// check exception
	    	$exceptionThrown = ($e->getMessage() == 'Not enough arguments (missing: "character").');
	    }
	    $this->assertTrue($exceptionThrown);

    	// check that file ha not been created
        $this->assertFileNotExists($params['path']);
	}

	/**
	 * Verifies that exception is thrown when data type parameter is not provided.
	 *
	 * @return void
	 */
	public function testMissingTypeParameter()
	{
		// Command parameters for test
		$params=[
    		'character' => 'spider-man',
            'path' => $this->testFilePath,
        ];

		$exceptionThrown = false;
		try
		{
        	// execute command
			Artisan::call(self::COMMAND, $params);
	    }
	    catch(\Symfony\Component\Console\Exception\RuntimeException $e){
	    	// check exception
	    	$exceptionThrown = ($e->getMessage() == 'Not enough arguments (missing: "type").');
	    }
	    $this->assertTrue($exceptionThrown);

    	// check that file ha not been created
        $this->assertFileNotExists($params['path']);
	}

	/**
	 * Verifies that exit code 1 is returned when data type is invalid.
	 *
	 * @return void
	 */
	public function testInvalidDataType()
	{
		// Command parameters for test
		$params=[
    		'character' => 'spider-man',
            'type' => 'invalidDataType',
            'path' => $this->testFilePath,
        ];

        // execute command
    	$exitCode = Artisan::call(self::COMMAND, $params);

    	// check exit code
    	$this->assertEquals($exitCode, 1);

    	// check that file ha not been created
        $this->assertFileNotExists($params['path']);
	}

	/**
	 * Verifies that exit code 2 is returned when character with specified name does not exist.
	 *
	 * @return void
	 */
	public function testCharacterDoesNotExist()
	{
		// Command parameters for test
		$params = [
    		'character' => 'CharacterWithThisNameDoesNotExist',
            'type' => 'Event',
            'path' => $this->testFilePath,
        ];

        // execute command
    	$exitCode = Artisan::call(self::COMMAND, $params);

    	// check exit code
    	$this->assertEquals($exitCode, 2);

    	// check that file ha not been created
        $this->assertFileNotExists($params['path']);
	}

	/**
	 * Verifies that result of given data type and for character with given name is properly obtained and stored.
	 *
	 * @param type string
	 * @param character string
	 * @return void
	 */
	private function executeTypeTest($type, $character = 'spider-man')
	{
		// Command parameters for test
		$params = [
    		'character' => $character,
            'type' => $type,
            'path' => $this->testFilePath,
        ];

        // execute command
    	$exitCode = Artisan::call(self::COMMAND, $params);

    	// check exit code
    	$this->assertEquals($exitCode, 0);

    	// check file exists
        $this->assertFileExists($params['path']);
    	$result = Excel::load($params['path'])->all()->toArray();

    	// result body should have no more than MAX_ROWS rows.
    	$this->assertLessThanOrEqual(self::MAX_ROWS, sizeof($result));

    	// check result body
    	if (sizeof($result) == 0){
    		$this->markTestIncomplete('Result body empty for character '.$character.' and data tye '.$type.', consider changing character.');
    	}
    	foreach ($result as $row){
    		$cells = array_values($row);
    		$this->assertTrue(sizeof($cells) == 5);
    		$this->assertEquals($cells[0], $params['character']);
    		$this->assertEquals($cells[1], $params['type']);
    		$this->assertTrue(is_string($cells[2]));
    		$this->assertTrue(is_null($cells[3]) || is_string($cells[3]));
    		$this->assertTrue(strtotime($cells[4]) !== false);

	    	// check result header
	    	$heading = array_keys($row);
	    	$this->assertEquals(sizeof($heading), 5);
	    	$this->assertEquals($heading[0], 'Character');
	    	$this->assertEquals($heading[1], 'Data type');
	    	$this->assertEquals($heading[2], $type.' name');
	    	$this->assertEquals($heading[3], $type.' description');
	    	$this->assertEquals($heading[4], $type.' date first published');
    	}
	}

	/**
	 * Verifies data type comic is properly fetched.
	 *
	 * @return void
	 */
	public function testComicType()
	{
		$this->executeTypeTest('Comic');
	}

	/**
	 * Verifies data type event is properly fetched.
	 *
	 * @return void
	 */
	public function testEventType()
	{
		$this->executeTypeTest('Event');
	}

	/**
	 * Verifies data type series is properly fetched.
	 *
	 * @return void
	 */
	public function testSeriesType()
	{
		$this->executeTypeTest('Series');
	}

	/**
	 * Verifies data type story is properly fetched.
	 *
	 * @return void
	 */
	public function testStoryType()
	{
		$this->executeTypeTest('Story');
	}

	/**
	 * Verifies that character name with special characters is properly handled.
	 *
	 * @return void
	 */
	public function testCharacterEncoding()
	{
        $this->executeTypeTest('Event', '3-D Man');
	}
}
