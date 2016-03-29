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

require_once '../../source/vendor/autoload.php';

// Connection data
$host = 'localhost';
$database = 'oxid';
$user = 'oxid';
$password = 'oxid';

// Variables
$nrOfEntriesToInsert = 100000;

$mysqli = new mysqli($host, $user, $password, $database, 3306);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}


// use the factory to create a Faker\Generator instance
$faker = Faker\Factory::create();


for ($i=0; $i < $nrOfEntriesToInsert; $i++) {
    echo $i . PHP_EOL;
    generateArtExtendsEntries($faker, $mysqli);
}

/**
 * @param $faker
 * @param $mysqli
 */
function generateArtExtendsEntries($faker, $mysqli)
{
    $longDesc = $faker->text($maxNbChars = 200);
    $tags = implode(',', $faker->words(10));


    $query = vsprintf('INSERT INTO `oxid`.`oxartextends` (`OXID`, `OXLONGDESC`, `OXTAGS`) VALUES (REPLACE( UUID( ) ,  \'-\',  \'\' ), \'%s\', \'%s\');', array($longDesc, $tags));

    if (!$mysqli->query($query)) {
        echo "query failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }
}





