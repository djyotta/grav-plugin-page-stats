<?php
declare (strict_types=1);

namespace Grav\Plugin\PageStats;

use DateTimeImmutable;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Page\Page;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Plugin\PageStats\Geolocation\GeolocationData;
use \PDO;

class Stats {
    private $db;
    private $dbPath;
    private $config;

    public function __construct($dbPath, $config)
    {
        $this->config = $config;

        $this->dbPath = new \SplFileInfo($dbPath);
        $migrate = ! $this->dbPath->isWritable();
        $this->db  = new PDO(
            'sqlite:' . $dbPath,
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );


        if ($migrate) {
            $this->migrate();
        }
    }

    public function migrate() 
    {
        $version = 0;
        try {
            $q = 'SELECT version FROM migrations ORDER BY date Desc LIMIT 1' ;

            $q = $this->query($q, [], $limit, $dateFrom, $dateTo);
    
            if ($q) {
                $version = $q[0]['version'];
            }
        } catch (\Throwable $e) {
            $version = 0;
        }
       
        while (true) {
            $version++;
            $file = new \SplFileInfo(__DIR__ . '/../data/migrations/' . $version . '.sql');
            if (! $file->isFile()) {
                break;
            }
            $contents = file_get_contents((string) $file );
            $this->db->exec($contents);
            $this->db->exec('INSERT INTO migrations (version) VALUES('.$version.');');
        }

    }

   private function isBot() {

        if ( preg_match('/abacho|accona|AddThis|AdsBot|ahoy|AhrefsBot|AISearchBot|alexa|altavista|anthill|appie|applebot|arale|araneo|AraybOt|ariadne|arks|aspseek|ATN_Worldwide|Atomz|baiduspider|baidu|bbot|bingbot|bing|Bjaaland|BlackWidow|BotLink|bot|boxseabot|bspider|calif|CCBot|ChinaClaw|christcrawler|CMC\/0\.01|combine|confuzzledbot|contaxe|CoolBot|cosmos|crawler|crawlpaper|crawl|curl|cusco|cyberspyder|cydralspider|dataprovider|digger|DIIbot|DotBot|downloadexpress|DragonBot|DuckDuckBot|dwcp|EasouSpider|ebiness|ecollector|elfinbot|esculapio|ESI|esther|eStyle|Ezooms|facebookexternalhit|facebook|facebot|fastcrawler|FatBot|FDSE|FELIX IDE|fetch|fido|find|Firefly|fouineur|Freecrawl|froogle|gammaSpider|gazz|gcreep|geona|Getterrobo-Plus|get|girafabot|golem|googlebot|\-google|grabber|GrabNet|griffon|Gromit|gulliver|gulper|hambot|havIndex|hotwired|htdig|HTTrack|ia_archiver|iajabot|IDBot|Informant|InfoSeek|InfoSpiders|INGRID\/0\.1|inktomi|inspectorwww|Internet Cruiser Robot|irobot|Iron33|JBot|jcrawler|Jeeves|jobo|KDD\-Explorer|KIT\-Fireball|ko_yappo_robot|label\-grabber|larbin|legs|libwww-perl|linkedin|Linkidator|linkwalker|Lockon|logo_gif_crawler|Lycos|m2e|majesticsEO|marvin|mattie|mediafox|mediapartners|MerzScope|MindCrawler|MJ12bot|mod_pagespeed|moget|Motor|msnbot|muncher|muninn|MuscatFerret|MwdSearch|NationalDirectory|naverbot|NEC\-MeshExplorer|NetcraftSurveyAgent|NetScoop|NetSeer|newscan\-online|nil|none|Nutch|ObjectsSearch|Occam|openstat.ru\/Bot|packrat|pageboy|ParaSite|patric|pegasus|perlcrawler|phpdig|piltdownman|Pimptrain|pingdom|pinterest|pjspider|PlumtreeWebAccessor|PortalBSpider|psbot|rambler|Raven|RHCS|RixBot|roadrunner|Robbie|robi|RoboCrawl|robofox|Scooter|Scrubby|Search\-AU|searchprocess|search|SemrushBot|Senrigan|seznambot|Shagseeker|sharp\-info\-agent|sift|SimBot|Site Valet|SiteSucker|skymob|SLCrawler\/2\.0|slurp|snooper|solbot|speedy|spider_monkey|SpiderBot\/1\.0|spiderline|spider|suke|tach_bw|TechBOT|TechnoratiSnoop|templeton|teoma|titin|topiclink|twitterbot|twitter|UdmSearch|Ukonline|UnwindFetchor|URL_Spider_SQL|urlck|urlresolver|Valkyrie libwww\-perl|verticrawl|Victoria|void\-bot|Voyager|VWbot_K|wapspider|WebBandit\/1\.0|webcatcher|WebCopier|WebFindBot|WebLeacher|WebMechanic|WebMoose|webquest|webreaper|webspider|webs|WebWalker|WebZip|wget|whowhere|winona|wlm|WOLP|woriobot|WWWC|XGET|xing|yahoo|YandexBot|YandexMobileBot|yandex|yeti|Zeus/i', $_SERVER['HTTP_USER_AGENT'])
        ) {
            return true; 
        }
    
        return false;
    
    } 

