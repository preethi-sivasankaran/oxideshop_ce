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
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version   OXID eShop CE
 */


class performanceGenerator
{
    private $startTime = null;

    /**
     * Set start time.
     */
    public function start()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Start time getter.
     *
     * @return float
     */
    public function getStartTime()
    {
        return (float) $this->startTime;
    }

    /**
     * Get time spent since setting start time.
     *
     * @return float
     */
    public function getTimeSpent($roundTo = 4)
    {
        $nowTime = microtime(true);
        $return = $nowTime - $this->getStartTime();
        return round($return, $roundTo);
    }

    /**
     * Prepare database for testing.
     */
    public function prepareDatabase()
    {
        $tables = array('oxarticles',
                        'oxartextends',
                        'oxcategories',
                        'oxobject2category');

        $dbMetaDataHandler = oxNew("oxDbMetaDataHandler");

        foreach ($tables as $table) {
            if ($dbMetaDataHandler->tableExists($table)) {
                $query = "TRUNCATE TABLE {$table}";
                oxDb::getDb()->execute($query);
            }
        }
    }

    /**
     * Configure the shop.
     */
    public function configureShop()
    {
        oxRegistry::getConfig()->saveShopConfVar('num', 'iNewestArticlesMode', 2);
        oxRegistry::getConfig()->setConfigParam('iNewestArticlesMode', 2);
    }

    /**
     * Configure languages.
     *
     * @param int $count
     */
    public function createLanguages($count = 10)
    {
        $this->start();
        $letters = 26;
        $mod = 0;

        for ($i=0;$i<$count;$i++) {
            $mod = ($mod > $letters)? 0 : $mod;
            $languageId = chr(97 + $mod) . $i . chr(97 + $mod);
            $this->insertLanguage($languageId);
            $mod++;
        }

        //we need a fresh instance of language object in registry,
        //otherwise stale data is used for language abbreviations.
        oxRegistry::set('oxLang', null);
        oxRegistry::set('oxTableViewNameGenerator', null);

        return $this->getTimeSpent();
    }

    /**
     * Create many articles with given number of variants each.
     *
     * @param int $count
     *
     * @return float
     */
    public function createArticles($count = 1000, $variants = 0, $skipLongDesc = false)
    {
        $languageIds = oxRegistry::getLang()->getLanguageIds();

        $this->start();
        for ($i=1; $i<=$count; $i++) {
            $this->createArticle($languageIds, null, $variants, $skipLongDesc);
        }

        return $this->getTimeSpent();
    }

    /**
     * Create a category tree.
     *
     * @param int $count
     * @param int $depth
     *
     * @return float
     */
    public function createCategories($count = 20, $depth = 3)
    {
        $languageIds = oxRegistry::getLang()->getLanguageIds();

        $this->start();
        for ($i=1; $i<=$count; $i++) {
            $this->createCategory($languageIds, 'oxrootid', $depth);
        }

        return $this->getTimeSpent();
    }

    /**
     * Assign articles to categories.
     *
     * @param int $count Assign each article to up to $count categories
     * @return float time elapsed in seconds
     */
    public function assignArt2Cat($count = 3)
    {
        $this->start();

        $query = 'SELECT oxid FROM oxcategories';
        $raw = oxDB::getDb()->getArray($query);
        $catCount = count($raw);
        $categoryIds = array();
        foreach ($raw as $sub) {
            $categoryIds[] = $sub[0];
        }

        $offset = 0;
        $chunk  = 100;
        do {
            $query = "SELECT oxid from oxarticles limit {$offset}, {$chunk}";
            $slice = oxDB::getDb()->getArray($query);
            $offset += $chunk;

            foreach ($slice as $sub) {
                $assignTo = array();
                for ($i=0; $i<$count; $i++) {
                    $categoryId = $categoryIds[rand(0,$catCount)];
                    $assignTo[$categoryId] = $categoryId;
                }
                foreach($assignTo as $categoryId) {
                    $this->addToCategory($categoryId, $sub[0]);
                }
            }
        } while (!empty($slice));

        return $this->getTimeSpent();
    }

    /**
     * Load an article list.
     *
     * @return float
     */
    public function loadArticleList()
    {
        $this->start();
        $articleList = oxNew('oxArticleList');
        $articleList->loadNewestArticles(100);

        foreach ($articleList as $article) {
            $title = $article->oxarticles__oxtitle->value;
        }

        return $this->getTimeSpent();
    }

    /**
     * Randomly load $count articles (oxtitle) in all given languages.
     *
     * @param $count
     * @param $languageIds
     *
     * @return float
     */
    public function loadArticlesInLanguage($count, $languageIds)
    {
        $raw = oxDb::getDb()->getArray("SELECT oxid FROM oxarticles");
        $articleIds = array();
        foreach ($raw as $sub) {
            $articleIds[] = $sub[0];
        }

        $this->start();
        for ($i=0; $i<$count; $i++) {
            $articleId = $articleIds[rand(0, count($articleIds) - 1)];
            $article = oxNew('oxArticle');

            foreach($languageIds as $languageId => $abbreviation) {
                $article->loadinLang($languageId, $articleId);
                $article->oxarticles__oxtitle->value;
            }
        }

        return $this->getTimeSpent();
    }

