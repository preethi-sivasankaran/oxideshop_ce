<?php
/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version       OXID eShop CE
 */

/**
 * NOTE:
 * You have to install Faker to use this code:
 * composer require fzaninotto/faker
 *
 */


// Connection data
$host = 'localhost';
$database = 'oxid';
$user = 'oxid';
$password = 'oxid';


require_once '../../source/vendor/autoload.php';

// Variables
$nrOfEntriesToRetrieve = 100;
$nrOfTestsToRun = 10;
$typeOfEntries = 'fulltext-oxtags';
$logFilePath = '/tmp/full-text-performance.myisam.csv';
$mode = 'r';

$mysqli = new mysqli($host, $user, $password, $database, 3306);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

// use the factory to create a Faker\Generator instance
$faker = Faker\Factory::create();

$tags = $faker->words($nrOfEntriesToRetrieve);

for ($i = 1; $i <= $nrOfTestsToRun; $i++) {
    $startTime = microtime(true);
    for ($k = 0; $k < $nrOfEntriesToRetrieve; $k++) {
        getTag($tags[$k], $mysqli);
    }
    $endTime = microtime(true);

    $elapsedTime = $endTime - $startTime;
    logPerformance($mode, $typeOfEntries , $nrOfEntriesToRetrieve, $elapsedTime, $logFilePath);

}

function getTag($tagName, $mysqli)
{
    $query = "SELECT OXID FROM oxartextends WHERE MATCH (OXTAGS) AGAINST ('$tagName' IN BOOLEAN MODE)";
    if ($result = $mysqli->query($query)) {
        $result->close();
    } else {
        printf("Error: %s\n", $mysqli->error);
        printf("Query: %s\n", $query);
    }
}

function logPerformance($mode, $typeOfEntries, $nrEntries, $timeElapsed, $logFilePath)
{
    $fh = fopen($logFilePath, 'a');
    $data = array(
        $mode, $typeOfEntries, $nrEntries, $timeElapsed, round(($nrEntries / $timeElapsed), 5)
    );
    fputcsv($fh, $data, ';');
    fclose($fh);
}