    public function dbStats() 
    {
        return [
            'mb' => round($this->dbPath->getSize() / 1024 / 1024, 1),
            'path' => (string) $this->dbPath,
        ];
    }

    
    /**
     * save stats into db
     */
    public function collect(string $ip, GeolocationData $geo, PageInterface $page, $uri,  UserInterface $user, DateTimeImmutable $date): void
    {
        $username = $user->username;
        if ($this->isBot()) {
            if (false === $this->config['log_bots']) {
                error_log('Bot detected and we are configured to not log bot activiy');
                return;
            }
            $username = 'Bot ' . $_SERVER['HTTP_USER_AGENT'];
        }

        if ($this->config['log_admin'] == false &&  $user->authorize('admin.login')) {
            error_log('=====>> Admin user detected, we are configured not to log admin activity.');
            return;
        }

        $s = $this->db->prepare('
            INSERT INTO data
                ("ip", "country", "city", "region", "route", "page_title", "user", "date")
             VALUES
                (:ip, :country, :city, :region, :route, :title, :user, :date)
        ');

        $s->bindValue(':ip', $ip );
        $s->bindValue(':country', $geo->countryCode());
        $s->bindValue(':city', $geo->city());
        $s->bindValue(':region', $geo->region());
        $s->bindValue(':route', (string) $uri);
        $s->bindValue(':title', $page->title());
        $s->bindValue(':user', $username);
        $s->bindValue(':date', $date->format('c'));

        $s->execute();
    }

    private function query(string $q, array $params = [], ?int $limit = null, ?DateTimeImmutable $dateFrom = null, ?DateTimeImmutable $dateTo = null)
    {
        if ($dateFrom && $dateTo) {
            $q .= ' WHERE date BETWEEN :date_from AND :dateTo';
            $params['date_from'] = $dateFrom;
            $params['date_to'] = $dateTo;
        }

        if ($limit) {
            $q .= ' LIMIT :limit';
            $params['limit'] = $limit;
        }

        $s = $this->db->prepare($q);

        foreach($params as $key => $value) {
            $s->bindValue(':'.$key, $value);
        }

        $s->execute();

        return $s->fetchAll(PDO::FETCH_ASSOC);
    }


    public function pagesSummary(int $limit = 10, ?DateTimeImmutable $dateFrom = null, ?DateTimeImmutable $dateTo = null)
    {
        $q = 'SELECT route, page_title, count(route) as hits, count(distinct ip) as visitors, count(distinct user) as users FROM data GROUP BY page_title ORDER BY hits DESC';

        return $this->query($q, [], $limit, $dateFrom, $dateTo);
    }

    public function recentPages(int $limit = 10, ?DateTimeImmutable $dateFrom = null, ?DateTimeImmutable $dateTo = null)
    {
        // $q = 'SELECT route, page_title, count(route) as hits, date FROM data GROUP BY route ORDER BY date DESC';
        $q = 'SELECT route, page_title, ip, user, country, city, date FROM data ORDER BY date DESC';

        return $this->query($q, [], $limit, $dateFrom, $dateTo);
    }

    public function siteSummary(?DateTimeImmutable $dateFrom = null, ?DateTimeImmutable $dateTo = null)
    {
        $hits = $this->query('SELECT date(date) as date, route, page_title, count(route) as hits FROM data GROUP BY date(date)', [], $dateFrom, $dateTo);
        $visitors = $this->query('SELECT date(date) as date, route, page_title, ip, count(distinct ip) as hits FROM data GROUP BY date(date)',  [], $dateFrom, $dateTo);
        $users = $this->query('SELECT date(date) as date, route, page_title, ip, count(distinct user) as hits FROM data GROUP BY date(date)',  [], $dateFrom, $dateTo);

        return [
            'hits' => $hits,
            'visitors' => $visitors,
            'users' => $users,
        ];
    }
}