    /**
     * Randomly load $count articles (oxtitle) in all given languages.
     *
     * @param $count
     *
     * @return float
     */
    public function loadArticles($count)
    {
        $raw = oxDb::getDb()->getArray("SELECT oxid FROM oxarticles");
        $articleIds = array();
        foreach ($raw as $sub) {
            $articleIds[] = $sub[0];
        }

        $this->start();
        for ($i=0; $i<$count; $i++) {
            $articleId = $articleIds[rand(0, count($articleIds) - 1)];
            $article = oxNew('oxArticle');
            $article->load($articleId);
        }

        return $this->getTimeSpent();
    }

    /**
     * Assign object to category.
     *
     * @param $categoryId
     * @param $objectId
     */
    private function addToCategory($categoryId, $objectId)
    {
        $base = oxNew('oxBase');
        $base->init('oxobject2category');
        $base->oxobject2category__oxtime = new oxField(0);
        $base->oxobject2category__oxobjectid = new oxField($objectId);
        $base->oxobject2category__oxcatnid = new oxField($categoryId);
        $base->save();
    }

    /**
     * Create a main category with title, shortdesc, longdesc for all given language ids
     * with $depth subcategories.
     *
     * @param array  $languageIds
     * @param string $parentId
     * @param int    $depth        //number of subcategories
     *
     * @return string
     */
    private function createCategory($languageIds, $parentId = 'oxrootid', $depth = 0)
    {
        $categoryId = oxUtilsObject::getInstance()->generateUId();
        $category = oxNew('oxCategory');
        $category->setId($categoryId);

        if (!is_null($parentId)) {
            $category->oxcategories__oxparentid = new oxField($parentId);
        }

        foreach ($languageIds as $languageId => $abbreviation) {
            $category->loadInLang($languageId, $categoryId);
            $category->oxcategories__oxactive = new oxField(1);
            $category->oxcategories__oxtitle = new oxField($categoryId . '_' . $abbreviation);
            $category->oxcategories__oxdesc = new oxField('desc_' . $categoryId . '_' . $abbreviation);
            $category->oxcategories__oxlongdesc = new oxField('longdesc_' . $categoryId . '_' . $abbreviation);
            $category->save();
        }

        if (0 < $depth){
            for ($i=1; $i<=$depth; $i++) {
                $depth--;
                $this->createCategory($languageIds, $categoryId, $depth);
            }
        }

        return $categoryId;
    }

    /**
     * Create an article with title, shortdesc, longdesc for all given language ids.
     * In case variant data is supplied, create variants for article.
     *
     * @param array $languageIds
     * @param null $parentid
     * @param int  $variants
     * @param bool $skipLongDesc
     *
     * @return string
     */
    private function createArticle($languageIds, $parentid = null, $variants = 0, $skipLongDesc = false)
    {
        $articleId = oxUtilsObject::getInstance()->generateUId();
        $article = oxNew('oxArticle');
        $article->setId($articleId);

        if (!is_null($parentid)) {
            $article->oxarticles__oxparentid = new oxField($parentid);
        }
        $article->save();

        foreach ($languageIds as $languageId => $abbreviation) {
            $article->loadInLang($languageId, $articleId);
            $article->oxarticles__oxtitle = new oxField($articleId . '_' . $abbreviation);
            $article->oxarticles__oxshortdesc = new oxField('shortdesc_' . $articleId . '_' . $abbreviation);
            $article->oxarticles__oxsearchkeys = new oxField('searchkeys' . $articleId . '_' . $abbreviation);
            if (!$skipLongDesc) {
                $article->setArticleLongDesc('shortdesc_' . $articleId . '_' . $abbreviation);
            }
            $article->save();
        }

        if (0 < $variants){
            for ($i=1; $i<=$variants; $i++) {
                $this->createArticle($languageIds, $articleId);
            }
        }

        return $articleId;
    }

    /**
     * Insert new language.
     *
     * @param $languageId
     */
    private function insertLanguage($languageId)
    {
        $languageMain = oxNew('language_main');

        $parameters = array(
            'oxid'       => '-1',
            'active'     => '1',
            'abbr'       => $languageId,
            'desc'       => $languageId,
            'baseurl'    => '',
            'basesslurl' => '',
            'sort'       => ''
        );

        $this->setRequestParameter('oxid', '-1');
        $this->setRequestParameter('editval', $parameters);
        $languageMain->save();

        $dbMetaDataHandler = oxNew("oxDbMetaDataHandler");
        $dbMetaDataHandler->updateViews();
    }

    /**
     * Sets parameter to POST.
     *
     * @param string $paramName
     * @param string $paramValue
     */
    private function setRequestParameter($paramName, $paramValue)
    {
        $_POST[$paramName] = $paramValue;
    }
}
