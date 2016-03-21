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


require_once dirname(__FILE__) . '/../source/bootstrap.php';
include_once dirname(__FILE__) . '/generator.php';

class tester
{

    private $generator = null;
    private $logFilePath = '/tmp/performance.log';

    public function logPerformance($mode, $typeOfEntries, $nrEntries, $timeElapsed)
    {
        $fh = fopen($this->logFilePath, 'a');
        $data = array(
            $mode, $typeOfEntries, $nrEntries, $timeElapsed, round(($nrEntries / $timeElapsed), 5)
        );
        fputcsv($fh, $data, ';');
        fclose($fh);
    }

    /**
     * Setup
     */
    public function prepare()
    {
        $nrArticles = 10;
        $nrVariants = 10;
        $nrCategories = 10;
        $catTreeDepth = 3;

        $generator = $this->getGenerator();
        $generator->prepareDatabase();
        $generator->configureShop();

        //creation
        // $durationCreateLanguages = $generator->createLanguages(10);

        //create articles
        $timeElapsed = $generator->createArticles($nrArticles, $nrVariants, false);
        // echo 'Written ' . $nrArticles . ' articles with ' . $nrVariants . ' variants in ' . $timeElapsed . ' seconds. Ratio ' . ($nrArticles * $nrVariants) /  $timeElapsed . PHP_EOL;
        $this->logPerformance('w', 'article-variants', $nrArticles * $nrVariants, $timeElapsed);

        //create categories
        $timeElapsed = $generator->createCategories($nrCategories, 3);
        // echo 'Written ' . $nrCategories . ' categories in ' . $timeElapsed . 'seconds. Ratio ' . $nrCategories / $timeElapsed . PHP_EOL;
        $this->logPerformance('w', 'categories', $nrCategories, $timeElapsed);

        //assign each article to up to n categories
        $timeElapsed = $generator->assignArt2Cat($catTreeDepth);
        // echo 'Written articles to categories ' . $timeElapsed . ' sec' . PHP_EOL;
        $this->logPerformance('w', 'article-categories', $nrArticles * $catTreeDepth, $timeElapsed);
    }

    /**
     * Test loading newest articles list.
     *
     * @return float
     */
    public function performanceArticleList()
    {
        //test loading newest articles list
        $durationLoadNewestArticles = $this->getGenerator()->loadArticleList();

        return $durationLoadNewestArticles;
    }

    /**
     * Randomly load articles in each language.
     *
     * @param int $count
     *
     * @return float
     */
    public function loadArticlesInAllLanguages($count = 10000)
    {
        $durationLoadArticles = $this->getGenerator()->loadArticles($count);

        return $durationLoadArticles;
    }

    /**
     * Randomly load articles, only call oxArticle::load, not loadInLang.
     *
     * @param $count
     *
     * @return float
     */
    public function loadArticles($count = 10000, $baseLanguage = 'de')
    {
        oxRegistry::getLang()->setBaseLanguage($baseLanguage);

        $durationLoadArticles = $this->getGenerator()->loadArticles($count);

        return $durationLoadArticles;
    }

    /**
     * Getter for generator object
     *
     * @return null|performanceGenerator
     */
    private function getGenerator()
    {
        if (is_null($this->generator)) {
            $this->generator = new performanceGenerator;
        }

        return $this->generator;
    }
}

try {

    $nrLaps = 100;
    $nrArticles = 10;

    $tester = new tester;

    for ($i = 0; $i <= $nrLaps; $i++) {
        $tester->prepare();

        $timeElapsed = $tester->performanceArticleList();
        // echo 'Read 100 newest articles in ' . $timeElapsed . ' seconds' . PHP_EOL;
        $tester->logPerformance('r', 'newest-articles', 100, $timeElapsed);

        $timeElapsed = $tester->loadArticlesInAllLanguages($nrArticles);
        // echo 'Read ' . $nrArticles . ' articles in all languages in ' . $timeElapsed . ' seconds. Ratio ' . $nrArticles / $timeElapsed . PHP_EOL;
        $tester->logPerformance('r', 'articles-all-languages', $nrArticles, $timeElapsed);


        $timeElapsed = $tester->loadArticles($nrArticles);
        // echo 'Read ' . $nrArticles . ' articles in base language ' . $timeElapsed . ' seconds. Ratio ' . $nrArticles / $timeElapsed . PHP_EOL;
        $tester->logPerformance('r', 'articles-base-language', $nrArticles, $timeElapsed);
    }
} catch (Exception $oE) {
    var_dump($oE->getMessage());
    var_dump($oE->getTraceAsString());
}

